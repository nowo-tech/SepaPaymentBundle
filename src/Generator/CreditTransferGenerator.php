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
 * Used for payment remittances where the debtor (company) sends money to creditors (suppliers/beneficiaries).
 *
 * Note: In this implementation, CreditTransferData.creditor* fields represent the debtor (company that pays),
 * and Transaction.creditor* fields represent each creditor (supplier/beneficiary that receives).
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

            // Auto-fill debtor BIC if missing (PaymentInformation uses debtor data - the company that pays)
            // Note: CreditTransferData.creditor* fields represent the debtor (company that pays) in this context
            $debtorBic = $creditTransferData->getCreditorBic();
            if (null === $debtorBic && null !== $this->bicLookupService) {
                $lookedUpBic = $this->bicLookupService->lookupBic($creditTransferData->getCreditorIban());
                if (null !== $lookedUpBic) {
                    $debtorBic = $lookedUpBic;
                }
            }

            // Create payment information
            // PaymentInformation must contain debtor data (who pays) - using creditor* fields from CreditTransferData
            $paymentInformation = new PaymentInformation(
                $creditTransferData->getPaymentInfoId(),
                $this->ibanValidator->normalize($creditTransferData->getCreditorIban()),
                $debtorBic ?? '',
                $creditTransferData->getCreditorName(),
                'EUR'
            );
            // Payment method is automatically set based on the transfer file type (CustomerCreditTransferFile)
            $paymentInformation->setBatchBooking($creditTransferData->isBatchBooking());
            $paymentInformation->setDueDate($creditTransferData->getRequestedExecutionDate());

            // Set debtor address if available (PaymentInformation uses debtor address)
            $debtorAddress = $creditTransferData->getCreditorAddress();
            if (null !== $debtorAddress) {
                $this->setDebtorPostalAddress($paymentInformation, $debtorAddress);
            }

            // Add transactions
            foreach ($creditTransferData->getTransactions() as $transaction) {
                // CustomerCreditTransferInformation contains creditor data (who receives)
                $transferInformation = new CustomerCreditTransferInformation(
                    (int) round($transaction->getAmount() * 100), // Convert to cents
                    $this->ibanValidator->normalize($transaction->getCreditorIban()),
                    $transaction->getCreditorName(),
                    $transaction->getEndToEndId()
                );

                // Auto-fill creditor BIC if missing
                $creditorBic = $transaction->getCreditorBic();
                if (null === $creditorBic && null !== $this->bicLookupService) {
                    $lookedUpBic = $this->bicLookupService->lookupBic($transaction->getCreditorIban());
                    if (null !== $lookedUpBic) {
                        $creditorBic = $lookedUpBic;
                    }
                }

                if (null !== $creditorBic) {
                    $transferInformation->setBic($creditorBic);
                }

                if (null !== $transaction->getRemittanceInformation()) {
                    $transferInformation->setRemittanceInformation($transaction->getRemittanceInformation());
                }

                // Set creditor address if available
                $creditorAddress = $transaction->getCreditorAddress();
                if (null !== $creditorAddress) {
                    $this->setPostalAddress($transferInformation, $creditorAddress);
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
        $required = ['reference', 'initiatingPartyName', 'paymentInfoId', 'requestedExecutionDate'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }
        // Check for debtorIban and debtorName (normalized from debtor* keys)
        if (!isset($data['debtorIban'])) {
            throw new \InvalidArgumentException('Missing required field: debtorIban');
        }
        if (!isset($data['debtorName'])) {
            throw new \InvalidArgumentException('Missing required field: debtorName');
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

        // CreditTransferData constructor expects creditor* parameters (even though they represent the debtor)
        $creditTransferData = new CreditTransferData(
            $data['reference'],
            $creationDate,
            $data['initiatingPartyName'],
            $data['paymentInfoId'],
            $data['debtorIban'], // Mapped from debtorIban - CreditTransferData uses creditor* internally but represents debtor
            $data['debtorName'], // Mapped from debtorName - CreditTransferData uses creditor* internally but represents debtor
            $requestedExecutionDate
        );

        if (isset($data['debtorBic'])) {
            $creditTransferData->setCreditorBic($data['debtorBic']);
        }

        if (isset($data['batchBooking'])) {
            $creditTransferData->setBatchBooking((bool) $data['batchBooking']);
        }

        // Set debtor address if provided (optional)
        // Note: CreditTransferData uses setCreditorAddress internally but represents debtor address
        if (isset($data['debtorAddress']) && is_array($data['debtorAddress']) && !empty($data['debtorAddress'])) {
            $creditTransferData->setCreditorAddressFromArray($data['debtorAddress']);
        } elseif (isset($data['debtor_street']) || isset($data['debtor_city']) || isset($data['debtor_postal_code']) || isset($data['debtor_country'])
                  || isset($data['debtorStreet']) || isset($data['debtorCity']) || isset($data['debtorPostalCode']) || isset($data['debtorCountry'])) {
            // Support individual address fields (only if at least one is provided)
            $creditTransferData->setCreditorAddress(
                $data['debtor_street'] ?? $data['debtorStreet'] ?? null,
                $data['debtor_city'] ?? $data['debtorCity'] ?? null,
                $data['debtor_postal_code'] ?? $data['debtorPostalCode'] ?? null,
                $data['debtor_country'] ?? $data['debtorCountry'] ?? null
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
        // Validate for incorrect keys (creditor* should not be used at top level)
        $incorrectCreditorKeys = [];
        $creditorKeysPattern = ['creditor_iban', 'creditor_name', 'creditor_bic', 'creditor_address', 'creditorIban', 'creditorName', 'creditorBic', 'creditorAddress'];
        foreach ($creditorKeysPattern as $key) {
            if (isset($data[$key])) {
                $incorrectCreditorKeys[] = $key;
            }
        }

        if (!empty($incorrectCreditorKeys)) {
            $suggestions = [];
            foreach ($incorrectCreditorKeys as $key) {
                if (strpos($key, 'creditor_') === 0) {
                    $suggestions[] = str_replace('creditor_', 'debtor_', $key);
                } elseif (strpos($key, 'creditor') === 0 && ctype_upper($key[8] ?? '')) {
                    $suggestions[] = 'debtor' . substr($key, 8);
                } else {
                    $suggestions[] = str_replace('creditor', 'debtor', $key);
                }
            }

            throw new \InvalidArgumentException(
                'Invalid key(s) at top level: ' . implode(', ', $incorrectCreditorKeys) . '. ' .
                'At the top level (payment information), you must use "debtor*" keys (e.g., debtorIban, debtorName, debtorBic) ' .
                'to represent the company that pays. ' .
                'Suggested keys: ' . implode(', ', $suggestions) . '. ' .
                'Note: "creditor*" keys should only be used within the "transactions" array (for beneficiaries that receive payments).'
            );
        }

        $mapping = [
            'message_id' => 'reference',
            'initiating_party_name' => 'initiatingPartyName',
            'payment_name' => 'paymentInfoId',
            'payment_info_id' => 'paymentInfoId',
            'creation_date' => 'creationDate',
            'requested_execution_date' => 'requestedExecutionDate',
            // Support debtor* keys (conceptually correct - represents who pays)
            'debtor_name' => 'debtorName',
            'debtor_iban' => 'debtorIban',
            'debtor_bic' => 'debtorBic',
            'debtor_address' => 'debtorAddress',
            'batch_booking' => 'batchBooking',
            'items' => 'transactions',
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
        // Validate for incorrect keys (debtor* should not be used in transactions)
        $incorrectDebtorKeys = [];
        $debtorKeysPattern = ['debtor_iban', 'debtor_name', 'debtor_bic', 'debtor_address', 'debtorIban', 'debtorName', 'debtorBic', 'debtorAddress'];
        foreach ($debtorKeysPattern as $key) {
            if (isset($data[$key])) {
                $incorrectDebtorKeys[] = $key;
            }
        }

        if (!empty($incorrectDebtorKeys)) {
            $suggestions = [];
            foreach ($incorrectDebtorKeys as $key) {
                if (strpos($key, 'debtor_') === 0) {
                    $suggestions[] = str_replace('debtor_', 'creditor_', $key);
                } elseif (strpos($key, 'debtor') === 0 && ctype_upper($key[6] ?? '')) {
                    $suggestions[] = 'creditor' . substr($key, 6);
                } else {
                    $suggestions[] = str_replace('debtor', 'creditor', $key);
                }
            }

            throw new \InvalidArgumentException(
                'Invalid key(s) in transaction: ' . implode(', ', $incorrectDebtorKeys) . '. ' .
                'Within transactions array, you must use "creditor*" keys (e.g., creditorIban, creditorName, creditorBic) ' .
                'to represent the beneficiary that receives the payment. ' .
                'Suggested keys: ' . implode(', ', $suggestions) . '. ' .
                'Note: "debtor*" keys should only be used at the top level (for the company that pays).'
            );
        }

        $mapping = [
            'instruction_id' => 'endToEndId',
            'end_to_end_id' => 'endToEndId',
            'creditor_iban' => 'creditorIban',
            'creditor_name' => 'creditorName',
            'creditor_bic' => 'creditorBic',
            'creditor_address' => 'creditorAddress',
            'information' => 'remittanceInformation',
            'remittance_information' => 'remittanceInformation',
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
        $endToEndId = $transactionData['endToEndId'] ?? null;

        $required = ['amount', 'endToEndId', 'creditorIban', 'creditorName'];
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
            $transactionData['creditorIban'],
            $transactionData['creditorName']
        );

        if (isset($transactionData['creditorBic'])) {
            $transaction->setCreditorBic($transactionData['creditorBic']);
        }

        if (isset($transactionData['remittanceInformation'])) {
            $transaction->setRemittanceInformation($transactionData['remittanceInformation']);
        }

        // Set creditor address if provided (optional)
        if (isset($transactionData['creditorAddress']) && is_array($transactionData['creditorAddress']) && !empty($transactionData['creditorAddress'])) {
            $transaction->setCreditorAddressFromArray($transactionData['creditorAddress']);
        } elseif (isset($transactionData['creditor_street']) || isset($transactionData['creditor_city']) || isset($transactionData['creditor_postal_code']) || isset($transactionData['creditor_country'])
                  || isset($transactionData['creditorStreet']) || isset($transactionData['creditorCity']) || isset($transactionData['creditorPostalCode']) || isset($transactionData['creditorCountry'])) {
            // Support individual address fields (only if at least one is provided)
            $transaction->setCreditorAddress(
                $transactionData['creditor_street'] ?? $transactionData['creditorStreet'] ?? null,
                $transactionData['creditor_city'] ?? $transactionData['creditorCity'] ?? null,
                $transactionData['creditor_postal_code'] ?? $transactionData['creditorPostalCode'] ?? null,
                $transactionData['creditor_country'] ?? $transactionData['creditorCountry'] ?? null
            );
        }

        return $transaction;
    }

    /**
     * Attempts to set postal address on transfer information (creditor address).
     * Note: CustomerCreditTransferInformation contains creditor data (who receives).
     * The Digitick\Sepa library may not support this directly, so addresses
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
        // Try to set creditor postal address using available methods from the library
        // If these methods don't exist, addresses will be added via DOM manipulation
        if (method_exists($transferInformation, 'setCreditorPostalAddress')) {
            /** @phpstan-ignore-next-line */
            $transferInformation->setCreditorPostalAddress(
                $address['street'] ?? '',
                $address['city'] ?? '',
                $address['postalCode'] ?? '',
                $address['country'] ?? ''
            );
        } elseif (method_exists($transferInformation, 'setPostalAddress')) {
            /** @phpstan-ignore-next-line */
            $transferInformation->setPostalAddress(
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
     * Attempts to set debtor postal address on payment information.
     * Note: PaymentInformation contains debtor data (who pays).
     * The Digitick\Sepa library may not support this directly, so addresses
     * are also added via DOM manipulation in addAddressesToXml() method.
     *
     * @param PaymentInformation         $paymentInformation The payment information object
     * @param array<string, string|null> $address            Address array with keys: street, city, postalCode, country
     *
     * @return void
     */
    private function setDebtorPostalAddress(
        PaymentInformation $paymentInformation,
        array $address
    ): void {
        // Try to set debtor postal address using available methods from the library
        // Note: The library may use setDebtorPostalAddress or setPostalAddress
        // If these methods don't exist, addresses will be added via DOM manipulation
        if (method_exists($paymentInformation, 'setDebtorPostalAddress')) {
            /** @phpstan-ignore-next-line */
            $paymentInformation->setDebtorPostalAddress(
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
     * Note: CreditTransferData.creditor* fields represent the debtor (company that pays),
     * and Transaction.debtor* fields represent each creditor (supplier/beneficiary that receives).
     *
     * @param string             $xml                The generated XML from the library
     * @param CreditTransferData $creditTransferData The credit transfer data containing addresses
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

            // Add debtor address if available (PaymentInformation contains debtor data)
            // CreditTransferData.creditor* fields represent the debtor (company that pays)
            $debtorAddress = $creditTransferData->getCreditorAddress();
            if (null !== $debtorAddress) {
                $this->addDebtorAddressToDom($dom, $xpath, $debtorAddress, 0, $namespace);
            }

            // Add creditor addresses for each transaction (each Transaction contains creditor data)
            $transactions = $creditTransferData->getTransactions();
            foreach ($transactions as $index => $transaction) {
                $creditorAddress = $transaction->getCreditorAddress();
                if (null !== $creditorAddress) {
                    $this->addCreditorAddressToDom($dom, $xpath, $creditorAddress, $index, $namespace);
                }
            }

            return $dom->saveXML();
        } catch (\Exception $e) {
            // If DOM manipulation fails, return original XML
            return $xml;
        }
    }

    /**
     * Adds creditor address to DOM for a specific transaction.
     *
     * @param \DOMDocument $dom       The DOM document
     * @param \DOMXPath    $xpath     The XPath object
     * @param array        $address   The address array
     * @param int          $index     Transaction index
     * @param string       $namespace The namespace URI
     *
     * @return void
     */
    private function addCreditorAddressToDom(\DOMDocument $dom, \DOMXPath $xpath, array $address, int $index, string $namespace): void
    {
        // Find all CdtTrfTxInf (Transaction) elements
        $transactionNodes = $xpath->query('//ns:CdtTrfTxInf');
        if ($transactionNodes === false || $transactionNodes->length === 0) {
            // Try without namespace prefix
            $transactionNodes = $xpath->query('//CdtTrfTxInf');
            if ($transactionNodes === false || $transactionNodes->length <= $index) {
                return;
            }
        }

        if ($transactionNodes->length <= $index) {
            return;
        }

        // Find Cdtr (Creditor) element within the specific transaction
        $transactionNode = $transactionNodes->item($index);
        $creditorNodes = $xpath->query('.//ns:Cdtr', $transactionNode);
        if ($creditorNodes === false || $creditorNodes->length === 0) {
            // Try without namespace prefix
            $creditorNodes = $xpath->query('.//Cdtr', $transactionNode);
            if ($creditorNodes === false || $creditorNodes->length === 0) {
                return;
            }
        }

        $creditorNode = $creditorNodes->item(0);
        $this->createPostalAddressElement($dom, $creditorNode, $address, $namespace);
    }

    /**
     * Adds debtor address to DOM for PaymentInformation.
     *
     * @param \DOMDocument $dom       The DOM document
     * @param \DOMXPath    $xpath     The XPath object
     * @param array        $address   The address array
     * @param int          $index     Not used (kept for signature compatibility, PaymentInformation has only one Dbtr)
     * @param string       $namespace The namespace URI
     *
     * @return void
     */
    private function addDebtorAddressToDom(\DOMDocument $dom, \DOMXPath $xpath, array $address, int $index, string $namespace): void
    {
        // Find PaymentInformation (PmtInf) element first
        $pmtInfNodes = $xpath->query('//ns:PmtInf');
        if ($pmtInfNodes === false || $pmtInfNodes->length === 0) {
            // Try without namespace prefix
            $pmtInfNodes = $xpath->query('//PmtInf');
            if ($pmtInfNodes === false || $pmtInfNodes->length === 0) {
                return;
            }
        }

        // Find Dbtr (Debtor) element within PaymentInformation
        $pmtInfNode = $pmtInfNodes->item(0);
        $debtorNodes = $xpath->query('.//ns:Dbtr', $pmtInfNode);
        if ($debtorNodes === false || $debtorNodes->length === 0) {
            // Try without namespace prefix
            $debtorNodes = $xpath->query('.//Dbtr', $pmtInfNode);
            if ($debtorNodes === false || $debtorNodes->length === 0) {
                return;
            }
        }

        $debtorNode = $debtorNodes->item(0);
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
            if (!$this->ibanValidator->isValid($transaction->getCreditorIban())) {
                throw new \InvalidArgumentException('Invalid creditor IBAN: ' . $transaction->getCreditorIban());
            }
        }
    }
}
