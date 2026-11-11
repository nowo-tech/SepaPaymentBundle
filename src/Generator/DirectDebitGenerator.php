<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Generator;

use DateTime;
use DateTimeImmutable;
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
use Nowo\SepaPaymentBundle\Http\SepaXmlResponseFactory;
use Nowo\SepaPaymentBundle\Logger\SepaPaymentLogger;
use Nowo\SepaPaymentBundle\Lookup\BicLookupServiceInterface;
use Nowo\SepaPaymentBundle\Model\Amount;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitData;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitGenerationOptions;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitTransaction;
use Nowo\SepaPaymentBundle\Model\DirectDebit\Pain008Version;
use Nowo\SepaPaymentBundle\Model\DirectDebit\SequenceType;
use Nowo\SepaPaymentBundle\Model\DirectDebit\SequenceTypeMode;
use Nowo\SepaPaymentBundle\Model\Mandate\MandateAmendment;
use Nowo\SepaPaymentBundle\Model\PartyIdentification;
use Nowo\SepaPaymentBundle\Validator\IbanValidatorInterface;
use Nowo\SepaPaymentBundle\Validator\SepaBusinessRulesValidator;
use Nowo\SepaPaymentBundle\Validator\SepaStringSanitizer;
use Nowo\SepaPaymentBundle\Validator\XsdValidator;
use Nowo\SepaPaymentBundle\Validator\XsdValidatorInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function array_column;
use function array_key_first;
use function array_map;
use function array_unique;
use function count;
use function implode;
use function in_array;
use function is_array;
use function is_float;
use function is_int;
use function is_string;
use function sprintf;
use function strlen;
use function trigger_error;
use function trim;

use const E_USER_DEPRECATED;
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
#[AsAlias(id: DirectDebitGeneratorInterface::class)]
final class DirectDebitGenerator implements DirectDebitGeneratorInterface
{
    public const SERVICE_NAME = 'nowo_sepa_payment.generator.direct_debit_generator';

    /**
     * Error message for an invalid or ambiguous transaction amount.
     */
    private const INVALID_AMOUNT_MESSAGE = 'Invalid transaction amount: use either "amount" (decimal, in euros) or "amountCents" (integer, in cents), not both.';

    /**
     * Whether to validate generated XML against XSD schema.
     */
    private bool $validateXsd;

    /**
     * Sanitizer used to enforce SEPA field length limits.
     */
    private readonly SepaStringSanitizer $stringSanitizer;

    /**
     * Default message version (bundle config `direct_debit.pain_version`).
     */
    private readonly Pain008Version $defaultPainVersion;

    /**
     * Default sequence type mode (bundle config `direct_debit.sequence_type_mode`).
     */
    private readonly SequenceTypeMode $defaultSequenceTypeMode;

    /**
     * Constructor.
     *
     * @param IbanValidatorInterface $ibanValidator IBAN validator (any implementation, e.g. a host decorator)
     * @param XsdValidator|null $xsdValidator Optional XSD validator instance
     * @param bool $validateXsd Whether to enable XSD validation (default: false)
     * @param EventDispatcherInterface|null $eventDispatcher Optional event dispatcher for Symfony events
     * @param SepaPaymentLogger|null $logger Optional logger for structured logging
     * @param BicLookupServiceInterface|null $bicLookupService Optional BIC lookup service for auto-filling BIC
     * @param string $defaultCurrency Currency used when a transaction has none (bundle config `default_currency`; SEPA requires EUR)
     * @param SepaStringSanitizer|null $stringSanitizer Sanitizer used to enforce SEPA field length limits
     * @param Pain008Version|string $defaultPainVersion Message version when the call gives none (config `direct_debit.pain_version`)
     * @param SequenceTypeMode|string $defaultSequenceTypeMode Sequence type mode when the call gives none (config `direct_debit.sequence_type_mode`)
     */
    public function __construct(
        private readonly IbanValidatorInterface $ibanValidator,
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
        Pain008Version|string $defaultPainVersion = Pain008Version::DEFAULT,
        SequenceTypeMode|string $defaultSequenceTypeMode = SequenceTypeMode::DEFAULT,
    ) {
        if ($validateXsd && !$this->xsdValidator instanceof XsdValidatorInterface) {
            // Never disable a requested validation silently (LB-D-I1)
            throw new InvalidArgumentException('XSD validation was enabled (validateXsd: true) but no XSD validator was given: pass an XsdValidatorInterface (the bundle service has one).');
        }
        $this->validateXsd             = $validateXsd;
        $this->stringSanitizer         = $stringSanitizer ?? new SepaStringSanitizer();
        $this->defaultPainVersion      = Pain008Version::fromValue($defaultPainVersion);
        $this->defaultSequenceTypeMode = SequenceTypeMode::fromValue($defaultSequenceTypeMode);
    }

    /**
     * Generates a SEPA Direct Debit XML file from array data.
     *
     * @param array<string, mixed> $data The direct debit data in array format
     * @param DirectDebitGenerationOptions|null $options Per-call options (null: bundle configuration)
     *
     * @throws InvalidArgumentException If the data is invalid
     *
     * @return string The XML content
     */
    public function generateFromArray(array $data, ?DirectDebitGenerationOptions $options = null): string
    {
        $directDebitData = $this->createDirectDebitDataFromArray($data);

        return $this->generate($directDebitData, $options);
    }

