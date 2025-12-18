<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Generator;

use Digitick\Sepa\DomBuilder\DomBuilderFactory;
use Digitick\Sepa\GroupHeader;
use Digitick\Sepa\PaymentInformation;
use Digitick\Sepa\TransferFile\CustomerCreditTransferFile;
use Digitick\Sepa\TransferInformation\CustomerCreditTransferInformation;
use Nowo\SepaPaymentBundle\Model\Remesa\RemesaData;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
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
class RemesaGenerator
{
    public const SERVICE_NAME = 'nowo_sepa_payment.generator.remesa_generator';

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
        $groupHeader = new GroupHeader(
            $remesaData->getMessageId(),
            $remesaData->getInitiatingPartyName()
        );

        // Create transfer file (pain.001.001.03 format) with group header
        $transferFile = new CustomerCreditTransferFile($groupHeader);

        // Create payment information
        $paymentInformation = new PaymentInformation(
            $remesaData->getPaymentInfoId(),
            $this->ibanValidator->normalize($remesaData->getCreditorIban()),
            $remesaData->getCreditorBic() ?? '',
            $remesaData->getCreditorName(),
            'EUR'
        );
        // Payment method is automatically set based on the transfer file type (CustomerCreditTransferFile)
        $paymentInformation->setBatchBooking($remesaData->isBatchBooking());
        $paymentInformation->setDueDate($remesaData->getRequestedExecutionDate());

        // Add transactions
        foreach ($remesaData->getTransactions() as $transaction) {
            $transferInformation = new CustomerCreditTransferInformation(
                (int) round($transaction->getAmount() * 100), // Convert to cents
                $this->ibanValidator->normalize($transaction->getDebtorIban()),
                $transaction->getDebtorName(),
                $transaction->getEndToEndId()
            );

            if (null !== $transaction->getDebtorBic()) {
                $transferInformation->setBic($transaction->getDebtorBic());
            }

            if (null !== $transaction->getRemittanceInformation()) {
                $transferInformation->setRemittanceInformation($transaction->getRemittanceInformation());
            }

            $paymentInformation->addTransfer($transferInformation);
        }

        $transferFile->addPaymentInformation($paymentInformation);

        // Generate XML
        $domBuilder = DomBuilderFactory::createDomBuilder($transferFile);

        return $domBuilder->asXml();
    }

    /**
     * Creates an HTTP Response with XML content for download.
     *
     * @param string $xmlData  The XML content
     * @param string $filename The filename for the download (e.g., "remesa-pago.xml")
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
