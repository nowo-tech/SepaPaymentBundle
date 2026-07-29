<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Generator;

use DateTime;
use DateTimeInterface;
use Digitick\Sepa\DomBuilder\DomBuilderFactory;
use Digitick\Sepa\GroupHeader;
use Digitick\Sepa\PaymentInformation;
use Digitick\Sepa\TransferFile\CustomerDirectDebitTransferFile;
use Digitick\Sepa\TransferInformation\CustomerDirectDebitTransferInformation;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Exception;
use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Event\AfterDirectDebitGenerationEvent;
use Nowo\SepaPaymentBundle\Event\BeforeDirectDebitGenerationEvent;
use Nowo\SepaPaymentBundle\Logger\SepaPaymentLogger;
use Nowo\SepaPaymentBundle\Lookup\BicLookupServiceInterface;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitData;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitTransaction;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Nowo\SepaPaymentBundle\Validator\XsdValidator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function count;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;
use function strlen;

use const ENT_XML1;

/**
 * SEPA Direct Debit generator.
 * Generates SEPA Direct Debit XML files using Digitick\Sepa library according to ISO 20022 standard.
 * Used for collection remittances where the creditor collects money from the debtor based on a SEPA mandate.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class DirectDebitGenerator
{
    public const SERVICE_NAME = 'nowo_sepa_payment.generator.direct_debit_generator';

    /**
     * Whether to validate generated XML against XSD schema.
     */
    private bool $validateXsd = false;

    /**
     * Constructor.
     *
     * @param IbanValidator $ibanValidator IBAN validator instance
     * @param XsdValidator|null $xsdValidator Optional XSD validator instance
     * @param bool $validateXsd Whether to enable XSD validation (default: false)
     * @param EventDispatcherInterface|null $eventDispatcher Optional event dispatcher for Symfony events
     * @param SepaPaymentLogger|null $logger Optional logger for structured logging
     * @param BicLookupServiceInterface|null $bicLookupService Optional BIC lookup service for auto-filling BIC
     */
    public function __construct(
        private readonly IbanValidator $ibanValidator,
        /**
         * XSD validator instance (optional).
         */
        private readonly ?XsdValidator $xsdValidator = null,
        bool $validateXsd = false,
        /**
         * Event dispatcher instance (optional).
         */
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
        /**
         * Logger instance (optional).
         */
        private readonly ?SepaPaymentLogger $logger = null,
        /**
         * BIC lookup service instance (optional).
         */
        private readonly ?BicLookupServiceInterface $bicLookupService = null
    ) {
        $this->validateXsd = $validateXsd && $this->xsdValidator instanceof XsdValidator;
    }

    /**
     * Generates a SEPA Direct Debit XML file from array data.
     *
     * @param array<string, mixed> $data The direct debit data in array format
     *
     * @throws InvalidArgumentException If the data is invalid
     *
     * @return string The XML content
     */
    public function generateFromArray(array $data): string
    {
        $directDebitData = $this->createDirectDebitDataFromArray($data);

        return $this->generate($directDebitData);
    }

    /**
     * Creates an HTTP Response with XML content for download.
     *
     * @param string $xmlData The XML content
     * @param string $filename The filename for the download (e.g., "direct-debit.xml")
     *
     * @return Response The HTTP response with XML content
     */
    public function createResponse(string $xmlData, string $filename): Response
    {
        return new Response($xmlData, Response::HTTP_OK, [
            'Content-Type'        => 'application/xml',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /**
     * Generates a SEPA Direct Debit XML file.
     *
     * @param DirectDebitData $directDebitData The direct debit data
     *
     * @throws InvalidArgumentException If the data is invalid
     *
     * @return string The XML content
     */
    public function generate(DirectDebitData $directDebitData): string
    {
        $transactionCount = count($directDebitData->getTransactions());
        $messageId        = $directDebitData->getMessageId();

        // Log generation start
        if ($this->logger instanceof SepaPaymentLogger) {
            $this->logger->logDirectDebitGenerationStart($messageId, $transactionCount);
        }

        try {
            // Dispatch before generation event
            if ($this->eventDispatcher instanceof EventDispatcherInterface) {
                $beforeEvent = new BeforeDirectDebitGenerationEvent($directDebitData);
                $this->eventDispatcher->dispatch($beforeEvent);
                $directDebitData = $beforeEvent->getDirectDebitData();
            }

            $this->validateDirectDebitData($directDebitData);

            // Create and configure group header
            $groupHeader = new GroupHeader(
                $directDebitData->getMessageId(),
                $directDebitData->getInitiatingPartyName(),
            );

            // Create transfer file (pain.008.001.02 format) with group header
            $transferFile = new CustomerDirectDebitTransferFile($groupHeader);

            // Auto-fill creditor BIC if missing
            $creditorBic = $directDebitData->getCreditorBic();
            if ($creditorBic === null && $this->bicLookupService instanceof BicLookupServiceInterface) {
                $lookedUpBic = $this->bicLookupService->lookupBic($directDebitData->getCreditorIban());
                if ($lookedUpBic !== null) {
                    $creditorBic = $lookedUpBic;
                }
            }

            // Create payment information
            $paymentInformation = new PaymentInformation(
                $directDebitData->getPaymentInfoId(),
                $this->ibanValidator->normalize($directDebitData->getCreditorIban()),
                $creditorBic ?? '',
                $directDebitData->getCreditorName(),
                'EUR',
            );
            // Payment method is automatically set based on the transfer file type (CustomerDirectDebitTransferFile)
            $paymentInformation->setDueDate($directDebitData->getDueDate());
            $paymentInformation->setCreditorId($directDebitData->getCreditorId());
            $paymentInformation->setSequenceType($directDebitData->getSequenceType());
            $paymentInformation->setLocalInstrumentCode($directDebitData->getLocalInstrumentCode());

            // Set creditor address if available
            $creditorAddress = $directDebitData->getCreditorAddress();
            if ($creditorAddress !== null) {
                $this->setCreditorPostalAddress($paymentInformation, $creditorAddress);
            }

            // Add transactions
            foreach ($directDebitData->getTransactions() as $transaction) {
                $transferInformation = new CustomerDirectDebitTransferInformation(
                    (int) round($transaction->getAmount() * 100), // Convert to cents
                    $this->ibanValidator->normalize($transaction->getDebtorIban()),
                    $transaction->getDebtorName(),
                    $transaction->getEndToEndId(),
                );

                $transferInformation->setMandateId($transaction->getDebtorMandate());
                $transferInformation->setMandateSignDate($transaction->getDebtorMandateSignDate());

                if ($transaction->getRemittanceInformation() !== null) {
                    $transferInformation->setRemittanceInformation($transaction->getRemittanceInformation());
                }

                // Auto-fill debtor BIC if missing
                $debtorBic = $transaction->getDebtorBic();
                if ($debtorBic === null && $this->bicLookupService instanceof BicLookupServiceInterface) {
                    $lookedUpBic = $this->bicLookupService->lookupBic($transaction->getDebtorIban());
                    if ($lookedUpBic !== null) {
                        $debtorBic = $lookedUpBic;
                    }
                }

                // Set debtor BIC if available
                if ($debtorBic !== null) {
                    $transferInformation->setBic($debtorBic);
                }

                // Apply additional data if available
                // Note: This allows for future extensibility. Additional fields can be set
                // using methods available in CustomerDirectDebitTransferInformation
                $this->applyAdditionalData($transferInformation, $transaction);

                $paymentInformation->addTransfer($transferInformation);
            }

            $transferFile->addPaymentInformation($paymentInformation);

            // Generate XML
            $domBuilder = DomBuilderFactory::createDomBuilder($transferFile);
            $xml        = $domBuilder->asXml();

            // Add addresses to XML if they were provided
            $xml = $this->addAddressesToXml($xml, $directDebitData);

            // Validate against XSD schema if enabled
            if ($this->validateXsd && $this->xsdValidator instanceof XsdValidator) {
                try {
                    $this->xsdValidator->validateDirectDebit($xml);
                } catch (InvalidArgumentException $e) {
                    throw new InvalidArgumentException('Generated XML failed XSD validation: ' . $e->getMessage(), 0, $e);
                }
            }

            // Dispatch after generation event
            if ($this->eventDispatcher instanceof EventDispatcherInterface) {
                $afterEvent = new AfterDirectDebitGenerationEvent($xml, $directDebitData->getMessageId());
                $this->eventDispatcher->dispatch($afterEvent);
                $xml = $afterEvent->getXml();
            }

            // Log generation success
            if ($this->logger instanceof SepaPaymentLogger) {
                $this->logger->logDirectDebitGenerationSuccess($messageId, $transactionCount, strlen($xml));
            }

            return $xml;
        } catch (Exception $e) {
            // Log generation failure
            if ($this->logger instanceof SepaPaymentLogger) {
                $this->logger->logDirectDebitGenerationFailure($messageId, $e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Creates DirectDebitData from array format.
     * Supports both camelCase and snake_case field names.
     *
     * @param array<string, mixed> $data The data in array format
     *
     * @throws InvalidArgumentException If the data is invalid
     *
     * @return DirectDebitData The DirectDebitData object
     */
    private function createDirectDebitDataFromArray(array $data): DirectDebitData
    {
        // Normalize field names (support both camelCase and snake_case)
        $data = $this->normalizeArrayKeys($data);

        // Validate required fields
        $required = ['reference', 'bankAccountOwner', 'paymentInfoId', 'dueDate', 'creditorName', 'creditorIban', 'seqType', 'creditorId', 'localInstrumentCode'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Parse due date
        $dueDate = $data['dueDate'];
        if (is_string($dueDate)) {
            $dueDate = new DateTime($dueDate);
        } elseif (!$dueDate instanceof DateTimeInterface) {
            throw new InvalidArgumentException('dueDate must be a string or DateTimeInterface');
        }

        $directDebitData = new DirectDebitData(
            $data['reference'],
            $data['bankAccountOwner'],
            $data['paymentInfoId'],
            $dueDate,
            $data['creditorName'],
            $data['creditorIban'],
            $data['seqType'],
            $data['creditorId'],
            $data['localInstrumentCode'],
        );

        if (isset($data['creditorBic'])) {
            $directDebitData->setCreditorBic($data['creditorBic']);
        }

        // Set creditor address if provided (optional)
        if (isset($data['creditorAddress']) && is_array($data['creditorAddress']) && (isset($data['creditorAddress']) && $data['creditorAddress'] !== [])) {
            $directDebitData->setCreditorAddressFromArray($data['creditorAddress']);
        } elseif (isset($data['creditor_street']) || isset($data['creditor_city']) || isset($data['creditor_postal_code']) || isset($data['creditor_country'])
                  || isset($data['creditorStreet']) || isset($data['creditorCity']) || isset($data['creditorPostalCode']) || isset($data['creditorCountry'])) {
            // Support individual address fields (only if at least one is provided)
            $directDebitData->setCreditorAddress(
                $data['creditor_street'] ?? $data['creditorStreet'] ?? null,
                $data['creditor_city'] ?? $data['creditorCity'] ?? null,
                $data['creditor_postal_code'] ?? $data['creditorPostalCode'] ?? null,
                $data['creditor_country'] ?? $data['creditorCountry'] ?? null,
            );
        }

        // Add transactions (after normalization, 'items' should already be 'transactions')
        if (isset($data['transactions']) && is_array($data['transactions'])) {
            foreach ($data['transactions'] as $transactionData) {
                // Normalize transaction array keys
                $transactionData = $this->normalizeTransactionArrayKeys($transactionData);
                $transaction     = $this->createTransactionFromArray($transactionData, $dueDate);
                $directDebitData->addTransaction($transaction);
            }
        }

        return $directDebitData;
    }

    /**
     * Normalizes array keys from snake_case to camelCase format.
     * Supports both formats for backward compatibility.
     *
     * @param array<string, mixed> $data The data array
     *
     * @return array<string, mixed> Normalized array
     */
    private function normalizeArrayKeys(array $data): array
    {
        $mapping = [
            'message_id'            => 'reference',
            'initiating_party_name' => 'bankAccountOwner',
            'payment_name'          => 'paymentInfoId',
            'due_date'              => 'dueDate',
            'creditor_name'         => 'creditorName',
            'creditor_iban'         => 'creditorIban',
            'creditor_bic'          => 'creditorBic',
            'sequence_type'         => 'seqType',
            'creditor_id'           => 'creditorId',
            'instrument_code'       => 'localInstrumentCode',
            'items'                 => 'transactions',
            'creditor_address'      => 'creditorAddress',
        ];

        $normalized = [];
        foreach ($data as $key => $value) {
            // If key exists in mapping, use mapped key, otherwise keep original
            $normalizedKey              = $mapping[$key] ?? $key;
            $normalized[$normalizedKey] = $value;
        }

        return $normalized;
    }

    /**
     * Normalizes transaction array keys from snake_case to camelCase format.
     *
     * @param array<string, mixed> $data The transaction data array
     *
     * @return array<string, mixed> Normalized array
     */
    private function normalizeTransactionArrayKeys(array $data): array
    {
        $mapping = [
            'instruction_id'                => 'endToEndId',
            'debtor_iban'                   => 'debtorIban',
            'debtor_name'                   => 'debtorName',
            'debtor_mandate'                => 'debtorMandate',
            'debtor_mandate_signature_date' => 'debtorMandateSignDate',
            'debtor_mandate_sign_date'      => 'debtorMandateSignDate',
            'information'                   => 'remittanceInformation',
            'remittance_information'        => 'remittanceInformation',
            'debtor_address'                => 'debtorAddress',
        ];

        $normalized = [];
        foreach ($data as $key => $value) {
            // If key exists in mapping, use mapped key, otherwise keep original
            $normalizedKey              = $mapping[$key] ?? $key;
            $normalized[$normalizedKey] = $value;
        }

        return $normalized;
    }

    /**
     * Creates DirectDebitTransaction from array format.
     *
     * @param array<string, mixed> $transactionData The transaction data
     * @param DateTimeInterface $defaultDate Default date for mandate sign date if not provided
     *
     * @throws InvalidArgumentException If the data is invalid
     *
     * @return DirectDebitTransaction The DirectDebitTransaction object
     */
    private function createTransactionFromArray(array $transactionData, DateTimeInterface $defaultDate): DirectDebitTransaction
    {
        $required = ['amount', 'debtorIban', 'debtorName', 'debtorMandate', 'endToEndId'];
        foreach ($required as $field) {
            if (!isset($transactionData[$field])) {
                throw new InvalidArgumentException("Missing required transaction field: {$field}");
            }
        }

        // Parse amount (convert from cents if needed, but assume it's already in currency units)
        $amount = (float) $transactionData['amount'];
        // If amount seems to be in cents (very large number), convert to currency units
        if ($amount > 10000) {
            $amount /= 100;
        }

        // Parse mandate sign date
        $mandateSignDate = $defaultDate;
        if (isset($transactionData['debtorMandateSignDate'])) {
            $signDate = $transactionData['debtorMandateSignDate'];
            if (is_string($signDate)) {
                $mandateSignDate = new DateTime($signDate);
            } elseif ($signDate instanceof DateTimeInterface) {
                $mandateSignDate = $signDate;
            }
        }

        $transaction = new DirectDebitTransaction(
            $amount,
            $transactionData['debtorIban'],
            $transactionData['debtorName'],
            $transactionData['debtorMandate'],
            $mandateSignDate,
            $transactionData['endToEndId'],
        );

        if (isset($transactionData['remittanceInformation'])) {
            $transaction->setRemittanceInformation($transactionData['remittanceInformation']);
        }

        // Set debtor BIC if available
        if (isset($transactionData['debtorBic'])) {
            $transaction->setDebtorBic($transactionData['debtorBic']);
        }

        // Set debtor address if provided (optional)
        if (isset($transactionData['debtorAddress']) && is_array($transactionData['debtorAddress']) && (isset($transactionData['debtorAddress']) && $transactionData['debtorAddress'] !== [])) {
            $transaction->setDebtorAddressFromArray($transactionData['debtorAddress']);
        } elseif (isset($transactionData['debtor_street']) || isset($transactionData['debtor_city']) || isset($transactionData['debtor_postal_code']) || isset($transactionData['debtor_country'])
                  || isset($transactionData['debtorStreet']) || isset($transactionData['debtorCity']) || isset($transactionData['debtorPostalCode']) || isset($transactionData['debtorCountry'])) {
            // Support individual address fields (only if at least one is provided)
            $transaction->setDebtorAddress(
                $transactionData['debtor_street'] ?? $transactionData['debtorStreet'] ?? null,
                $transactionData['debtor_city'] ?? $transactionData['debtorCity'] ?? null,
                $transactionData['debtor_postal_code'] ?? $transactionData['debtorPostalCode'] ?? null,
                $transactionData['debtor_country'] ?? $transactionData['debtorCountry'] ?? null,
            );
        }

        // Store any additional fields that are not standard
        $standardFields = ['amount', 'debtorIban', 'debtorName', 'debtorMandate', 'debtorMandateSignDate', 'endToEndId', 'remittanceInformation', 'debtorBic', 'debtorAddress', 'debtor_street', 'debtor_city', 'debtor_postal_code', 'debtor_country', 'debtorStreet', 'debtorCity', 'debtorPostalCode', 'debtorCountry'];
        $additionalData = [];
        foreach ($transactionData as $key => $value) {
            if (!in_array($key, $standardFields, true)) {
                $additionalData[$key] = $value;
            }
        }
        if ($additionalData !== []) {
            $transaction->setAdditionalData($additionalData);
        }

        return $transaction;
    }

    /**
     * Applies additional data to the transfer information if available.
     * This method can be extended to support additional fields from the Digitick\Sepa library.
     *
     * @param CustomerDirectDebitTransferInformation $transferInformation The transfer information object
     * @param DirectDebitTransaction $transaction The transaction data
     */
    private function applyAdditionalData(
        CustomerDirectDebitTransferInformation $transferInformation,
        DirectDebitTransaction $transaction
    ): void {
        $transaction->getAdditionalData();

        // Set debtor address if available
        $debtorAddress = $transaction->getDebtorAddress();
        if ($debtorAddress !== null) {
            $this->setPostalAddress($transferInformation, $debtorAddress);
        }

        // Example: If you need to set instruction identification or other fields
        // that are supported by CustomerDirectDebitTransferInformation, you can add them here
        // For example:
        // if (isset($additionalData['instructionId'])) {
        //     $transferInformation->setInstructionId($additionalData['instructionId']);
        // }

        // This method is intentionally left extensible for future needs
        // You can add more field mappings here as needed
    }

    /**
     * Sets postal address on transfer information (debtor address).
     * Uses available methods from the Digitick\Sepa library.
     *
     * @param object $transferInformation The transfer information object (CustomerDirectDebitTransferInformation or test double)
     * @param array<string, string|null> $address Address array with keys: street, city, postalCode, country
     */
    private function setPostalAddress(
        object $transferInformation,
        array $address
    ): void {
        if (method_exists($transferInformation, 'setPostalAddress')) {
            $transferInformation->setPostalAddress(
                $address['street'] ?? '',
            );
        }
        if (method_exists($transferInformation, 'setDebtorPostalAddress')) {
            $transferInformation->setDebtorPostalAddress(
                $address['street'] ?? '',
                $address['city'] ?? '',
                $address['postalCode'] ?? '',
                $address['country'] ?? '',
            );
        } elseif (method_exists($transferInformation, 'setAddress')) {
            $transferInformation->setAddress(
                $address['street'] ?? '',
                $address['city'] ?? '',
                $address['postalCode'] ?? '',
                $address['country'] ?? '',
            );
        }
        // Note: If the library doesn't support addresses in this format,
        // the address is still stored in additionalData for internal use
    }

    /**
     * Sets creditor postal address on payment information.
     * Uses available methods from the Digitick\Sepa library.
     *
     * @param object $paymentInformation The payment information object (PaymentInformation or test double)
     * @param array<string, string|null> $address Address array with keys: street, city, postalCode, country
     */
    private function setCreditorPostalAddress(
        object $paymentInformation,
        array $address
    ): void {
        // Try to set creditor postal address using available methods
        if (method_exists($paymentInformation, 'setCreditorPostalAddress')) {
            $paymentInformation->setCreditorPostalAddress(
                $address['street'] ?? '',
                $address['city'] ?? '',
                $address['postalCode'] ?? '',
                $address['country'] ?? '',
            );
        } elseif (method_exists($paymentInformation, 'setPostalAddress')) {
            $paymentInformation->setPostalAddress(
                $address['street'] ?? '',
                $address['city'] ?? '',
                $address['postalCode'] ?? '',
                $address['country'] ?? '',
            );
        } elseif (method_exists($paymentInformation, 'setAddress')) {
            $paymentInformation->setAddress(
                $address['street'] ?? '',
                $address['city'] ?? '',
                $address['postalCode'] ?? '',
                $address['country'] ?? '',
            );
        }
        // Note: If the library doesn't support addresses in this format,
        // the address is still stored internally for internal use
    }

    /**
     * Adds addresses to the generated XML using DOM manipulation.
     * This ensures addresses are included even if the library doesn't support them directly.
     *
     * @param string $xml The generated XML
     * @param DirectDebitData $directDebitData The direct debit data with addresses
     *
     * @return string The XML with addresses added
     */
    private function addAddressesToXml(string $xml, DirectDebitData $directDebitData): string
    {
        try {
            $dom                     = new DOMDocument();
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput       = true;

            if (!@$dom->loadXML($xml)) {
                // If XML is invalid, return original
                return $xml;
            }

            $xpath = new DOMXPath($dom);
            // Detect namespace from root element
            $root      = $dom->documentElement;
            $namespace = $root->namespaceURI ?? 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02';
            $xpath->registerNamespace('ns', $namespace);

            // Add creditor address if available
            $creditorAddress = $directDebitData->getCreditorAddress();
            if ($creditorAddress !== null) {
                $this->addCreditorAddressToDom($dom, $xpath, $creditorAddress, $namespace);
            }

            // Add debtor addresses for each transaction
            $transactions = $directDebitData->getTransactions();
            foreach ($transactions as $index => $transaction) {
                $debtorAddress = $transaction->getDebtorAddress();
                if ($debtorAddress !== null) {
                    $this->addDebtorAddressToDom($dom, $xpath, $debtorAddress, $index, $namespace);
                }
            }

            // PHPStan: saveXML() returns string|false; we guarantee valid XML from loadXML(), return string or original
            $saved = $dom->saveXML();

            return $saved !== false ? $saved : $xml;
        } catch (Throwable) {
            // If DOM manipulation fails, return original XML
            return $xml;
        }
    }

    /**
     * Adds creditor address to DOM.
     *
     * @param DOMDocument $dom The DOM document
     * @param DOMXPath $xpath The XPath object
     * @param array<string, string|null> $address The address array
     * @param string $namespace The namespace URI
     */
    private function addCreditorAddressToDom(DOMDocument $dom, DOMXPath $xpath, array $address, string $namespace): void
    {
        $creditorNodes = $xpath->query('//ns:Cdtr');
        if ($creditorNodes === false || $creditorNodes->length === 0) {
            $creditorNodes = $xpath->query('//Cdtr');
            if ($creditorNodes === false || $creditorNodes->length === 0) {
                return;
            }
        }

        $creditorNode = $creditorNodes->item(0);
        if (!$creditorNode instanceof DOMElement) {
            return;
        }
        $this->createPostalAddressElement($dom, $creditorNode, $address, $namespace);
    }

    /**
     * Adds debtor address to DOM.
     *
     * @param DOMDocument $dom The DOM document
     * @param DOMXPath $xpath The XPath object
     * @param array<string, string|null> $address The address array
     * @param int $index Transaction index
     * @param string $namespace The namespace URI
     */
    private function addDebtorAddressToDom(DOMDocument $dom, DOMXPath $xpath, array $address, int $index, string $namespace): void
    {
        $debtorNodes = $xpath->query('//ns:Dbtr');
        if ($debtorNodes === false || $debtorNodes->length === 0) {
            $debtorNodes = $xpath->query('//Dbtr');
            if ($debtorNodes === false || $debtorNodes->length <= $index) {
                return;
            }
        }

        if ($debtorNodes->length <= $index) {
            return;
        }

        $debtorNode = $debtorNodes->item($index);
        if (!$debtorNode instanceof DOMElement) {
            return;
        }
        $this->createPostalAddressElement($dom, $debtorNode, $address, $namespace);
    }

    /**
     * Creates a PstlAdr (Postal Address) element in the DOM.
     *
     * @param DOMDocument $dom The DOM document
     * @param DOMElement $parentNode The parent node
     * @param array<string, string|null> $address The address array
     * @param string $namespace The namespace URI
     */
    private function createPostalAddressElement(DOMDocument $dom, DOMElement $parentNode, array $address, string $namespace): void
    {
        $hasAddress = !empty($address['street'])
            || !empty($address['city'])
            || !empty($address['postalCode'])
            || !empty($address['country']);

        if (!$hasAddress) {
            return;
        }

        // PHPStan: item(0) can be null; removeChild expects DOMNode. Check with instanceof before use.
        $existing = $parentNode->getElementsByTagNameNS($namespace, 'PstlAdr');
        if ($existing->length > 0) {
            $first = $existing->item(0);
            if ($first instanceof DOMNode) {
                $parentNode->removeChild($first);
            }
        }

        $pstlAdr = $dom->createElementNS($namespace, 'PstlAdr');

        if (!empty($address['street'])) {
            $strtNm = $dom->createElementNS($namespace, 'StrtNm', htmlspecialchars($address['street'], ENT_XML1, 'UTF-8'));
            $pstlAdr->appendChild($strtNm);
        }
        if (!empty($address['city'])) {
            $twnNm = $dom->createElementNS($namespace, 'TwnNm', htmlspecialchars($address['city'], ENT_XML1, 'UTF-8'));
            $pstlAdr->appendChild($twnNm);
        }
        if (!empty($address['postalCode'])) {
            $pstCd = $dom->createElementNS($namespace, 'PstCd', htmlspecialchars($address['postalCode'], ENT_XML1, 'UTF-8'));
            $pstlAdr->appendChild($pstCd);
        }
        if (!empty($address['country'])) {
            $ctry = $dom->createElementNS($namespace, 'Ctry', htmlspecialchars($address['country'], ENT_XML1, 'UTF-8'));
            $pstlAdr->appendChild($ctry);
        }

        if ($pstlAdr->childNodes->length > 0) {
            // PHPStan: item(0) can be null; do not access nextSibling without checking it is DOMElement
            $nmNodes = $parentNode->getElementsByTagNameNS($namespace, 'Nm');
            if ($nmNodes->length > 0) {
                $nmFirst     = $nmNodes->item(0);
                $nextSibling = $nmFirst instanceof DOMElement ? $nmFirst->nextSibling : null;
                if ($nextSibling instanceof \DOMNode) {
                    $parentNode->insertBefore($pstlAdr, $nextSibling);
                } else {
                    $parentNode->appendChild($pstlAdr);
                }
            } else {
                $parentNode->appendChild($pstlAdr);
            }
        }
    }

    /**
     * Validates direct debit data.
     *
     * @param DirectDebitData $directDebitData The direct debit data to validate
     *
     * @throws InvalidArgumentException If the data is invalid
     */
    private function validateDirectDebitData(DirectDebitData $directDebitData): void
    {
        if (!$this->ibanValidator->isValid($directDebitData->getCreditorIban())) {
            throw new InvalidArgumentException('Invalid creditor IBAN: ' . $directDebitData->getCreditorIban());
        }

        foreach ($directDebitData->getTransactions() as $transaction) {
            if (!$this->ibanValidator->isValid($transaction->getDebtorIban())) {
                throw new InvalidArgumentException('Invalid debtor IBAN: ' . $transaction->getDebtorIban());
            }
        }
    }
}
