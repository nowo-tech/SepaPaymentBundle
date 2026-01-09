<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Generator;

use Digitick\Sepa\DomBuilder\DomBuilderFactory;
use Digitick\Sepa\GroupHeader;
use Digitick\Sepa\PaymentInformation;
use Digitick\Sepa\TransferFile\CustomerCreditTransferFile;
use Digitick\Sepa\TransferInformation\CustomerCreditTransferInformation;
use Nowo\SepaPaymentBundle\Event\AfterCreditTransferGenerationEvent;
use Nowo\SepaPaymentBundle\Event\BeforeCreditTransferGenerationEvent;
use Nowo\SepaPaymentBundle\Logger\SepaPaymentLogger;
use Nowo\SepaPaymentBundle\Lookup\BicLookupServiceInterface;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\CreditTransferData;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\Transaction;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Nowo\SepaPaymentBundle\Validator\XsdValidator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * SEPA Credit Transfer generator.
 * Generates SEPA Credit Transfer XML files using Digitick\Sepa library according to ISO 20022 standard.
 * Used for payment remittances where the debtor sends money to the creditor.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class CreditTransferGenerator
{
    public const SERVICE_NAME = 'nowo_sepa_payment.generator.credit_transfer_generator';

    /**
     * Whether to validate generated XML against XSD schema.
     *
     * @var bool
     */
    private bool $validateXsd = false;

    /**
     * XSD validator instance (optional).
     *
     * @var XsdValidator|null
     */
    private ?XsdValidator $xsdValidator = null;

    /**
     * Event dispatcher instance (optional).
     *
     * @var EventDispatcherInterface|null
     */
    private ?EventDispatcherInterface $eventDispatcher = null;

    /**
     * Logger instance (optional).
     *
     * @var SepaPaymentLogger|null
     */
    private ?SepaPaymentLogger $logger = null;

    /**
     * BIC lookup service instance (optional).
     *
     * @var BicLookupServiceInterface|null
     */
    private ?BicLookupServiceInterface $bicLookupService = null;

    /**
     * Constructor.
     *
     * @param IbanValidator                  $ibanValidator    IBAN validator instance
     * @param XsdValidator|null              $xsdValidator     Optional XSD validator instance
     * @param bool                           $validateXsd      Whether to enable XSD validation (default: false)
     * @param EventDispatcherInterface|null  $eventDispatcher  Optional event dispatcher for Symfony events
     * @param SepaPaymentLogger|null         $logger           Optional logger for structured logging
     * @param BicLookupServiceInterface|null $bicLookupService Optional BIC lookup service for auto-filling BIC
     */
    public function __construct(
        private IbanValidator $ibanValidator,
        ?XsdValidator $xsdValidator = null,
        bool $validateXsd = false,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?SepaPaymentLogger $logger = null,
        ?BicLookupServiceInterface $bicLookupService = null
    ) {
        $this->xsdValidator = $xsdValidator;
        $this->validateXsd = $validateXsd && null !== $xsdValidator;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
        $this->bicLookupService = $bicLookupService;
    }

    /**
     * Generates a SEPA Credit Transfer XML file from array data.
     *
     * @param array<string, mixed> $data The credit transfer data in array format
     *
     * @throws \InvalidArgumentException If the data is invalid
     *
     * @return string The XML content
     */
    public function generateFromArray(array $data): string
    {
        $creditTransferData = $this->createCreditTransferDataFromArray($data);

        return $this->generate($creditTransferData);
    }

    /**
     * Generates a SEPA Credit Transfer XML file.
     *
     * @param CreditTransferData $creditTransferData The credit transfer data
     *
     * @throws \InvalidArgumentException If the data is invalid
     *
     * @return string The XML content
     */
    public function generate(CreditTransferData $creditTransferData): string
    {
        $transactionCount = count($creditTransferData->getTransactions());
        $messageId = $creditTransferData->getMessageId();

        // Log generation start
        if (null !== $this->logger) {
            $this->logger->logCreditTransferGenerationStart($messageId, $transactionCount);
        }

        try {
            // Dispatch before generation event
            if (null !== $this->eventDispatcher) {
                $beforeEvent = new BeforeCreditTransferGenerationEvent($creditTransferData);
                $this->eventDispatcher->dispatch($beforeEvent);
                $creditTransferData = $beforeEvent->getCreditTransferData();
            }

            $this->validateCreditTransferData($creditTransferData);

            // Create and configure group header
            $groupHeader = new GroupHeader(
                $creditTransferData->getMessageId(),
                $creditTransferData->getInitiatingPartyName()
            );

            // Create transfer file (pain.001.001.03 format) with group header
            $transferFile = new CustomerCreditTransferFile($groupHeader);

            // Auto-fill creditor BIC if missing
            $creditorBic = $creditTransferData->getCreditorBic();
            if (null === $creditorBic && null !== $this->bicLookupService) {
                $lookedUpBic = $this->bicLookupService->lookupBic($creditTransferData->getCreditorIban());
                if (null !== $lookedUpBic) {
                    $creditorBic = $lookedUpBic;
                }
            }

            // Create payment information
            $paymentInformation = new PaymentInformation(
                $creditTransferData->getPaymentInfoId(),
                $this->ibanValidator->normalize($creditTransferData->getCreditorIban()),
                $creditorBic ?? '',
                $creditTransferData->getCreditorName(),
                'EUR'
            );
            // Payment method is automatically set based on the transfer file type (CustomerCreditTransferFile)
            $paymentInformation->setBatchBooking($creditTransferData->isBatchBooking());
            $paymentInformation->setDueDate($creditTransferData->getRequestedExecutionDate());

            // Set creditor address if available
            $creditorAddress = $creditTransferData->getCreditorAddress();
            if (null !== $creditorAddress) {
                $this->setCreditorPostalAddress($paymentInformation, $creditorAddress);
            }

            // Add transactions
            foreach ($creditTransferData->getTransactions() as $transaction) {
                $transferInformation = new CustomerCreditTransferInformation(
                    (int) round($transaction->getAmount() * 100), // Convert to cents
                    $this->ibanValidator->normalize($transaction->getDebtorIban()),
                    $transaction->getDebtorName(),
                    $transaction->getEndToEndId()
                );

                // Auto-fill debtor BIC if missing
                $debtorBic = $transaction->getDebtorBic();
                if (null === $debtorBic && null !== $this->bicLookupService) {
                    $lookedUpBic = $this->bicLookupService->lookupBic($transaction->getDebtorIban());
                    if (null !== $lookedUpBic) {
                        $debtorBic = $lookedUpBic;
                    }
                }

                if (null !== $debtorBic) {
                    $transferInformation->setBic($debtorBic);
                }

                if (null !== $transaction->getRemittanceInformation()) {
                    $transferInformation->setRemittanceInformation($transaction->getRemittanceInformation());
                }

                // Set debtor address if available
                $debtorAddress = $transaction->getDebtorAddress();
                if (null !== $debtorAddress) {
                    $this->setPostalAddress($transferInformation, $debtorAddress);
                }

                $paymentInformation->addTransfer($transferInformation);
            }

            $transferFile->addPaymentInformation($paymentInformation);

            // Generate XML
            $domBuilder = DomBuilderFactory::createDomBuilder($transferFile);
            $xml = $domBuilder->asXml();

            // Add addresses to XML if they were provided
            $xml = $this->addAddressesToXml($xml, $creditTransferData);

            // Validate against XSD schema if enabled
            if ($this->validateXsd && null !== $this->xsdValidator) {
                try {
                    $this->xsdValidator->validateCreditTransfer($xml);
                } catch (\InvalidArgumentException $e) {
                    throw new \InvalidArgumentException('Generated XML failed XSD validation: ' . $e->getMessage(), 0, $e);
                }
            }

            // Dispatch after generation event
            if (null !== $this->eventDispatcher) {
                $afterEvent = new AfterCreditTransferGenerationEvent($xml, $creditTransferData->getMessageId());
                $this->eventDispatcher->dispatch($afterEvent);
                $xml = $afterEvent->getXml();
            }

            // Log generation success
            if (null !== $this->logger) {
                $this->logger->logCreditTransferGenerationSuccess($messageId, $transactionCount, strlen($xml));
            }

            return $xml;
        } catch (\Exception $e) {
            // Log generation failure
            if (null !== $this->logger) {
                $this->logger->logCreditTransferGenerationFailure($messageId, $e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Creates an HTTP Response with XML content for download.
     *
     * @param string $xmlData  The XML content
     * @param string $filename The filename for the download (e.g., "credit-transfer.xml")
     *
     * @return Response The HTTP response with XML content
     */
    public function createResponse(string $xmlData, string $filename): Response
    {
        return new Response($xmlData, Response::HTTP_OK, [
            'Content-Type' => 'application/xml',
        'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /**
     * Creates Credit Transfer data from array format.
     * Supports both camelCase and snake_case field names.
     *
     * @param array<string, mixed> $data The data in array format
     *
     * @throws \InvalidArgumentException If the data is invalid
     *
     * @return CreditTransferData The CreditTransferData object
     */
    private function createCreditTransferDataFromArray(array $data): CreditTransferData
    {
        // Normalize field names (support both camelCase and snake_case)
        $data = $this->normalizeArrayKeys($data);

        // Validate required fields
        $required = ['reference', 'initiatingPartyName', 'paymentInfoId', 'creditorIban', 'creditorName', 'requestedExecutionDate'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Parse dates
        $creationDate = $data['creationDate'] ?? new \DateTime();
        if (is_string($creationDate)) {
            $creationDate = new \DateTime($creationDate);
        } elseif (!$creationDate instanceof \DateTimeInterface) {
            throw new \InvalidArgumentException('creationDate must be a string or DateTimeInterface');
        }

        $requestedExecutionDate = $data['requestedExecutionDate'];
        if (is_string($requestedExecutionDate)) {
            $requestedExecutionDate = new \DateTime($requestedExecutionDate);
        } elseif (!$requestedExecutionDate instanceof \DateTimeInterface) {
            throw new \InvalidArgumentException('requestedExecutionDate must be a string or DateTimeInterface');
        }

        $creditTransferData = new CreditTransferData(
            $data['reference'],
            $creationDate,
            $data['initiatingPartyName'],
            $data['paymentInfoId'],
            $data['creditorIban'],
            $data['creditorName'],
            $requestedExecutionDate
        );

        if (isset($data['creditorBic'])) {
            $creditTransferData->setCreditorBic($data['creditorBic']);
        }

        if (isset($data['batchBooking'])) {
            $creditTransferData->setBatchBooking((bool) $data['batchBooking']);
        }

        // Set creditor address if provided (optional)
        if (isset($data['creditorAddress']) && is_array($data['creditorAddress']) && !empty($data['creditorAddress'])) {
            $creditTransferData->setCreditorAddressFromArray($data['creditorAddress']);
        } elseif (isset($data['creditor_street']) || isset($data['creditor_city']) || isset($data['creditor_postal_code']) || isset($data['creditor_country'])
                  || isset($data['creditorStreet']) || isset($data['creditorCity']) || isset($data['creditorPostalCode']) || isset($data['creditorCountry'])) {
            // Support individual address fields (only if at least one is provided)
            $creditTransferData->setCreditorAddress(
                $data['creditor_street'] ?? $data['creditorStreet'] ?? null,
                $data['creditor_city'] ?? $data['creditorCity'] ?? null,
                $data['creditor_postal_code'] ?? $data['creditorPostalCode'] ?? null,
                $data['creditor_country'] ?? $data['creditorCountry'] ?? null
            );
        }

        // Add transactions (after normalization, 'items' should already be 'transactions')
        if (isset($data['transactions']) && is_array($data['transactions'])) {
            foreach ($data['transactions'] as $transactionData) {
                // Normalize transaction array keys
                $transactionData = $this->normalizeTransactionArrayKeys($transactionData);
                $transaction = $this->createTransactionFromArray($transactionData);
                $creditTransferData->addTransaction($transaction);
            }
        }

        return $creditTransferData;
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
            'message_id' => 'reference',
            'initiating_party_name' => 'initiatingPartyName',
            'payment_name' => 'paymentInfoId',
            'payment_info_id' => 'paymentInfoId',
            'creation_date' => 'creationDate',
            'requested_execution_date' => 'requestedExecutionDate',
            'creditor_name' => 'creditorName',
            'creditor_iban' => 'creditorIban',
            'creditor_bic' => 'creditorBic',
            'batch_booking' => 'batchBooking',
            'items' => 'transactions',
            'creditor_address' => 'creditorAddress',
        ];

        $normalized = [];
        foreach ($data as $key => $value) {
            // If key exists in mapping, use mapped key, otherwise keep original
            $normalizedKey = $mapping[$key] ?? $key;
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
            'instruction_id' => 'endToEndId',
            'end_to_end_id' => 'endToEndId',
            'debtor_iban' => 'debtorIban',
            'debtor_name' => 'debtorName',
            'debtor_bic' => 'debtorBic',
            'information' => 'remittanceInformation',
            'remittance_information' => 'remittanceInformation',
            'debtor_address' => 'debtorAddress',
        ];

        $normalized = [];
        foreach ($data as $key => $value) {
            // If key exists in mapping, use mapped key, otherwise keep original
            $normalizedKey = $mapping[$key] ?? $key;
            $normalized[$normalizedKey] = $value;
        }

        return $normalized;
    }

    /**
     * Creates Transaction from array format.
     *
     * @param array<string, mixed> $transactionData The transaction data
     *
     * @throws \InvalidArgumentException If the data is invalid
     *
     * @return Transaction The Transaction object
     */
    private function createTransactionFromArray(array $transactionData): Transaction
    {
        $required = ['amount', 'debtorIban', 'debtorName', 'endToEndId'];
        foreach ($required as $field) {
            if (!isset($transactionData[$field])) {
                throw new \InvalidArgumentException("Missing required transaction field: {$field}");
            }
        }

        // Parse amount (convert from cents if needed, but assume it's already in currency units)
        $amount = (float) $transactionData['amount'];
        // If amount seems to be in cents (very large number), convert to currency units
        if ($amount > 10000) {
            $amount = $amount / 100;
        }

        // Currency defaults to EUR if not provided
        $currency = $transactionData['currency'] ?? 'EUR';

        $transaction = new Transaction(
            $transactionData['endToEndId'],
            $amount,
            $currency,
            $transactionData['debtorIban'],
            $transactionData['debtorName']
        );

        if (isset($transactionData['debtorBic'])) {
            $transaction->setDebtorBic($transactionData['debtorBic']);
        }

        if (isset($transactionData['remittanceInformation'])) {
            $transaction->setRemittanceInformation($transactionData['remittanceInformation']);
        }

        // Set debtor address if provided (optional)
        if (isset($transactionData['debtorAddress']) && is_array($transactionData['debtorAddress']) && !empty($transactionData['debtorAddress'])) {
            $transaction->setDebtorAddressFromArray($transactionData['debtorAddress']);
        } elseif (isset($transactionData['debtor_street']) || isset($transactionData['debtor_city']) || isset($transactionData['debtor_postal_code']) || isset($transactionData['debtor_country'])
                  || isset($transactionData['debtorStreet']) || isset($transactionData['debtorCity']) || isset($transactionData['debtorPostalCode']) || isset($transactionData['debtorCountry'])) {
            // Support individual address fields (only if at least one is provided)
            $transaction->setDebtorAddress(
                $transactionData['debtor_street'] ?? $transactionData['debtorStreet'] ?? null,
                $transactionData['debtor_city'] ?? $transactionData['debtorCity'] ?? null,
                $transactionData['debtor_postal_code'] ?? $transactionData['debtorPostalCode'] ?? null,
                $transactionData['debtor_country'] ?? $transactionData['debtorCountry'] ?? null
            );
        }

        return $transaction;
    }

    /**
     * Attempts to set postal address on transfer information (debtor address).
     * Note: The Digitick\Sepa library may not support this directly, so addresses
     * are also added via DOM manipulation in addAddressesToXml() method.
     *
     * @param CustomerCreditTransferInformation $transferInformation The transfer information object
     * @param array<string, string|null>        $address             Address array with keys: street, city, postalCode, country
     *
     * @return void
     */
    private function setPostalAddress(
        CustomerCreditTransferInformation $transferInformation,
        array $address
    ): void {
        // Try to set postal address using available methods from the library
        // If these methods don't exist, addresses will be added via DOM manipulation
        if (method_exists($transferInformation, 'setPostalAddress')) {
            /** @phpstan-ignore-next-line */
            $transferInformation->setPostalAddress(
                $address['street'] ?? '',
                $address['city'] ?? '',
                $address['postalCode'] ?? '',
                $address['country'] ?? ''
            );
        } elseif (method_exists($transferInformation, 'setDebtorPostalAddress')) {
            /** @phpstan-ignore-next-line */
            $transferInformation->setDebtorPostalAddress(
                $address['street'] ?? '',
                $address['city'] ?? '',
                $address['postalCode'] ?? '',
                $address['country'] ?? ''
            );
        } elseif (method_exists($transferInformation, 'setAddress')) {
            /** @phpstan-ignore-next-line */
            $transferInformation->setAddress(
                $address['street'] ?? '',
                $address['city'] ?? '',
                $address['postalCode'] ?? '',
                $address['country'] ?? ''
            );
        }
        // Note: Addresses are always added to XML via DOM manipulation in addAddressesToXml()
        // even if the library methods don't exist, ensuring addresses are included in the final XML
    }

    /**
     * Attempts to set creditor postal address on payment information.
     * Note: The Digitick\Sepa library may not support this directly, so addresses
     * are also added via DOM manipulation in addAddressesToXml() method.
     *
     * @param PaymentInformation         $paymentInformation The payment information object
     * @param array<string, string|null> $address            Address array with keys: street, city, postalCode, country
     *
     * @return void
     */
    private function setCreditorPostalAddress(
        PaymentInformation $paymentInformation,
        array $address
    ): void {
        // Try to set creditor postal address using available methods from the library
        // If these methods don't exist, addresses will be added via DOM manipulation
        if (method_exists($paymentInformation, 'setCreditorPostalAddress')) {
            /** @phpstan-ignore-next-line */
            $paymentInformation->setCreditorPostalAddress(
                $address['street'] ?? '',
                $address['city'] ?? '',
                $address['postalCode'] ?? '',
                $address['country'] ?? ''
            );
        } elseif (method_exists($paymentInformation, 'setPostalAddress')) {
            /** @phpstan-ignore-next-line */
            $paymentInformation->setPostalAddress(
                $address['street'] ?? '',
                $address['city'] ?? '',
                $address['postalCode'] ?? '',
                $address['country'] ?? ''
            );
        } elseif (method_exists($paymentInformation, 'setAddress')) {
            /** @phpstan-ignore-next-line */
            $paymentInformation->setAddress(
                $address['street'] ?? '',
                $address['city'] ?? '',
                $address['postalCode'] ?? '',
                $address['country'] ?? ''
            );
        }
        // Note: Addresses are always added to XML via DOM manipulation in addAddressesToXml()
        // even if the library methods don't exist, ensuring addresses are included in the final XML
    }

    /**
     * Adds addresses to the generated XML using DOM manipulation.
     * This method ensures addresses are included in the final XML even if the Digitick\Sepa
     * library doesn't support them directly through its API methods.
     *
     * @param string             $xml                The generated XML from the library
     * @param CreditTransferData $creditTransferData The credit transfer data containing creditor and debtor addresses
     *
     * @return string The XML with addresses added via DOM manipulation
     */
    private function addAddressesToXml(string $xml, CreditTransferData $creditTransferData): string
    {
        try {
            $dom = new \DOMDocument();
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;

            if (!@$dom->loadXML($xml)) {
                // If XML is invalid, return original
                return $xml;
            }

            $xpath = new \DOMXPath($dom);
            // Detect namespace from root element
            $root = $dom->documentElement;
            $namespace = $root->namespaceURI ?? 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03';
            $xpath->registerNamespace('ns', $namespace);

            // Add creditor address if available
            $creditorAddress = $creditTransferData->getCreditorAddress();
            if (null !== $creditorAddress) {
                $this->addCreditorAddressToDom($dom, $xpath, $creditorAddress, $namespace);
            }

            // Add debtor addresses for each transaction
            $transactions = $creditTransferData->getTransactions();
            foreach ($transactions as $index => $transaction) {
                $debtorAddress = $transaction->getDebtorAddress();
                if (null !== $debtorAddress) {
                    $this->addDebtorAddressToDom($dom, $xpath, $debtorAddress, $index, $namespace);
                }
            }

            return $dom->saveXML();
        } catch (\Exception $e) {
            // If DOM manipulation fails, return original XML
            return $xml;
        }
    }

    /**
     * Adds creditor address to DOM.
     *
     * @param \DOMDocument $dom       The DOM document
     * @param \DOMXPath    $xpath     The XPath object
     * @param array        $address   The address array
     * @param string       $namespace The namespace URI
     *
     * @return void
     */
    private function addCreditorAddressToDom(\DOMDocument $dom, \DOMXPath $xpath, array $address, string $namespace): void
    {
        // Find Cdtr (Creditor) element
        $creditorNodes = $xpath->query('//ns:Cdtr');
        if ($creditorNodes === false || $creditorNodes->length === 0) {
            // Try without namespace prefix
            $creditorNodes = $xpath->query('//Cdtr');
            if ($creditorNodes === false || $creditorNodes->length === 0) {
                return;
            }
        }

        $creditorNode = $creditorNodes->item(0);
        $this->createPostalAddressElement($dom, $creditorNode, $address, $namespace);
    }

    /**
     * Adds debtor address to DOM.
     *
     * @param \DOMDocument $dom       The DOM document
     * @param \DOMXPath    $xpath     The XPath object
     * @param array        $address   The address array
     * @param int          $index     Transaction index
     * @param string       $namespace The namespace URI
     *
     * @return void
     */
    private function addDebtorAddressToDom(\DOMDocument $dom, \DOMXPath $xpath, array $address, int $index, string $namespace): void
    {
        // Find Dbtr (Debtor) elements
        $debtorNodes = $xpath->query('//ns:Dbtr');
        if ($debtorNodes === false || $debtorNodes->length === 0) {
            // Try without namespace prefix
            $debtorNodes = $xpath->query('//Dbtr');
            if ($debtorNodes === false || $debtorNodes->length <= $index) {
                return;
            }
        }

        if ($debtorNodes->length <= $index) {
            return;
        }

        $debtorNode = $debtorNodes->item($index);
        $this->createPostalAddressElement($dom, $debtorNode, $address, $namespace);
    }

    /**
     * Creates a PstlAdr (Postal Address) element in the DOM.
     * Only creates the element if at least one address field is provided.
     *
     * @param \DOMDocument $dom        The DOM document
     * @param \DOMElement  $parentNode The parent node
     * @param array        $address    The address array
     * @param string       $namespace  The namespace URI
     *
     * @return void
     */
    private function createPostalAddressElement(\DOMDocument $dom, \DOMElement $parentNode, array $address, string $namespace): void
    {
        // Check if at least one address field is provided
        $hasAddress = !empty($address['street'])
            || !empty($address['city'])
            || !empty($address['postalCode'])
            || !empty($address['country']);

        if (!$hasAddress) {
            // Don't create empty address element
            return;
        }

        // Check if PstlAdr already exists
        $existing = $parentNode->getElementsByTagNameNS($namespace, 'PstlAdr');
        if ($existing->length > 0) {
            // Remove existing address
            $parentNode->removeChild($existing->item(0));
        }

        $pstlAdr = $dom->createElementNS($namespace, 'PstlAdr');

        // Add structured address elements only if they are not empty
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

        // Only add PstlAdr if it has at least one child element
        if ($pstlAdr->childNodes->length > 0) {
            // Insert after Nm (Name) element if it exists, otherwise append
            $nmNodes = $parentNode->getElementsByTagNameNS($namespace, 'Nm');
            if ($nmNodes->length > 0) {
                $nextSibling = $nmNodes->item(0)->nextSibling;
                if ($nextSibling) {
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
     * Validates credit transfer data.
     *
     * @param CreditTransferData $creditTransferData The credit transfer data to validate
     *
     * @throws \InvalidArgumentException If the data is invalid
     *
     * @return void
     */
    private function validateCreditTransferData(CreditTransferData $creditTransferData): void
    {
        if (!$this->ibanValidator->isValid($creditTransferData->getCreditorIban())) {
            throw new \InvalidArgumentException('Invalid creditor IBAN: ' . $creditTransferData->getCreditorIban());
        }

        foreach ($creditTransferData->getTransactions() as $transaction) {
            if (!$this->ibanValidator->isValid($transaction->getDebtorIban())) {
                throw new \InvalidArgumentException('Invalid debtor IBAN: ' . $transaction->getDebtorIban());
            }
        }
    }
}
