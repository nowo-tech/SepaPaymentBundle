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

/**
 * SEPA Direct Debit generator.
 * Generates SEPA Direct Debit XML files using Digitick\Sepa library according to ISO 20022 standard.
 * Used for collection remittances where the creditor collects money from the debtor based on a SEPA mandate.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class DirectDebitGenerator
{
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
        $groupHeader = new GroupHeader();
        $groupHeader->setMessageIdentification($directDebitData->getMessageId());
        $groupHeader->setInitiatingPartyName($directDebitData->getInitiatingPartyName());
        $groupHeader->setCreationDateTime(new \DateTime());

        // Create transfer file (pain.008.001.02 format) with group header
        $transferFile = new CustomerDirectDebitTransferFile($groupHeader);

        // Create payment information
        $paymentInformation = new PaymentInformation();
        $paymentInformation->setPaymentInformationIdentification($directDebitData->getPaymentInfoId());
        $paymentInformation->setPaymentMethod('DD');
        $paymentInformation->setNumberOfTransactions(count($directDebitData->getTransactions()));
        $paymentInformation->setControlSum($directDebitData->getTotalAmount());
        $paymentInformation->setRequestedCollectionDate($directDebitData->getDueDate());

        // Set creditor information
        $paymentInformation->setCreditorName($directDebitData->getCreditorName());
        $paymentInformation->setCreditorAccountIBAN($this->ibanValidator->normalize($directDebitData->getCreditorIban()));
        if (null !== $directDebitData->getCreditorBic()) {
            $paymentInformation->setCreditorAgentBIC($directDebitData->getCreditorBic());
        }
        $paymentInformation->setCreditorSchemeIdentification($directDebitData->getCreditorId());
        $paymentInformation->setSequenceType($directDebitData->getSequenceType());
        $paymentInformation->setLocalInstrumentCode($directDebitData->getLocalInstrumentCode());

        // Add transactions
        foreach ($directDebitData->getTransactions() as $transaction) {
            $transferInformation = new CustomerDirectDebitTransferInformation();
            $transferInformation->setInstructedAmount($transaction->getAmount());
            $transferInformation->setDebtorName($transaction->getDebtorName());
            $transferInformation->setDebtorAccountIBAN($this->ibanValidator->normalize($transaction->getDebtorIban()));
            $transferInformation->setMandateIdentification($transaction->getDebtorMandate());
            $transferInformation->setDateOfSignature($transaction->getDebtorMandateSignDate());
            $transferInformation->setEndToEndIdentification($transaction->getEndToEndId());

            if (null !== $transaction->getRemittanceInformation()) {
                $transferInformation->setRemittanceInformation($transaction->getRemittanceInformation());
            }

            $paymentInformation->addTransferInformation($transferInformation);
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

        return $transaction;
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
