<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Generator;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Digitick\Sepa\DomBuilder\DomBuilderFactory;
use Digitick\Sepa\GroupHeader;
use Digitick\Sepa\PaymentInformation;
use Digitick\Sepa\TransferFile\CustomerCreditTransferFile;
use Digitick\Sepa\TransferInformation\CustomerCreditTransferInformation;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Exception;
use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Event\AfterCreditTransferGenerationEvent;
use Nowo\SepaPaymentBundle\Event\BeforeCreditTransferGenerationEvent;
use Nowo\SepaPaymentBundle\Logger\SepaPaymentLogger;
use Nowo\SepaPaymentBundle\Lookup\BicLookupServiceInterface;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\CreditTransferData;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\Transaction;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Nowo\SepaPaymentBundle\Validator\SepaBusinessRulesValidator;
use Nowo\SepaPaymentBundle\Validator\SepaStringSanitizer;
use Nowo\SepaPaymentBundle\Validator\XsdValidator;
use Nowo\SepaPaymentBundle\Validator\XsdValidatorInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

use function count;
use function is_array;
use function is_float;
use function is_int;
use function is_string;
use function strlen;

use const ENT_XML1;

/**
 * SEPA Credit Transfer generator.
 * Generates SEPA Credit Transfer XML files using Digitick\Sepa library according to ISO 20022 standard.
 * Used for payment remittances where the debtor sends money to the creditor.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
final class CreditTransferGenerator
{
    public const SERVICE_NAME = 'nowo_sepa_payment.generator.credit_transfer_generator';

    /**
     * Whether to validate generated XML against XSD schema.
     */
    private bool $validateXsd;

    /**
     * Sanitizer used to enforce SEPA field length limits.
     */
    private readonly SepaStringSanitizer $stringSanitizer;

    /**
     * Constructor.
     *
     * @param IbanValidator $ibanValidator IBAN validator instance
     * @param TranslatorInterface $translator Translator for internationalized error messages
     * @param XsdValidator|null $xsdValidator Optional XSD validator instance
     * @param bool $validateXsd Whether to enable XSD validation (default: false)
     * @param EventDispatcherInterface|null $eventDispatcher Optional event dispatcher for Symfony events
     * @param SepaPaymentLogger|null $logger Optional logger for structured logging
     * @param BicLookupServiceInterface|null $bicLookupService Optional BIC lookup service for auto-filling BIC
     * @param string $defaultCurrency Currency used when a transaction has none (bundle config `default_currency`; SEPA requires EUR)
     * @param SepaStringSanitizer|null $stringSanitizer Sanitizer used to enforce SEPA field length limits
     */
    public function __construct(
        private readonly IbanValidator $ibanValidator,
        /**
         * Translator instance.
         */
        private readonly TranslatorInterface $translator,
        /**
         * XSD validator instance (optional).
         */
        private readonly ?XsdValidatorInterface $xsdValidator = null,
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
        private readonly ?BicLookupServiceInterface $bicLookupService = null,
        /**
         * Default currency (ISO 4217) for transactions without an explicit currency.
         */
        private readonly string $defaultCurrency = SepaBusinessRulesValidator::SEPA_CURRENCY,
        ?SepaStringSanitizer $stringSanitizer = null,
        /**
         * Source of the default creation date (REQ-DI-001).
         */
        private readonly ClockInterface $clock = new NativeClock(),
    ) {
        $this->validateXsd     = $validateXsd && $this->xsdValidator instanceof XsdValidatorInterface;
        $this->stringSanitizer = $stringSanitizer ?? new SepaStringSanitizer();
    }

    /**
     * Generates a SEPA Credit Transfer XML file from array data.
     *
     * @param array<string, mixed> $data The credit transfer data in array format
     *
     * @throws InvalidArgumentException If the data is invalid
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
     * @throws InvalidArgumentException If the data is invalid
     *
     * @return string The XML content
     */
    public function generate(CreditTransferData $creditTransferData): string
    {
        $transactionCount = count($creditTransferData->getTransactions());
        $messageId        = $creditTransferData->getMessageId();

        // Log generation start
        if ($this->logger instanceof SepaPaymentLogger) {
            $this->logger->logCreditTransferGenerationStart($messageId, $transactionCount);
        }

        try {
            // Dispatch before generation event
            if ($this->eventDispatcher instanceof EventDispatcherInterface) {
                $beforeEvent = new BeforeCreditTransferGenerationEvent($creditTransferData);
                $this->eventDispatcher->dispatch($beforeEvent);
                $creditTransferData = $beforeEvent->getCreditTransferData();
            }

            $this->validateCreditTransferData($creditTransferData);

            // Create and configure group header
            $groupHeader = new GroupHeader(
                $creditTransferData->getMessageId(),
                $creditTransferData->getInitiatingPartyName(),
            );
            $this->setGroupHeaderCreationDate($groupHeader, $creditTransferData->getCreationDate());

            // Create transfer file with group header (digitick/sepa-xml 3.x writes pain.001.001.09)
            $transferFile = new CustomerCreditTransferFile($groupHeader);

            // Auto-fill creditor BIC if missing
            $creditorBic = $creditTransferData->getCreditorBic();
            if ($creditorBic === null && $this->bicLookupService instanceof BicLookupServiceInterface) {
                $lookedUpBic = $this->bicLookupService->lookupBic($creditTransferData->getCreditorIban());
                if ($lookedUpBic !== null) {
                    $creditorBic = $lookedUpBic;
                }
            }

            // Create payment information
            $paymentInformation = new PaymentInformation(
                $creditTransferData->getPaymentInfoId(),
                $this->ibanValidator->normalize($creditTransferData->getCreditorIban()),
                $creditorBic ?? '',
                $creditTransferData->getCreditorName(),
                $this->defaultCurrency,
            );
            // Payment method is automatically set based on the transfer file type (CustomerCreditTransferFile)
            $paymentInformation->setBatchBooking($creditTransferData->isBatchBooking());
            $paymentInformation->setDueDate($creditTransferData->getRequestedExecutionDate());

            // Set creditor address if available
            $creditorAddress = $creditTransferData->getCreditorAddress();
            if ($creditorAddress !== null) {
                $this->setCreditorPostalAddress($paymentInformation, $creditorAddress);
            }

            // Add transactions
            foreach ($creditTransferData->getTransactions() as $transaction) {
                $transferInformation = new CustomerCreditTransferInformation(
                    (int) round($transaction->getAmount() * 100), // Convert to cents
                    $this->ibanValidator->normalize($transaction->getCreditorIban()),
                    $transaction->getCreditorName(),
                    $transaction->getEndToEndId(),
                );
                $transferInformation->setCurrency($transaction->getCurrency());

                // Auto-fill creditor BIC if missing
                $creditorBic = $transaction->getCreditorBic();
                if ($creditorBic === null && $this->bicLookupService instanceof BicLookupServiceInterface) {
                    $lookedUpBic = $this->bicLookupService->lookupBic($transaction->getCreditorIban());
                    if ($lookedUpBic !== null) {
                        $creditorBic = $lookedUpBic;
                    }
                }

                if ($creditorBic !== null) {
                    $transferInformation->setBic($creditorBic);
                }

                if ($transaction->getRemittanceInformation() !== null) {
                    $transferInformation->setRemittanceInformation($transaction->getRemittanceInformation());
                }

                // Set creditor address if available
                $creditorAddress = $transaction->getCreditorAddress();
                if ($creditorAddress !== null) {
                    $this->setPostalAddress($transferInformation, $creditorAddress);
                }

                $paymentInformation->addTransfer($transferInformation);
            }

            $transferFile->addPaymentInformation($paymentInformation);

            // Generate XML
            $domBuilder = DomBuilderFactory::createDomBuilder($transferFile);
            $xml        = $domBuilder->asXml();

            // Add addresses to XML if they were provided
            $xml = $this->addAddressesToXml($xml, $creditTransferData);

            // Validate against XSD schema if enabled
            if ($this->validateXsd && $this->xsdValidator instanceof XsdValidatorInterface) {
                try {
                    $this->xsdValidator->validateCreditTransfer($xml);
                } catch (InvalidArgumentException $e) {
                    $message = $this->translator->trans('validation.generated_xml_failed_xsd', ['%error%' => $e->getMessage()], 'NowoSepaPaymentBundle');

                    throw new InvalidArgumentException($message, 0, $e);
                }
            }

            // Dispatch after generation event
            if ($this->eventDispatcher instanceof EventDispatcherInterface) {
                $afterEvent = new AfterCreditTransferGenerationEvent($xml, $creditTransferData->getMessageId());
                $this->eventDispatcher->dispatch($afterEvent);
                $xml = $afterEvent->getXml();
            }

            // Log generation success
            if ($this->logger instanceof SepaPaymentLogger) {
                $this->logger->logCreditTransferGenerationSuccess($messageId, $transactionCount, strlen($xml));
            }

            return $xml;
        } catch (Exception $e) {
            // Log generation failure
            if ($this->logger instanceof SepaPaymentLogger) {
                $this->logger->logCreditTransferGenerationFailure($messageId, $e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Creates an HTTP Response with XML content for download.
     *
     * @param string $xmlData The XML content
     * @param string $filename The filename for the download (e.g., "credit-transfer.xml")
     *
     * @return Response The HTTP response with XML content
     */
    public function createResponse(string $xmlData, string $filename): Response
    {
        return new Response($xmlData, Response::HTTP_OK, [
            'Content-Type'        => 'application/xml',
            'Content-Disposition' => $this->makeAttachmentDisposition($filename),
        ]);
    }

    /**
     * Creates Credit Transfer data from array format.
     * Supports both camelCase and snake_case field names.
     *
     * @param array<string, mixed> $data The data in array format
     *
     * @throws InvalidArgumentException If the data is invalid
     *
     * @return CreditTransferData The CreditTransferData object
     */
    private function createCreditTransferDataFromArray(array $data): CreditTransferData
    {
        // Normalize field names (support both camelCase and snake_case)
        $data = $this->normalizeArrayKeys($data);

        // Validate required fields
        $required = ['reference', 'initiatingPartyName', 'paymentInfoId', 'debtorIban', 'debtorName', 'requestedExecutionDate'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                $message = $this->translator->trans('validation.missing_required_field', ['%field%' => $field], 'NowoSepaPaymentBundle');

                throw new InvalidArgumentException($message);
            }
        }

        // Parse dates
        $creationDate = $data['creationDate'] ?? DateTime::createFromInterface($this->clock->now());
        if (is_string($creationDate)) {
            $creationDate = new DateTime($creationDate);
        } elseif (!$creationDate instanceof DateTimeInterface) {
            $message = $this->translator->trans('validation.invalid_creation_date', [], 'NowoSepaPaymentBundle');

            throw new InvalidArgumentException($message);
        }

        $requestedExecutionDate = $data['requestedExecutionDate'];
        if (is_string($requestedExecutionDate)) {
            $requestedExecutionDate = new DateTime($requestedExecutionDate);
        } elseif (!$requestedExecutionDate instanceof DateTimeInterface) {
            $message = $this->translator->trans('validation.invalid_execution_date', [], 'NowoSepaPaymentBundle');

            throw new InvalidArgumentException($message);
        }

        $creditTransferData = new CreditTransferData(
            $data['reference'],
            $creationDate,
            $data['initiatingPartyName'],
            $data['paymentInfoId'],
            $data['debtorIban'],
            $data['debtorName'],
            $requestedExecutionDate,
        );

        if (isset($data['debtorBic'])) {
            $creditTransferData->setCreditorBic($data['debtorBic']);
        }

        if (isset($data['batchBooking'])) {
            $creditTransferData->setBatchBooking((bool) $data['batchBooking']);
        }

        // Set creditor address if provided (optional)
        if (isset($data['debtorAddress']) && is_array($data['debtorAddress']) && (isset($data['debtorAddress']) && $data['debtorAddress'] !== [])) {
            $creditTransferData->setCreditorAddressFromArray($data['debtorAddress']);
        } elseif (isset($data['debtor_street']) || isset($data['debtor_city']) || isset($data['debtor_postal_code']) || isset($data['debtor_country'])
                  || isset($data['debtorStreet']) || isset($data['debtorCity']) || isset($data['debtorPostalCode']) || isset($data['debtorCountry'])) {
            // Support individual address fields (only if at least one is provided)
            $creditTransferData->setCreditorAddress(
                $data['debtor_street'] ?? $data['debtorStreet'] ?? null,
                $data['debtor_city'] ?? $data['debtorCity'] ?? null,
                $data['debtor_postal_code'] ?? $data['debtorPostalCode'] ?? null,
                $data['debtor_country'] ?? $data['debtorCountry'] ?? null,
            );
        }

        // Add transactions (after normalization, 'items' should already be 'transactions')
        if (isset($data['transactions']) && is_array($data['transactions'])) {
            foreach ($data['transactions'] as $transactionData) {
                // Normalize transaction array keys
                $transactionData = $this->normalizeTransactionArrayKeys($transactionData);
                $transaction     = $this->createTransactionFromArray($transactionData);
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
            'message_id'               => 'reference',
            'initiating_party_name'    => 'initiatingPartyName',
            'payment_name'             => 'paymentInfoId',
            'payment_info_id'          => 'paymentInfoId',
            'creation_date'            => 'creationDate',
            'requested_execution_date' => 'requestedExecutionDate',
            'debtor_name'              => 'debtorName',
            'debtor_iban'              => 'debtorIban',
            'debtor_bic'               => 'debtorBic',
            'batch_booking'            => 'batchBooking',
            'items'                    => 'transactions',
            'debtor_address'           => 'debtorAddress',
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
            'instruction_id'         => 'endToEndId',
            'end_to_end_id'          => 'endToEndId',
            'creditor_iban'          => 'creditorIban',
            'creditor_name'          => 'creditorName',
            'creditor_bic'           => 'creditorBic',
            'information'            => 'remittanceInformation',
            'remittance_information' => 'remittanceInformation',
            'creditor_address'       => 'creditorAddress',
            'amount_cents'           => 'amountCents',
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
     * Creates Transaction from array format.
     *
     * @param array<string, mixed> $transactionData The transaction data
     *
     * @throws InvalidArgumentException If the data is invalid
     *
     * @return Transaction The Transaction object
     */
    private function createTransactionFromArray(array $transactionData): Transaction
    {
        $required = ['creditorIban', 'creditorName', 'endToEndId'];
        foreach ($required as $field) {
            if (!isset($transactionData[$field])) {
                $message = $this->translator->trans('validation.missing_required_transaction_field', ['%field%' => $field], 'NowoSepaPaymentBundle');

                throw new InvalidArgumentException($message);
            }
        }

        // "amount" is always in euros (decimal); "amountCents" is the explicit integer alternative
        $amount = $this->parseAmount($transactionData);

        // Currency defaults to the configured default currency (validated before generation)
        $currency = (string) ($transactionData['currency'] ?? $this->defaultCurrency);

        $transaction = new Transaction(
            $transactionData['endToEndId'],
            $amount,
            $currency,
            $transactionData['creditorIban'],
            $transactionData['creditorName'],
        );

        if (isset($transactionData['creditorBic'])) {
            $transaction->setCreditorBic($transactionData['creditorBic']);
        }

        if (isset($transactionData['remittanceInformation'])) {
            $transaction->setRemittanceInformation($transactionData['remittanceInformation']);
        }

        // Set creditor address if provided (optional)
        if (isset($transactionData['creditorAddress']) && is_array($transactionData['creditorAddress']) && (isset($transactionData['creditorAddress']) && $transactionData['creditorAddress'] !== [])) {
            $transaction->setCreditorAddressFromArray($transactionData['creditorAddress']);
        } elseif (isset($transactionData['creditor_street']) || isset($transactionData['creditor_city']) || isset($transactionData['creditor_postal_code']) || isset($transactionData['creditor_country'])
                  || isset($transactionData['creditorStreet']) || isset($transactionData['creditorCity']) || isset($transactionData['creditorPostalCode']) || isset($transactionData['creditorCountry'])) {
            // Support individual address fields (only if at least one is provided)
            $transaction->setCreditorAddress(
                $transactionData['creditor_street'] ?? $transactionData['creditorStreet'] ?? null,
                $transactionData['creditor_city'] ?? $transactionData['creditorCity'] ?? null,
                $transactionData['creditor_postal_code'] ?? $transactionData['creditorPostalCode'] ?? null,
                $transactionData['creditor_country'] ?? $transactionData['creditorCountry'] ?? null,
            );
        }

        return $transaction;
    }

    /**
     * Parses a transaction amount from array data.
     * "amount" is always a decimal amount in euros (e.g. 15000.00 is fifteen thousand euros);
     * "amountCents" is an integer amount in cents (e.g. 1500000). Exactly one of them must be given.
     *
     * @param array<string, mixed> $transactionData The transaction data
     *
     * @throws InvalidArgumentException If no amount, both amounts or a non-numeric amount is given
     *
     * @return float The amount in euros
     */
    private function parseAmount(array $transactionData): float
    {
        $hasAmount = isset($transactionData['amount']);
        $hasCents  = isset($transactionData['amountCents']);

        if (!$hasAmount && !$hasCents) {
            $message = $this->translator->trans('validation.missing_required_transaction_field', ['%field%' => 'amount'], 'NowoSepaPaymentBundle');

            throw new InvalidArgumentException($message);
        }

        $invalidMessage = $this->translator->trans('validation.invalid_amount', [], 'NowoSepaPaymentBundle');
        if ($hasAmount && $hasCents) {
            throw new InvalidArgumentException($invalidMessage);
        }

        if ($hasCents) {
            $cents = $transactionData['amountCents'];
            if (is_string($cents) && ctype_digit($cents)) {
                $cents = (int) $cents;
            }
            if (!is_int($cents)) {
                throw new InvalidArgumentException($invalidMessage);
            }

            return $cents / 100;
        }

        $amount = $transactionData['amount'];
        if (!is_int($amount) && !is_float($amount) && (!is_string($amount) || !is_numeric($amount))) {
            throw new InvalidArgumentException($invalidMessage);
        }

        return (float) $amount;
    }

    /**
     * Validates a currency code: 3 uppercase letters (ISO 4217) and EUR, the only SEPA currency.
     *
     * @param string $currency The currency code
     *
     * @throws InvalidArgumentException If the code is malformed or not EUR
     */
    private function assertSepaCurrency(string $currency): void
    {
        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            $message = $this->translator->trans('validation.invalid_currency_code', ['%currency%' => $currency], 'NowoSepaPaymentBundle');

            throw new InvalidArgumentException($message);
        }

        if ($currency !== SepaBusinessRulesValidator::SEPA_CURRENCY) {
            $message = $this->translator->trans('validation.invalid_currency', ['%currency%' => $currency], 'NowoSepaPaymentBundle');

            throw new InvalidArgumentException($message);
        }
    }

    /**
     * Builds a safe Content-Disposition attachment header value (RFC 6266) for the given filename.
     * Path separators are replaced and a plain-ASCII fallback is added for non-ASCII names.
     *
     * @param string $filename The requested download filename
     *
     * @return string The header value
     */
    private function makeAttachmentDisposition(string $filename): string
    {
        $filename = str_replace(['/', '\\'], '_', $filename);
        $fallback = (string) preg_replace('/[^\x20-\x7e]|[%"]/', '_', $filename);

        return HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename, $fallback);
    }

    /**
     * Attempts to set postal address on transfer information (debtor address).
     * Note: The Digitick\Sepa library may not support this directly, so addresses
     * are also added via DOM manipulation in addAddressesToXml() method.
     *
     * @param CustomerCreditTransferInformation $transferInformation The transfer information object
     * @param array<string, string|null> $address Address array with keys: street, city, postalCode, country
     */
    private function setPostalAddress(
        CustomerCreditTransferInformation $transferInformation,
        array $address
    ): void {
        // PHPStan: method_exists() always true for Digitick\Sepa\TransferInformation\CustomerCreditTransferInformation (has setCountry/setTownName/setPostCode/setStreetName).
        // Fix: call directly; digitick/sepa-xml library provides them. Logic kept for compatibility with other implementations.
        $transferInformation->setCountry($address['country'] ?? '');
        $transferInformation->setTownName($address['city'] ?? '');
        $transferInformation->setPostCode($address['postalCode'] ?? '');
        $transferInformation->setStreetName($address['street'] ?? '');

        // Note: Addresses are always added to XML via DOM manipulation in addAddressesToXml()
        // even if the library methods don't exist, ensuring addresses are included in the final XML
    }

    /**
     * Attempts to set creditor postal address on payment information.
     * Note: The Digitick\Sepa library may not support this directly, so addresses
     * are also added via DOM manipulation in addAddressesToXml() method.
     *
     * @param object $paymentInformation The payment information object (PaymentInformation or test double)
     * @param array<string, string|null> $address Address array with keys: street, city, postalCode, country
     */
    private function setCreditorPostalAddress(
        object $paymentInformation,
        array $address
    ): void {
        // PHPStan: method_exists() always true for Digitick PaymentInformation (has setCreditorPostalAddress/setPostalAddress/setAddress).
        // Fix: try in order; first existing method runs. Compatible with different library versions.
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
        // Note: Addresses are always added to XML via DOM manipulation in addAddressesToXml()
        // even if the library methods don't exist, ensuring addresses are included in the final XML
    }

    /**
     * Adds addresses to the generated XML using DOM manipulation.
     * This method ensures addresses are included in the final XML even if the Digitick\Sepa
     * library doesn't support them directly through its API methods.
     *
     * @param string $xml The generated XML from the library
     * @param CreditTransferData $creditTransferData The credit transfer data containing creditor and debtor addresses
     *
     * @return string The XML with addresses added via DOM manipulation
     */
    private function addAddressesToXml(string $xml, CreditTransferData $creditTransferData): string
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
            $namespace = $root->namespaceURI ?? 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.09';
            $xpath->registerNamespace('ns', $namespace);

            // Add creditor address if available
            $creditorAddress = $creditTransferData->getCreditorAddress();
            if ($creditorAddress !== null) {
                $this->addDebtorAddressToDom($dom, $xpath, $creditorAddress, $namespace);
            }

            // Add creditor addresses for each transaction
            $transactions = $creditTransferData->getTransactions();
            foreach ($transactions as $index => $transaction) {
                $creditorAddress = $transaction->getCreditorAddress();
                if ($creditorAddress !== null) {
                    $this->addCreditorAddressToDom($dom, $xpath, $creditorAddress, $index, $namespace);
                }
            }

            // PHPStan: saveXML() returns string|false; we guarantee valid XML from loadXML() so we return string or fallback to original
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
     * @param array<string, string|null> $address The address array (street, city, postalCode, country)
     * @param string $namespace The namespace URI
     */
    private function addDebtorAddressToDom(DOMDocument $dom, DOMXPath $xpath, array $address, string $namespace): void
    {
        // Find Cdtr (Creditor) element
        $creditorNodes = $xpath->query('//ns:Dbtr');
        if ($creditorNodes === false || $creditorNodes->length === 0) {
            $creditorNodes = $xpath->query('//Dbtr');
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
    private function addCreditorAddressToDom(DOMDocument $dom, DOMXPath $xpath, array $address, int $index, string $namespace): void
    {
        // Find Dbtr (Debtor) elements
        $debtorNodes = $xpath->query('//ns:Cdtr');
        if ($debtorNodes === false || $debtorNodes->length === 0) {
            $debtorNodes = $xpath->query('//Cdtr');
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
     * Only creates the element if at least one address field is provided.
     *
     * @param DOMDocument $dom The DOM document
     * @param DOMElement $parentNode The parent node (must be DOMElement for getElementsByTagNameNS/removeChild)
     * @param array<string, string|null> $address The address array (street, city, postalCode, country)
     * @param string $namespace The namespace URI
     */
    private function createPostalAddressElement(DOMDocument $dom, DOMElement $parentNode, array $address, string $namespace): void
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

        // Check if PstlAdr already exists — PHPStan: item(0) can be null; removeChild expects DOMNode, we guard with length > 0
        $existing = $parentNode->getElementsByTagNameNS($namespace, 'PstlAdr');
        if ($existing->length > 0) {
            $first = $existing->item(0);
            if ($first instanceof DOMNode) {
                $parentNode->removeChild($first);
            }
        }

        $pstlAdr = $dom->createElementNS($namespace, 'PstlAdr');

        // Add structured address elements only if they are not empty
        if (!empty($address['street'])) {
            $strtNm = $dom->createElementNS($namespace, 'StrtNm', htmlspecialchars($address['street'], ENT_XML1, 'UTF-8'));
            $pstlAdr->appendChild($strtNm);
        }

        // ISO 20022 PostalAddress6 sequence: StrtNm, BldgNb, PstCd, TwnNm, CtrySubDvsn, Ctry, AdrLine
        if (!empty($address['postalCode'])) {
            $pstCd = $dom->createElementNS($namespace, 'PstCd', htmlspecialchars($address['postalCode'], ENT_XML1, 'UTF-8'));
            $pstlAdr->appendChild($pstCd);
        }

        if (!empty($address['city'])) {
            $twnNm = $dom->createElementNS($namespace, 'TwnNm', htmlspecialchars($address['city'], ENT_XML1, 'UTF-8'));
            $pstlAdr->appendChild($twnNm);
        }

        if (!empty($address['country'])) {
            $ctry = $dom->createElementNS($namespace, 'Ctry', htmlspecialchars($address['country'], ENT_XML1, 'UTF-8'));
            $pstlAdr->appendChild($ctry);
        }

        // Only add PstlAdr if it has at least one child element
        if ($pstlAdr->childNodes->length > 0) {
            // Insert after Nm (Name) element if it exists — PHPStan: item(0) can be null, so guard before accessing nextSibling
            $nmNodes = $parentNode->getElementsByTagNameNS($namespace, 'Nm');
            if ($nmNodes->length > 0) {
                $nmFirst     = $nmNodes->item(0);
                $nextSibling = $nmFirst instanceof DOMElement ? $nmFirst->nextSibling : null;
                if ($nextSibling instanceof DOMNode) {
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
     * @throws InvalidArgumentException If the data is invalid
     */
    private function validateCreditTransferData(CreditTransferData $creditTransferData): void
    {
        $this->assertSepaCurrency($this->defaultCurrency);
        foreach ($creditTransferData->getTransactions() as $transaction) {
            $this->assertSepaCurrency($transaction->getCurrency());
        }

        $this->assertFieldLengths($creditTransferData);

        if (!$this->ibanValidator->isValid($creditTransferData->getCreditorIban())) {
            $message = $this->translator->trans('validation.invalid_creditor_iban', ['%iban%' => $creditTransferData->getCreditorIban()], 'NowoSepaPaymentBundle');

            throw new InvalidArgumentException($message);
        }

        foreach ($creditTransferData->getTransactions() as $transaction) {
            if (!$this->ibanValidator->isValid($transaction->getCreditorIban())) {
                $message = $this->translator->trans('validation.invalid_creditor_iban', ['%iban%' => $transaction->getCreditorIban()], 'NowoSepaPaymentBundle');

                throw new InvalidArgumentException($message);
            }
        }
    }

    /**
     * Writes the requested creation date into the group header (GrpHdr/CreDtTm).
     * digitick/sepa-xml always uses "now" and exposes no setter for it.
     *
     * @param GroupHeader $groupHeader The group header
     * @param DateTimeInterface $creationDate The creation date and time
     */
    private function setGroupHeaderCreationDate(GroupHeader $groupHeader, DateTimeInterface $creationDate): void
    {
        $immutableDate = DateTimeImmutable::createFromInterface($creationDate);
        (function () use ($immutableDate): void {
            // @igor-ignore - Bound to the per-call GroupHeader instance, not to this service.
            $this->creationDateTime = $immutableDate;
        })->call($groupHeader);
    }

    /**
     * Enforces the SEPA (EPC implementation guidelines) maximum field lengths.
     *
     * @param CreditTransferData $creditTransferData The credit transfer data to check
     *
     * @throws InvalidArgumentException If a field exceeds its maximum length
     */
    private function assertFieldLengths(CreditTransferData $creditTransferData): void
    {
        $sanitizer = $this->stringSanitizer;
        $sanitizer->assertMaxLength('MsgId', $creditTransferData->getMessageId(), SepaStringSanitizer::MAX_IDENTIFIER_LENGTH);
        $sanitizer->assertMaxLength('InitgPty/Nm', $creditTransferData->getInitiatingPartyName(), SepaStringSanitizer::MAX_NAME_LENGTH);
        $sanitizer->assertMaxLength('PmtInfId', $creditTransferData->getPaymentInfoId(), SepaStringSanitizer::MAX_IDENTIFIER_LENGTH);
        $sanitizer->assertMaxLength('Dbtr/Nm', $creditTransferData->getCreditorName(), SepaStringSanitizer::MAX_NAME_LENGTH);

        foreach ($creditTransferData->getTransactions() as $transaction) {
            $sanitizer->assertMaxLength('EndToEndId', $transaction->getEndToEndId(), SepaStringSanitizer::MAX_IDENTIFIER_LENGTH);
            $sanitizer->assertMaxLength('Cdtr/Nm', $transaction->getCreditorName(), SepaStringSanitizer::MAX_NAME_LENGTH);
            $remittanceInformation = $transaction->getRemittanceInformation();
            if ($remittanceInformation !== null) {
                $sanitizer->assertMaxLength('RmtInf/Ustrd', $remittanceInformation, SepaStringSanitizer::MAX_REMITTANCE_INFO_LENGTH);
            }
        }
    }
}
