<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Generator;

use Digitick\Sepa\DomBuilder\DomBuilderFactory;
use Digitick\Sepa\GroupHeader;
use Digitick\Sepa\PaymentInformation;
use Digitick\Sepa\TransferFile\CustomerCreditTransferFile;
use Nowo\SepaPaymentBundle\Model\Remesa\RemesaData;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;

/**
 * SEPA Credit Transfer generator.
 * Generates SEPA Credit Transfer XML files using Digitick\Sepa library according to ISO 20022 standard.
 * Used for payment remittances where the debtor sends money to the creditor.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class RemesaGenerator
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
     * Generates a SEPA Credit Transfer XML file.
     *
     * @param RemesaData $remesaData The remesa data
     *
     * @throws \InvalidArgumentException If the data is invalid
     *
     * @return string The XML content
     */
    public function generate(RemesaData $remesaData): string
    {
        $this->validateRemesaData($remesaData);

        // Create and configure group header
        $groupHeader = new GroupHeader();
        $groupHeader->setMessageIdentification($remesaData->getMessageId());
        $groupHeader->setInitiatingPartyName($remesaData->getInitiatingPartyName());
        $groupHeader->setCreationDateTime($remesaData->getCreationDate());

        // Create transfer file (pain.001.001.03 format) with group header
        $transferFile = new CustomerCreditTransferFile($groupHeader);

        // Create payment information
        $paymentInformation = new PaymentInformation();
        $paymentInformation->setPaymentInformationIdentification($remesaData->getPaymentInfoId());
        $paymentInformation->setPaymentMethod('TRF');
        $paymentInformation->setBatchBooking($remesaData->isBatchBooking());
        $paymentInformation->setNumberOfTransactions(count($remesaData->getTransactions()));
        $paymentInformation->setControlSum($remesaData->getTotalAmount());
        $paymentInformation->setRequestedExecutionDate($remesaData->getRequestedExecutionDate());

        // Set creditor information
        $paymentInformation->setDebtorName($remesaData->getCreditorName());
        $paymentInformation->setDebtorAccountIBAN($this->ibanValidator->normalize($remesaData->getCreditorIban()));
        if (null !== $remesaData->getCreditorBic()) {
            $paymentInformation->setDebtorAgentBIC($remesaData->getCreditorBic());
        }

        // Add transactions
        foreach ($remesaData->getTransactions() as $transaction) {
            $paymentInformation->addCreditTransferTransaction(
                $transaction->getAmount(),
                $this->ibanValidator->normalize($transaction->getDebtorIban()),
                $transaction->getDebtorName(),
                $transaction->getEndToEndId(),
                $transaction->getDebtorBic(),
                $transaction->getRemittanceInformation()
            );
        }

        $transferFile->addPaymentInformation($paymentInformation);

        // Generate XML
        $domBuilder = DomBuilderFactory::createDomBuilder($transferFile);

        return $domBuilder->asXml();
    }

    /**
     * Validates remesa data.
     *
     * @param RemesaData $remesaData The remesa data to validate
     *
     * @throws \InvalidArgumentException If the data is invalid
     *
     * @return void
     */
    private function validateRemesaData(RemesaData $remesaData): void
    {
        if (!$this->ibanValidator->isValid($remesaData->getCreditorIban())) {
            throw new \InvalidArgumentException('Invalid creditor IBAN: ' . $remesaData->getCreditorIban());
        }

        foreach ($remesaData->getTransactions() as $transaction) {
            if (!$this->ibanValidator->isValid($transaction->getDebtorIban())) {
                throw new \InvalidArgumentException('Invalid debtor IBAN: ' . $transaction->getDebtorIban());
            }
        }
    }
}
