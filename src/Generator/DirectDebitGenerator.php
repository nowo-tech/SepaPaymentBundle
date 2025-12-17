<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Generator;

use Digitick\Sepa\DomBuilder\DomBuilderFactory;
use Digitick\Sepa\GroupHeader;
use Digitick\Sepa\PaymentInformation;
use Digitick\Sepa\TransferFile\CustomerDirectDebitTransferFile;
use Digitick\Sepa\TransferInformation\CustomerDirectDebitTransferInformation;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitData;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitTransaction;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * SEPA Direct Debit generator.
 * Generates SEPA Direct Debit XML files using Digitick\Sepa library according to ISO 20022 standard.
 * Used for collection remittances where the creditor collects money from the debtor based on a SEPA mandate.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class DirectDebitGenerator
{
    public const SERVICE_NAME = 'nowo_sepa_payment.generator.direct_debit_generator';

    /**
     * Constructor.
     *
     * @param IbanValidator $ibanValidator IBAN validator instance
     */
    public function __construct(
        private IbanValidator $ibanValidator
    ) {
    }

    /**
     * Generates a SEPA Direct Debit XML file from array data.
     *
     * @param array<string, mixed> $data The direct debit data in array format
     *
     * @throws \InvalidArgumentException If the data is invalid
     *
     * @return string The XML content
     */
    public function generateFromArray(array $data): string
    {
        $directDebitData = $this->createDirectDebitDataFromArray($data);

        return $this->generate($directDebitData);
    }

    /**
     * Generates a SEPA Direct Debit XML file.
     *
     * @param DirectDebitData $directDebitData The direct debit data
     *
     * @throws \InvalidArgumentException If the data is invalid
     *
     * @return string The XML content
     */
    public function generate(DirectDebitData $directDebitData): string
    {
        $this->validateDirectDebitData($directDebitData);

        // Create and configure group header
        $groupHeader = new GroupHeader(
            $directDebitData->getMessageId(),
            $directDebitData->getInitiatingPartyName()
        );

        // Create transfer file (pain.008.001.02 format) with group header
        $transferFile = new CustomerDirectDebitTransferFile($groupHeader);

        // Create payment information
        $paymentInformation = new PaymentInformation(
            $directDebitData->getPaymentInfoId(),
            $this->ibanValidator->normalize($directDebitData->getCreditorIban()),
            $directDebitData->getCreditorBic() ?? '',
            $directDebitData->getCreditorName(),
            'EUR'
        );
        // Payment method is automatically set based on the transfer file type (CustomerDirectDebitTransferFile)
        $paymentInformation->setDueDate($directDebitData->getDueDate());
        $paymentInformation->setCreditorId($directDebitData->getCreditorId());
        $paymentInformation->setSequenceType($directDebitData->getSequenceType());
        $paymentInformation->setLocalInstrumentCode($directDebitData->getLocalInstrumentCode());

        // Add transactions
        foreach ($directDebitData->getTransactions() as $transaction) {
            $transferInformation = new CustomerDirectDebitTransferInformation(
                (int) round($transaction->getAmount() * 100), // Convert to cents
                $this->ibanValidator->normalize($transaction->getDebtorIban()),
                $transaction->getDebtorName(),
                $transaction->getEndToEndId()
            );

            $transferInformation->setMandateId($transaction->getDebtorMandate());
            $transferInformation->setMandateSignDate($transaction->getDebtorMandateSignDate());

            if (null !== $transaction->getRemittanceInformation()) {
                $transferInformation->setRemittanceInformation($transaction->getRemittanceInformation());
            }

            // Set debtor BIC if available
            if (null !== $transaction->getDebtorBic()) {
                $transferInformation->setBic($transaction->getDebtorBic());
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

        return $domBuilder->asXml();
    }

    /**
     * Creates DirectDebitData from array format.
     *
     * @param array<string, mixed> $data The data in array format
     *
     * @throws \InvalidArgumentException If the data is invalid
     *
     * @return DirectDebitData The DirectDebitData object
     */
    private function createDirectDebitDataFromArray(array $data): DirectDebitData
    {
        // Validate required fields
        $required = ['reference', 'bankAccountOwner', 'paymentInfoId', 'dueDate', 'creditorName', 'creditorIban', 'seqType', 'creditorId', 'localInstrumentCode'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Parse due date
        $dueDate = $data['dueDate'];
        if (is_string($dueDate)) {
            $dueDate = new \DateTime($dueDate);
        } elseif (!$dueDate instanceof \DateTimeInterface) {
            throw new \InvalidArgumentException('dueDate must be a string or DateTimeInterface');
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
            $data['localInstrumentCode']
        );

        if (isset($data['creditorBic'])) {
            $directDebitData->setCreditorBic($data['creditorBic']);
        }

        // Add transactions
        if (isset($data['transactions']) && is_array($data['transactions'])) {
            foreach ($data['transactions'] as $transactionData) {
                $transaction = $this->createTransactionFromArray($transactionData, $dueDate);
                $directDebitData->addTransaction($transaction);
            }
        }

        return $directDebitData;
    }

    /**
     * Creates DirectDebitTransaction from array format.
     *
     * @param array<string, mixed> $transactionData The transaction data
     * @param \DateTimeInterface   $defaultDate     Default date for mandate sign date if not provided
     *
     * @throws \InvalidArgumentException If the data is invalid
     *
     * @return DirectDebitTransaction The DirectDebitTransaction object
     */
    private function createTransactionFromArray(array $transactionData, \DateTimeInterface $defaultDate): DirectDebitTransaction
    {
        $required = ['amount', 'debtorIban', 'debtorName', 'debtorMandate', 'endToEndId'];
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

        // Parse mandate sign date
        $mandateSignDate = $defaultDate;
        if (isset($transactionData['debtorMandateSignDate'])) {
            $signDate = $transactionData['debtorMandateSignDate'];
            if (is_string($signDate)) {
                $mandateSignDate = new \DateTime($signDate);
            } elseif ($signDate instanceof \DateTimeInterface) {
                $mandateSignDate = $signDate;
            }
        }

        $transaction = new DirectDebitTransaction(
            $amount,
            $transactionData['debtorIban'],
            $transactionData['debtorName'],
            $transactionData['debtorMandate'],
            $mandateSignDate,
            $transactionData['endToEndId']
        );

        if (isset($transactionData['remittanceInformation'])) {
            $transaction->setRemittanceInformation($transactionData['remittanceInformation']);
        }

        // Set debtor BIC if available
        if (isset($transactionData['debtorBic'])) {
            $transaction->setDebtorBic($transactionData['debtorBic']);
        }

        // Store any additional fields that are not standard
        $standardFields = ['amount', 'debtorIban', 'debtorName', 'debtorMandate', 'debtorMandateSignDate', 'endToEndId', 'remittanceInformation', 'debtorBic'];
        $additionalData = [];
        foreach ($transactionData as $key => $value) {
            if (!in_array($key, $standardFields, true)) {
                $additionalData[$key] = $value;
            }
        }
        if (!empty($additionalData)) {
            $transaction->setAdditionalData($additionalData);
        }

        return $transaction;
    }

    /**
     * Applies additional data to the transfer information if available.
     * This method can be extended to support additional fields from the Digitick\Sepa library.
     *
     * @param CustomerDirectDebitTransferInformation $transferInformation The transfer information object
     * @param DirectDebitTransaction                 $transaction         The transaction data
     *
     * @return void
     */
    private function applyAdditionalData(
        CustomerDirectDebitTransferInformation $transferInformation,
        DirectDebitTransaction $transaction
    ): void {
        $additionalData = $transaction->getAdditionalData();

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
     * Validates direct debit data.
     *
     * @param DirectDebitData $directDebitData The direct debit data to validate
     *
     * @throws \InvalidArgumentException If the data is invalid
     *
     * @return void
     */
    private function validateDirectDebitData(DirectDebitData $directDebitData): void
    {
        if (!$this->ibanValidator->isValid($directDebitData->getCreditorIban())) {
            throw new \InvalidArgumentException('Invalid creditor IBAN: ' . $directDebitData->getCreditorIban());
        }

        foreach ($directDebitData->getTransactions() as $transaction) {
            if (!$this->ibanValidator->isValid($transaction->getDebtorIban())) {
                throw new \InvalidArgumentException('Invalid debtor IBAN: ' . $transaction->getDebtorIban());
            }
        }
    }
}