    /**
     * Creates an HTTP Response with XML content for download.
     *
     * @deprecated since nowo-tech/sepa-payment-bundle 2.2, use {@see SepaXmlResponseFactory::createResponse()} (removed in 3.0)
     *
     * @param string $xmlData The XML content
     * @param string $filename The filename for the download (e.g., "direct-debit.xml")
     *
     * @return Response The HTTP response with XML content
     */
    public function createResponse(string $xmlData, string $filename): Response
    {
        @trigger_error('DirectDebitGenerator::createResponse() is deprecated since nowo-tech/sepa-payment-bundle 2.2: use Nowo\\SepaPaymentBundle\\Http\\SepaXmlResponseFactory::createResponse() instead.', E_USER_DEPRECATED);

        return (new SepaXmlResponseFactory())->createResponse($xmlData, $filename);
    }

    /**
     * Generates a SEPA Direct Debit XML file.
     *
     * One `PmtInf` is written per payment-information block ({@see DirectDebitData::getPaymentInformation()}); in the
     * {@see SequenceTypeMode::PER_TRANSACTION} mode a block whose transactions carry different sequence types is split
     * into one `PmtInf` per sequence type (`<PmtInfId>-FRST`, `<PmtInfId>-RCUR`, ...).
     *
     * @param DirectDebitData $directDebitData The direct debit data
     * @param DirectDebitGenerationOptions|null $options Per-call options (null options: bundle configuration)
     *
     * @throws InvalidArgumentException If the data is invalid
     *
     * @return string The XML content
     */
    public function generate(DirectDebitData $directDebitData, ?DirectDebitGenerationOptions $options = null): string
    {
        $transactionCount = count($directDebitData->getTransactions());
        $messageId        = $directDebitData->getMessageId();
        $painVersion      = $options?->getPainVersion() ?? $this->defaultPainVersion;
        $sequenceTypeMode = $options?->getSequenceTypeMode() ?? $this->defaultSequenceTypeMode;
        $validateXsd      = ($options?->getValidateXsd() ?? $this->validateXsd) && $this->xsdValidator instanceof XsdValidatorInterface;
        if ($options?->getValidateXsd() === true && !$this->xsdValidator instanceof XsdValidatorInterface) {
            throw new InvalidArgumentException('XSD validation was requested (validateXsd: true) but the generator has no XSD validator: pass an XsdValidatorInterface to the constructor (the bundle service has one).');
        }

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
            $blocks = $this->resolvePaymentBlocks($directDebitData, $sequenceTypeMode);

            // Create and configure group header
            $groupHeader = new GroupHeader(
                $directDebitData->getMessageId(),
                $directDebitData->getInitiatingPartyName(),
            );
            $creationDateTime = $options?->getCreationDateTime();
            if ($creationDateTime instanceof DateTimeImmutable) {
                $this->setGroupHeaderCreationDate($groupHeader, $creationDateTime);
            }

            // Create transfer file with group header (the version is chosen when the DOM is built)
            $transferFile = new CustomerDirectDebitTransferFile($groupHeader);

            // Auto-fill creditor BIC if missing
            $creditorBic = $directDebitData->getCreditorBic();
            if ($creditorBic === '') {
                $creditorBic = null;
            }
            if ($creditorBic === null && $this->bicLookupService instanceof BicLookupServiceInterface) {
                $lookedUpBic = $this->bicLookupService->lookupBic($directDebitData->getCreditorIban());
                if ($lookedUpBic !== null) {
                    $creditorBic = $lookedUpBic;
                }
            }

            $creditorIban    = $this->ibanValidator->normalize($directDebitData->getCreditorIban());
            $creditorAddress = $directDebitData->getCreditorAddress();
            $written         = [];
            $controlSum      = 0;
            $blockSums       = [];
            foreach ($blocks as $block) {
                $blockSum = 0;
                // Without a BIC the creditor agent is written as <Othr><Id>NOTPROVIDED</Id></Othr> (P4)
                $paymentInformation = new PaymentInformation(
                    $block['id'],
                    $creditorIban,
                    $creditorBic,
                    $directDebitData->getCreditorName(),
                    $this->defaultCurrency,
                );
                // Payment method is automatically set based on the transfer file type (CustomerDirectDebitTransferFile)
                $paymentInformation->setDueDate($block['dueDate']);
                $paymentInformation->setCreditorId($directDebitData->getCreditorId());
                // In the "omit" mode a placeholder is written here and the SeqTp element removed afterwards
                $paymentInformation->setSequenceType($block['sequenceType'] ?? SequenceType::RECURRENT);
                $paymentInformation->setLocalInstrumentCode($block['localInstrumentCode']);

                // Set creditor address if available
                if ($creditorAddress !== null) {
                    $this->setCreditorPostalAddress($paymentInformation, $creditorAddress);
                }

                foreach ($block['transactions'] as $transaction) {
                    $paymentInformation->addTransfer($this->createTransferInformation($transaction));
                    $written[] = $transaction;
                    $controlSum += $transaction->getAmountCents();
                    $blockSum += $transaction->getAmountCents();
                }
                $blockSums[] = $blockSum;

                $transferFile->addPaymentInformation($paymentInformation);
            }

            // Generate XML
            $domBuilder = DomBuilderFactory::createDomBuilder($transferFile, $painVersion->value);
            $xml        = $domBuilder->asXml();

            // Add addresses, the initiating party id and the sequence type mode to the XML
            $xml = $this->addAddressesToXml(
                $xml,
                $directDebitData,
                $written,
                $sequenceTypeMode === SequenceTypeMode::OMIT,
                $directDebitData->getInitiatingPartyIdentification(),
                $blockSums,
                $controlSum,
            );

            // Validate against the XSD of the written version if enabled
            if ($validateXsd && $this->xsdValidator instanceof XsdValidatorInterface) {
                try {
                    $this->xsdValidator->validateDirectDebit($xml);
                } catch (InvalidArgumentException $e) {
                    throw new InvalidArgumentException('Generated XML failed XSD validation: ' . $e->getMessage(), 0, $e);
                }
            }

            // Dispatch after generation event (before the host stores the file: the bundle never persists anything)
            if ($this->eventDispatcher instanceof EventDispatcherInterface) {
                $afterEvent = new AfterDirectDebitGenerationEvent(
                    $xml,
                    $directDebitData->getMessageId(),
                    array_column($blocks, 'id'),
                    $painVersion->value,
                    count($written),
                    $controlSum,
                );
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
     * Builds the library transfer information of one transaction.
     *
     * @param DirectDebitTransaction $transaction The transaction
     */
    private function createTransferInformation(DirectDebitTransaction $transaction): CustomerDirectDebitTransferInformation
    {
        $transferInformation = new CustomerDirectDebitTransferInformation(
            $transaction->getAmountCents(), // exact integer cents
            $this->ibanValidator->normalize($transaction->getDebtorIban()),
            $transaction->getDebtorName(),
            $transaction->getEndToEndId(),
        );
        $transferInformation->setCurrency($transaction->getCurrency() ?? $this->defaultCurrency);

        $transferInformation->setMandateId($transaction->getDebtorMandate());
        $transferInformation->setMandateSignDate($transaction->getDebtorMandateSignDate());

        if ($transaction->getRemittanceInformation() !== null) {
            $transferInformation->setRemittanceInformation($transaction->getRemittanceInformation());
        }

        // Auto-fill debtor BIC if missing
        $debtorBic = $transaction->getDebtorBic();
        if ($debtorBic === '') {
            $debtorBic = null;
        }
        if ($debtorBic === null && $this->bicLookupService instanceof BicLookupServiceInterface) {
            $lookedUpBic = $this->bicLookupService->lookupBic($transaction->getDebtorIban());
            if ($lookedUpBic !== null) {
                $debtorBic = $lookedUpBic;
            }
        }

        // Set debtor BIC if available (without one the debtor agent is written as NOTPROVIDED)
        if ($debtorBic !== null) {
            $transferInformation->setBic($debtorBic);
        }

        // Apply additional data if available
        // Note: This allows for future extensibility. Additional fields can be set
        // using methods available in CustomerDirectDebitTransferInformation
        $this->applyAdditionalData($transferInformation, $transaction);

        return $transferInformation;
    }

    /**
     * Resolves the `PmtInf` blocks to write: the data's payment-information blocks, with the sequence type mode applied.
     *
     * @param DirectDebitData $directDebitData The direct debit data
     * @param SequenceTypeMode $mode The sequence type mode
     *
     * @throws InvalidArgumentException If a block is empty, a sequence type is missing, local instruments are mixed, or a
     *                                  PmtInfId is too long or used twice
     *
     * @return list<array{id: string, dueDate: DateTimeInterface, sequenceType: string|null, localInstrumentCode: string, transactions: list<DirectDebitTransaction>}>
     */
    private function resolvePaymentBlocks(DirectDebitData $directDebitData, SequenceTypeMode $mode): array
    {
        $resolved = [];
        foreach ($directDebitData->getPaymentInformation() as $block) {
            // An empty PmtInf is XSD-invalid and every bank rejects it (LB-L4)
            if ($block->getTransactions() === []) {
                throw new InvalidArgumentException(sprintf('Payment information "%s" has no transactions: a direct debit file needs at least one transaction in every block.', $block->getPaymentInfoId()));
            }

            $base = [
                'id'                  => $block->getPaymentInfoId(),
                'dueDate'             => $block->getDueDate(),
                'sequenceType'        => null,
                'localInstrumentCode' => $block->getLocalInstrumentCode(),
                'transactions'        => $block->getTransactions(),
            ];

            if ($mode === SequenceTypeMode::OMIT) {
                $resolved[] = $base;
                continue;
            }

            /** @var array<string, list<DirectDebitTransaction>> $groups */
            $groups = [];
            foreach ($block->getTransactions() as $transaction) {
                $sequenceType = $transaction->getSequenceType() ?? $block->getSequenceType();
                if ($mode === SequenceTypeMode::ALWAYS_RCUR) {
                    // One-off mandates stay OOFF; FRST, FNAL, RCUR and "none" become RCUR (LB-L1)
                    $sequenceType = $sequenceType === SequenceType::ONE_OFF ? SequenceType::ONE_OFF : SequenceType::RECURRENT;
                } elseif ($sequenceType === null) {
                    throw new InvalidArgumentException(sprintf('Missing sequence type for transaction "%s" in payment information "%s": set it on the block or on the transaction, or use the "always_rcur" or "omit" sequence type mode.', $transaction->getEndToEndId(), $block->getPaymentInfoId()));
                }
                $groups[$sequenceType][] = $transaction;
            }

            if (count($groups) === 1) {
                $base['sequenceType'] = array_key_first($groups);
                $resolved[]           = $base;
                continue;
            }

            foreach ($groups as $sequenceType => $transactions) {
                $resolved[] = [
                    'id'           => $block->getPaymentInfoId() . '-' . $sequenceType,
                    'sequenceType' => (string) $sequenceType,
                    'transactions' => $transactions,
                ] + $base;
            }
        }

        // A change of debtor bank (SMNDA) must be collected as FRST (EPC rulebook)
        foreach ($resolved as $resolvedBlock) {
            foreach ($resolvedBlock['transactions'] as $transaction) {
                if ($transaction->getAmendment()?->isNewDebtorAgent() === true && $resolvedBlock['sequenceType'] !== null && $resolvedBlock['sequenceType'] !== SequenceType::FIRST) {
                    throw new InvalidArgumentException(sprintf('Transaction "%s" amends the mandate with a new debtor bank (SMNDA): it must be collected with sequence type FRST, not %s.', $transaction->getEndToEndId(), $resolvedBlock['sequenceType']));
                }
            }
        }

        // One local instrument per message: the EPC guidelines do not allow CORE and B2B in one file (LB-L5)
        $instruments = array_unique(array_column($resolved, 'localInstrumentCode'));
        if (count($instruments) > 1) {
            throw new InvalidArgumentException(sprintf('A direct debit file cannot mix local instruments (%s): generate one file per instrument.', implode(', ', $instruments)));
        }

        $seen = [];
        foreach ($resolved as $block) {
            $this->stringSanitizer->assertMaxLength('PmtInfId', $block['id'], SepaStringSanitizer::MAX_IDENTIFIER_LENGTH);
            if (isset($seen[$block['id']])) {
                throw new InvalidArgumentException(sprintf('Duplicate payment information id (PmtInfId) "%s": every block of a file needs its own id.', $block['id']));
            }
            $seen[$block['id']] = true;
        }

        return $resolved;
    }

    /**
     * Writes the requested creation date into the group header (GrpHdr/CreDtTm).
     * digitick/sepa-xml always uses "now" and exposes no setter for it.
     *
     * @param GroupHeader $groupHeader The group header
     * @param DateTimeImmutable $creationDate The creation date and time
     */
    private function setGroupHeaderCreationDate(GroupHeader $groupHeader, DateTimeImmutable $creationDate): void
    {
        (function () use ($creationDate): void {
            // @igor-ignore - Bound to the per-call GroupHeader instance, not to this service.
            $this->creationDateTime = $creationDate;
        })->call($groupHeader);
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
                $transaction     = $this->createTransactionFromArray($transactionData);
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
            'amount_cents'                  => 'amountCents',
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
     * @param array<string, mixed> $transactionData The transaction data (debtorMandateSignDate is required)
     *
     * @throws InvalidArgumentException If the data is invalid
     *
     * @return DirectDebitTransaction The DirectDebitTransaction object
     */
    private function createTransactionFromArray(array $transactionData): DirectDebitTransaction
    {
        $required = ['debtorIban', 'debtorName', 'debtorMandate', 'endToEndId'];
        foreach ($required as $field) {
            if (!isset($transactionData[$field])) {
                throw new InvalidArgumentException("Missing required transaction field: {$field}");
            }
        }

        // "amount" is always in euros (decimal); "amountCents" is the explicit integer alternative
        $amount = $this->parseAmount($transactionData);

        // Mandate signature date (DtOfSgntr) is mandatory in SEPA: never guess it from the due date
        $signDate = $transactionData['debtorMandateSignDate'] ?? null;
        if (is_string($signDate) && $signDate !== '') {
            $mandateSignDate = new DateTime($signDate);
        } elseif ($signDate instanceof DateTimeInterface) {
            $mandateSignDate = $signDate;
        } else {
            throw new InvalidArgumentException(sprintf('Missing or invalid required transaction field: debtorMandateSignDate (mandate signature date) for mandate "%s".', (string) $transactionData['debtorMandate']));
        }

        // Decimal string built from the exact cents (no float); `new` keeps the DTO visibly local (Igor)
        $transaction = new DirectDebitTransaction(
            Amount::decimalFromCents($amount),
            $transactionData['debtorIban'],
            $transactionData['debtorName'],
            $transactionData['debtorMandate'],
            $mandateSignDate,
            $transactionData['endToEndId'],
        );

        if (isset($transactionData['remittanceInformation'])) {
            $transaction->setRemittanceInformation($transactionData['remittanceInformation']);
        }

        if (isset($transactionData['currency'])) {
            $transaction->setCurrency((string) $transactionData['currency']);
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
        $standardFields = ['amount', 'amountCents', 'currency', 'debtorIban', 'debtorName', 'debtorMandate', 'debtorMandateSignDate', 'endToEndId', 'remittanceInformation', 'debtorBic', 'debtorAddress', 'debtor_street', 'debtor_city', 'debtor_postal_code', 'debtor_country', 'debtorStreet', 'debtorCity', 'debtorPostalCode', 'debtorCountry'];
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
     * Parses a transaction amount from array data.
     * "amount" is always a decimal amount in euros (e.g. 15000.00 is fifteen thousand euros);
     * "amountCents" is an integer amount in cents (e.g. 1500000). Exactly one of them must be given.
     *
     * @param array<string, mixed> $transactionData The transaction data
     *
     * A plain decimal string ("1500.25") is read exactly; an int is whole euros; a float or another numeric string
     * ("1e3") is rounded half up to the cent like round($x, 2) ({@see Amount}).
     *
     * @throws InvalidArgumentException If no amount, both amounts or a non-numeric amount is given
     *
     * @return int The amount in cents
     */
    private function parseAmount(array $transactionData): int
    {
        $hasAmount = isset($transactionData['amount']);
        $hasCents  = isset($transactionData['amountCents']);

        if (!$hasAmount && !$hasCents) {
            throw new InvalidArgumentException('Missing required transaction field: amount');
        }

        if ($hasAmount && $hasCents) {
            throw new InvalidArgumentException(self::INVALID_AMOUNT_MESSAGE);
        }

        if ($hasCents) {
            $cents = $transactionData['amountCents'];
            if (is_string($cents) && ctype_digit($cents)) {
                $cents = (int) $cents;
            }
            if (!is_int($cents)) {
                throw new InvalidArgumentException(self::INVALID_AMOUNT_MESSAGE);
            }

            return $cents;
        }

        $amount = $transactionData['amount'];
        if (!is_int($amount) && !is_float($amount) && (!is_string($amount) || !is_numeric($amount))) {
            throw new InvalidArgumentException(self::INVALID_AMOUNT_MESSAGE);
        }
        if (is_int($amount)) {
            return Amount::centsFromDecimal((string) $amount);
        }
        if (is_string($amount) && preg_match('/^-?\d{1,15}(?:\.\d+)?$/D', $amount) === 1) {
            return Amount::centsFromDecimal($amount);
        }

        return Amount::centsFromFloat((float) $amount);
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
            throw new InvalidArgumentException(sprintf('Invalid currency "%s": expected a 3-letter uppercase ISO 4217 code.', $currency));
        }

        if ($currency !== SepaBusinessRulesValidator::SEPA_CURRENCY) {
            throw new InvalidArgumentException(sprintf('Currency "%s" is not allowed: SEPA direct debits only support EUR.', $currency));
        }
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
        // EPC character set before the library writes it (LF-L3: the library may keep its own AdrLine)
        $address = array_map(fn (?string $value): ?string => $value === null ? null : $this->stringSanitizer->sanitize($value), $address);
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
        $address = array_map(fn (?string $value): ?string => $value === null ? null : $this->stringSanitizer->sanitize($value), $address);
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
     * Adds addresses (and, since 2.2, the initiating party id and the "omit" sequence type mode) to the generated XML
     * using DOM manipulation. This ensures they are included even if the library doesn't support them directly.
     *
     * @param string $xml The generated XML
     * @param DirectDebitData $directDebitData The direct debit data with addresses
     * @param list<DirectDebitTransaction>|null $transactions The transactions in the order they were written (null: the data's order)
     * @param bool $omitSequenceType Whether to remove every PmtTpInf/SeqTp element
     * @param PartyIdentification|null $initiatingPartyId The initiating party identification (GrpHdr/InitgPty/Id)
     * @param list<int>|null $blockSums Control sum in cents of each PmtInf, in file order (null: keep the library's)
     * @param int|null $controlSum Control sum in cents of the file (null: keep the library's)
     *
     * @return string The XML with addresses added
     */
    private function addAddressesToXml(string $xml, DirectDebitData $directDebitData, ?array $transactions = null, bool $omitSequenceType = false, ?PartyIdentification $initiatingPartyId = null, ?array $blockSums = null, ?int $controlSum = null): string
    {
        try {
            $dom                     = new DOMDocument();
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput       = true;

            if (!@$dom->loadXML($xml)) {
                // Since 2.2 this step carries semantics (omit mode, InitgPty/Id): never return the raw library XML (LB-L3)
                throw new InvalidArgumentException('Generated direct debit XML could not be loaded for post-processing (check that every text is valid UTF-8).');
            }

            $xpath = new DOMXPath($dom);
            // Detect namespace from root element
            $root      = $dom->documentElement;
            $namespace = $root->namespaceURI ?? 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.09';
            $xpath->registerNamespace('ns', $namespace);

            // Add creditor address if available (to the creditor of every payment-information block)
            $creditorAddress = $directDebitData->getCreditorAddress();
            if ($creditorAddress !== null) {
                $this->addCreditorAddressToDom($dom, $xpath, $creditorAddress, $namespace);
            }

            // Add debtor addresses for each transaction
            foreach ($transactions ?? $directDebitData->getTransactions() as $index => $transaction) {
                $debtorAddress = $transaction->getDebtorAddress();
                if ($debtorAddress !== null) {
                    $this->addDebtorAddressToDom($dom, $xpath, $debtorAddress, $index, $namespace);
                }
            }

            if ($omitSequenceType) {
                $this->removeSequenceTypes($xpath);
            }

            if ($initiatingPartyId instanceof PartyIdentification) {
                $this->addInitiatingPartyIdToDom($dom, $xpath, $initiatingPartyId, $namespace);
            }

            // Mandate amendments (MndtRltdInf/AmdmntInd + AmdmntInfDtls), by written transaction order
            foreach ($transactions ?? $directDebitData->getTransactions() as $index => $transaction) {
                $amendment = $transaction->getAmendment();
                if ($amendment instanceof MandateAmendment) {
                    $this->addAmendmentToDom($dom, $xpath, $amendment, $index, $namespace);
                }
            }

            // Control sums from the integer cents (LB-I2): the library divides a float, inexact beyond 10^15 cents
            if ($blockSums !== null && $controlSum !== null) {
                $this->writeControlSums($xpath, $blockSums, $controlSum);
            }

            $saved = $dom->saveXML();
            if ($saved === false) {
                throw new InvalidArgumentException('Generated direct debit XML could not be written after post-processing.');
            }

            return $saved;
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (Throwable $e) {
            // Fail closed (LB-L3): a half-processed file would miss the sequence type mode or the initiating party id
            throw new InvalidArgumentException('Generated direct debit XML could not be post-processed: ' . $e->getMessage(), 0, $e);
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

        foreach ($creditorNodes as $creditorNode) {
            if ($creditorNode instanceof DOMElement) {
                $this->createPostalAddressElement($dom, $creditorNode, $address, $namespace);
            }
        }
    }

    /**
     * Removes every sequence type element (PmtInf/PmtTpInf/SeqTp; the "omit" sequence type mode).
     *
     * @param DOMXPath $xpath The XPath object (namespace registered as "ns")
     */
    private function removeSequenceTypes(DOMXPath $xpath): void
    {
        $nodes = $xpath->query('//ns:PmtInf/ns:PmtTpInf/ns:SeqTp');
        if ($nodes === false) {
            return;
        }
        foreach ($nodes as $node) {
            if ($node instanceof DOMElement) {
                $node->parentNode?->removeChild($node);
            }
        }
    }

    /**
     * Writes the initiating party identification (GrpHdr/InitgPty/Id) after InitgPty/Nm.
     *
     * @param DOMDocument $dom The DOM document
     * @param DOMXPath $xpath The XPath object (namespace registered as "ns")
     * @param PartyIdentification $identification The identification
     * @param string $namespace The namespace URI
     */
    private function addInitiatingPartyIdToDom(DOMDocument $dom, DOMXPath $xpath, PartyIdentification $identification, string $namespace): void
    {
        $parties = $xpath->query('//ns:GrpHdr/ns:InitgPty');
        $party   = $parties === false ? null : $parties->item(0);
        if (!$party instanceof DOMElement) {
            return;
        }

        $existing = $xpath->query('ns:Id', $party);
        if ($existing !== false) {
            foreach ($existing as $node) {
                if ($node instanceof DOMElement) {
                    $party->removeChild($node);
                }
            }
        }

        $other = $dom->createElementNS($namespace, 'Othr');
        $other->appendChild($dom->createElementNS($namespace, 'Id', htmlspecialchars($identification->getId(), ENT_XML1, 'UTF-8')));
        $schemeCode        = $identification->getSchemeCode();
        $schemeProprietary = $identification->getSchemeProprietary();
        if ($schemeCode !== null || $schemeProprietary !== null) {
            $schemeName = $dom->createElementNS($namespace, 'SchmeNm');
            $schemeName->appendChild($schemeCode !== null
                ? $dom->createElementNS($namespace, 'Cd', $schemeCode)
                : $dom->createElementNS($namespace, 'Prtry', htmlspecialchars($this->stringSanitizer->sanitize((string) $schemeProprietary), ENT_XML1, 'UTF-8')));
            $other->appendChild($schemeName);
        }
        $issuer = $identification->getIssuer();
        if ($issuer !== null) {
            $other->appendChild($dom->createElementNS($namespace, 'Issr', htmlspecialchars($this->stringSanitizer->sanitize($issuer), ENT_XML1, 'UTF-8')));
        }

        $kind = $dom->createElementNS($namespace, $identification->isOrganisation() ? 'OrgId' : 'PrvtId');
        $kind->appendChild($other);
        $id = $dom->createElementNS($namespace, 'Id');
        $id->appendChild($kind);

        // InitgPty: Nm, PstlAdr, Id, CtryOfRes, CtctDtls; the generator writes Nm only
        $party->appendChild($id);
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
        // EPC character set for text added after the library (LB-I4); lengths are checked before generation
        $address = array_map(fn (?string $value): ?string => $value === null ? null : $this->stringSanitizer->sanitize($value), $address);

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

        if ($pstlAdr->childNodes->length > 0) {
            // PHPStan: item(0) can be null; do not access nextSibling without checking it is DOMElement
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
     * Validates direct debit data.
     *
     * @param DirectDebitData $directDebitData The direct debit data to validate
     *
     * @throws InvalidArgumentException If the data is invalid
     */
    private function validateDirectDebitData(DirectDebitData $directDebitData): void
    {
        $this->assertSepaCurrency($this->defaultCurrency);
        foreach ($directDebitData->getTransactions() as $transaction) {
            $this->assertSepaCurrency($transaction->getCurrency() ?? $this->defaultCurrency);
        }

        $this->assertFieldLengths($directDebitData);

        if (!$this->ibanValidator->isValid($directDebitData->getCreditorIban())) {
            throw new InvalidArgumentException('Invalid creditor IBAN: ' . $directDebitData->getCreditorIban());
        }

        foreach ($directDebitData->getTransactions() as $transaction) {
            if (!$this->ibanValidator->isValid($transaction->getDebtorIban())) {
                throw new InvalidArgumentException('Invalid debtor IBAN: ' . $transaction->getDebtorIban());
            }
        }

        // Texts added to the XML after the library must be valid UTF-8 (LB-L3)
        $this->assertUtf8Address('creditor address', $directDebitData->getCreditorAddress());
        $this->assertEpcAddress('creditor address', $directDebitData->getCreditorAddress());
        foreach ($directDebitData->getTransactions() as $transaction) {
            $field = sprintf('debtor address of transaction "%s"', $transaction->getEndToEndId());
            $this->assertUtf8Address($field, $transaction->getDebtorAddress());
            $this->assertEpcAddress($field, $transaction->getDebtorAddress());
            $originalCreditorName = $transaction->getAmendment()?->getOriginalCreditorName();
            if ($originalCreditorName !== null) {
                $this->assertNotEmptyAfterSanitizing(sprintf('original creditor name of the amendment of transaction "%s"', $transaction->getEndToEndId()), $originalCreditorName);
            }
        }

        // EndToEndId identifies a collection for the creditor and its bank: unique in the file (LB-I6)
        $endToEndIds = [];
        foreach ($directDebitData->getTransactions() as $transaction) {
            if (isset($endToEndIds[$transaction->getEndToEndId()])) {
                throw new InvalidArgumentException(sprintf('Duplicate EndToEndId "%s": every transaction of a file needs its own end-to-end id.', $transaction->getEndToEndId()));
            }
            $endToEndIds[$transaction->getEndToEndId()] = true;
        }

        // EPC range per transaction (KQ-P-L3): 0.01 to 999 999 999.99 EUR
        foreach ($directDebitData->getTransactions() as $transaction) {
            if (!SepaBusinessRulesValidator::isValidTransactionAmountCents($transaction->getAmountCents())) {
                throw new InvalidArgumentException(sprintf('Invalid amount for transaction "%s": %s EUR is outside the SEPA range of 0.01 to 999999999.99 EUR (1 to %d cents).', $transaction->getEndToEndId(), $transaction->getAmountDecimal(), SepaBusinessRulesValidator::MAX_TRANSACTION_AMOUNT_CENTS));
            }
        }
    }

    /**
     * Enforces the SEPA (EPC implementation guidelines) maximum field lengths.
     *
     * @param DirectDebitData $directDebitData The direct debit data to check
     *
     * @throws InvalidArgumentException If a field exceeds its maximum length
     */
    private function assertFieldLengths(DirectDebitData $directDebitData): void
    {
        $sanitizer = $this->stringSanitizer;
        $sanitizer->assertMaxLength('MsgId', $directDebitData->getMessageId(), SepaStringSanitizer::MAX_IDENTIFIER_LENGTH);
        $sanitizer->assertMaxLength('InitgPty/Nm', $directDebitData->getInitiatingPartyName(), SepaStringSanitizer::MAX_NAME_LENGTH);
        $sanitizer->assertMaxLength('PmtInfId', $directDebitData->getPaymentInfoId(), SepaStringSanitizer::MAX_IDENTIFIER_LENGTH);
        $sanitizer->assertMaxLength('Cdtr/Nm', $directDebitData->getCreditorName(), SepaStringSanitizer::MAX_NAME_LENGTH);
        $sanitizer->assertMaxLength('CdtrSchmeId', $directDebitData->getCreditorId(), SepaStringSanitizer::MAX_IDENTIFIER_LENGTH);

        foreach ($directDebitData->getTransactions() as $transaction) {
            $sanitizer->assertMaxLength('EndToEndId', $transaction->getEndToEndId(), SepaStringSanitizer::MAX_IDENTIFIER_LENGTH);
            $sanitizer->assertMaxLength('Dbtr/Nm', $transaction->getDebtorName(), SepaStringSanitizer::MAX_NAME_LENGTH);
            $sanitizer->assertMaxLength('MndtId', $transaction->getDebtorMandate(), SepaStringSanitizer::MAX_IDENTIFIER_LENGTH);
            $remittanceInformation = $transaction->getRemittanceInformation();
            if ($remittanceInformation !== null) {
                $sanitizer->assertMaxLength('RmtInf/Ustrd', $remittanceInformation, SepaStringSanitizer::MAX_REMITTANCE_INFO_LENGTH);
            }
        }
    }

    /**
     * Rejects an address with a value that is not valid UTF-8 (it would break the XML).
     *
     * @param string $field What the address is (used in the error message)
     * @param array<string, string|null>|null $address The address
     *
     * @throws InvalidArgumentException If a value is not valid UTF-8
     */
    private function assertUtf8Address(string $field, ?array $address): void
    {
        foreach ($address ?? [] as $value) {
            if ($value !== null && preg_match('//u', $value) !== 1) {
                throw new InvalidArgumentException(sprintf('Invalid %s: the text is not valid UTF-8.', $field));
            }
        }
    }

    /**
     * Writes a mandate amendment into MndtRltdInf of the transaction at $index (AmdmntInd, AmdmntInfDtls).
     *
     * @param DOMDocument $dom The DOM document
     * @param DOMXPath $xpath The XPath object (namespace registered as "ns")
     * @param MandateAmendment $amendment The amendment
     * @param int $index The transaction index in file order
     * @param string $namespace The namespace URI
     */
    private function addAmendmentToDom(DOMDocument $dom, DOMXPath $xpath, MandateAmendment $amendment, int $index, string $namespace): void
    {
        $nodes   = $xpath->query('//ns:DrctDbtTxInf/ns:DrctDbtTx/ns:MndtRltdInf');
        $related = $nodes === false ? null : $nodes->item($index);
        if (!$related instanceof DOMElement) {
            throw new InvalidArgumentException(sprintf('No mandate information for the transaction at position %d.', $index));
        }
        $element = static fn (string $name, ?string $value = null): DOMElement => $value === null
            ? $dom->createElementNS($namespace, $name)
            : $dom->createElementNS($namespace, $name, htmlspecialchars($value, ENT_XML1, 'UTF-8'));

        $details = $element('AmdmntInfDtls');
        if ($amendment->getOriginalMandateId() !== null) {
            $details->appendChild($element('OrgnlMndtId', $amendment->getOriginalMandateId()));
        }
        if ($amendment->getOriginalCreditorSchemeId() !== null || $amendment->getOriginalCreditorName() !== null) {
            $scheme = $element('OrgnlCdtrSchmeId');
            if ($amendment->getOriginalCreditorName() !== null) {
                $scheme->appendChild($element('Nm', $this->stringSanitizer->sanitize($amendment->getOriginalCreditorName())));
            }
            if ($amendment->getOriginalCreditorSchemeId() !== null) {
                $schemeName = $element('SchmeNm');
                $schemeName->appendChild($element('Prtry', 'SEPA'));
                $other = $element('Othr');
                $other->appendChild($element('Id', $amendment->getOriginalCreditorSchemeId()));
                $other->appendChild($schemeName);
                $private = $element('PrvtId');
                $private->appendChild($other);
                $id = $element('Id');
                $id->appendChild($private);
                $scheme->appendChild($id);
            }
            $details->appendChild($scheme);
        }
        if ($amendment->getOriginalDebtorIban() !== null) {
            $id = $element('Id');
            $id->appendChild($element('IBAN', $amendment->getOriginalDebtorIban()));
            $account = $element('OrgnlDbtrAcct');
            $account->appendChild($id);
            $details->appendChild($account);
        }
        if ($amendment->isNewDebtorAgent()) {
            $other = $element('Othr');
            $other->appendChild($element('Id', 'SMNDA'));
            $institution = $element('FinInstnId');
            $institution->appendChild($other);
            $agent = $element('OrgnlDbtrAgt');
            $agent->appendChild($institution);
            $details->appendChild($agent);
        }

        // MndtRltdInf: MndtId, DtOfSgntr, AmdmntInd, AmdmntInfDtls, ... (the library writes the first two only)
        $related->appendChild($element('AmdmntInd', 'true'));
        $related->appendChild($details);
    }

    /**
     * Writes every PmtInf/CtrlSum and GrpHdr/CtrlSum from integer cents.
     *
     * @param DOMXPath $xpath The XPath object (namespace registered as "ns")
     * @param list<int> $blockSums Control sum of each PmtInf, in file order
     * @param int $controlSum Control sum of the file
     */
    private function writeControlSums(DOMXPath $xpath, array $blockSums, int $controlSum): void
    {
        $group = $xpath->query('//ns:GrpHdr/ns:CtrlSum');
        if ($group !== false && $group->item(0) instanceof DOMElement) {
            $group->item(0)->nodeValue = Amount::decimalFromCents($controlSum);
        }
        $blocks = $xpath->query('//ns:PmtInf/ns:CtrlSum');
        foreach ($blocks === false ? [] : $blocks as $index => $node) {
            if ($node instanceof DOMElement && isset($blockSums[$index])) {
                $node->nodeValue = Amount::decimalFromCents($blockSums[$index]);
            }
        }
    }

    /**
     * Refuses a non-blank value that the EPC character set reduces to nothing (e.g. only CJK characters): it would
     * give an empty element, or the library's unsanitised text (LF-L3).
     *
     * @throws InvalidArgumentException If nothing is left after sanitising
     */
    private function assertNotEmptyAfterSanitizing(string $field, string $value): void
    {
        if (trim($value) !== '' && trim($this->stringSanitizer->sanitize($value)) === '') {
            throw new InvalidArgumentException(sprintf('Invalid %s "%s": nothing is left after reducing it to the EPC character set (Latin letters, digits and basic punctuation).', $field, $value));
        }
    }

    /**
     * Checks an address against the EPC limits after sanitising: street 70, town 35, postal code 16, ISO country code.
     *
     * @param string $field What the address is (used in the error message)
     * @param array<string, string|null>|null $address The address
     *
     * @throws InvalidArgumentException If a value is too long or the country is not two uppercase letters
     */
    private function assertEpcAddress(string $field, ?array $address): void
    {
        if ($address === null) {
            return;
        }
        foreach (['street' => SepaStringSanitizer::MAX_STREET_LENGTH, 'city' => SepaStringSanitizer::MAX_CITY_LENGTH, 'postalCode' => SepaStringSanitizer::MAX_POSTAL_CODE_LENGTH] as $key => $max) {
            $value = $address[$key] ?? null;
            if ($value !== null) {
                $this->assertNotEmptyAfterSanitizing($field . ' ' . $key, $value);
                $this->stringSanitizer->assertMaxLength($field . ' ' . $key, $this->stringSanitizer->sanitize($value), $max);
            }
        }
        $country = $address['country'] ?? null;
        if ($country !== null && $country !== '' && preg_match('/^[A-Z]{2}$/D', $country) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid %s country "%s": use an ISO 3166 alpha-2 code in uppercase (e.g. "ES").', $field, $country));
        }
    }
}
