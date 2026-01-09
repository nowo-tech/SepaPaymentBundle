<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Generator;

use Deprecated;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\CreditTransferData;
use Nowo\SepaPaymentBundle\Model\Remesa\RemesaData;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Nowo\SepaPaymentBundle\Validator\XsdValidator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\HttpFoundation\Response;

/**
 * SEPA Credit Transfer generator (deprecated).
 *
 * @deprecated Since 1.1.0, use CreditTransferGenerator instead. This class will be removed in 2.0.0.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class RemesaGenerator
{
    public const SERVICE_NAME = 'nowo_sepa_payment.generator.remesa_generator';

    /**
     * Credit transfer generator instance.
     *
     * @var CreditTransferGenerator
     */
    private CreditTransferGenerator $generator;

    /**
     * Constructor.
     *
     * @param IbanValidator     $ibanValidator IBAN validator instance
     * @param XsdValidator|null $xsdValidator  Optional XSD validator instance
     * @param bool              $validateXsd   Whether to validate XML against XSD schema
     */
    public function __construct(IbanValidator $ibanValidator, ?XsdValidator $xsdValidator = null, bool $validateXsd = false)
    {
        $this->generator = new CreditTransferGenerator($ibanValidator, $xsdValidator, $validateXsd);
    }

    /**
     * Generates SEPA Credit Transfer XML from array format.
     *
     * @deprecated Since 1.1.0, use CreditTransferGenerator::generateFromArray() instead
     *
     * @param array<string, mixed> $data The data array
     *
     * @return string The generated XML
     */
    #[Deprecated(message: 'Use CreditTransferGenerator::generateFromArray() instead', since: '1.1.0')]
    public function generateFromArray(array $data): string
    {
        @trigger_error('RemesaGenerator is deprecated since 1.1.0. Use CreditTransferGenerator instead.', \E_USER_DEPRECATED);

        return $this->generator->generateFromArray($data);
    }

    /**
     * Generates SEPA Credit Transfer XML.
     *
     * @deprecated Since 1.1.0, use CreditTransferGenerator::generate() instead
     *
     * @param RemesaData|CreditTransferData $remesaData The credit transfer data
     *
     * @return string The generated XML
     */
    #[Deprecated(message: 'Use CreditTransferGenerator::generate() instead', since: '1.1.0')]
    public function generate(RemesaData|CreditTransferData $remesaData): string
    {
        @trigger_error('RemesaGenerator is deprecated since 1.1.0. Use CreditTransferGenerator instead.', \E_USER_DEPRECATED);

        // Convert RemesaData to CreditTransferData if needed
        if ($remesaData instanceof RemesaData) {
            $creditTransferData = $this->convertRemesaDataToCreditTransferData($remesaData);
        } else {
            $creditTransferData = $remesaData;
        }

        return $this->generator->generate($creditTransferData);
    }

    /**
     * Creates an HTTP response with the XML content.
     *
     * @deprecated Since 1.1.0, use CreditTransferGenerator::createResponse() instead
     *
     * @param string $xml      The XML content
     * @param string $filename The filename for the download
     *
     * @return Response The HTTP response
     */
    #[Deprecated(message: 'Use CreditTransferGenerator::createResponse() instead', since: '1.1.0')]
    public function createResponse(string $xml, string $filename = 'credit-transfer.xml'): Response
    {
        @trigger_error('RemesaGenerator is deprecated since 1.1.0. Use CreditTransferGenerator instead.', \E_USER_DEPRECATED);

        return $this->generator->createResponse($xml, $filename);
    }

    /**
     * Converts RemesaData to CreditTransferData.
     *
     * @param RemesaData $remesaData The remesa data
     *
     * @return CreditTransferData The credit transfer data
     */
    private function convertRemesaDataToCreditTransferData(RemesaData $remesaData): CreditTransferData
    {
        $creditTransferData = new CreditTransferData(
            $remesaData->getMessageId(),
            $remesaData->getCreationDate(),
            $remesaData->getInitiatingPartyName(),
            $remesaData->getPaymentInfoId(),
            $remesaData->getCreditorIban(),
            $remesaData->getCreditorName(),
            $remesaData->getRequestedExecutionDate()
        );

        if ($remesaData->getCreditorBic() !== null) {
            $creditTransferData->setCreditorBic($remesaData->getCreditorBic());
        }

        $creditTransferData->setBatchBooking($remesaData->isBatchBooking());

        if ($remesaData->getCreditorAddress() !== null) {
            $creditTransferData->setCreditorAddressFromArray($remesaData->getCreditorAddress());
        }

        foreach ($remesaData->getTransactions() as $transaction) {
            $creditTransferTransaction = new \Nowo\SepaPaymentBundle\Model\CreditTransfer\Transaction(
                $transaction->getEndToEndId(),
                $transaction->getAmount(),
                $transaction->getCurrency(),
                $transaction->getDebtorIban(),
                $transaction->getDebtorName()
            );

            if ($transaction->getDebtorBic() !== null) {
                $creditTransferTransaction->setDebtorBic($transaction->getDebtorBic());
            }

            if ($transaction->getRemittanceInformation() !== null) {
                $creditTransferTransaction->setRemittanceInformation($transaction->getRemittanceInformation());
            }

            if ($transaction->getDebtorAddress() !== null) {
                $creditTransferTransaction->setDebtorAddressFromArray($transaction->getDebtorAddress());
            }

            $creditTransferData->addTransaction($creditTransferTransaction);
        }

        return $creditTransferData;
    }
}
