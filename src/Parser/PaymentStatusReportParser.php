<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Parser;

use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Model\Report\PaymentInformationStatus;
use Nowo\SepaPaymentBundle\Model\Report\PaymentStatusReport;
use Nowo\SepaPaymentBundle\Model\Report\TransactionStatus;
use Nowo\SepaPaymentBundle\Parser\Report\IsoReportLoader;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

use function array_map;
use function sprintf;

/**
 * Parses customer payment status reports (pain.002.001.03 and pain.002.001.10): the bank's acceptance or rejection of a
 * pain.008 / pain.001 file, per group, block and transaction, with ISO reason codes. Every document is checked against
 * the official XSD shipped with the bundle, loaded without network access or DTD, and limited in size. Pure parsing:
 * the host matches {@see TransactionStatus::getOriginalEndToEndId()} to its collections. Stateless (worker safe).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
#[AsAlias(id: PaymentStatusReportParserInterface::class, public: true)]
final class PaymentStatusReportParser implements PaymentStatusReportParserInterface
{
    public const SERVICE_NAME = 'nowo_sepa_payment.parser.payment_status_report_parser';

    /** Supported versions. */
    public const VERSIONS = ['pain.002.001.03', 'pain.002.001.10'];

    /** Default size limit (bundle config `reports.max_size`). */
    public const DEFAULT_MAX_SIZE = 20_971_520;

    /**
     * @param int $maxSize Largest accepted document in bytes (bundle config `reports.max_size`)
     */
    public function __construct(
        private readonly int $maxSize = self::DEFAULT_MAX_SIZE,
    ) {
        if ($maxSize < 1) {
            throw new InvalidArgumentException(sprintf('Invalid report size limit %d.', $maxSize));
        }
    }

    public function parse(string $xml): PaymentStatusReport
    {
        [$xpath, $version] = IsoReportLoader::load($xml, self::VERSIONS, $this->maxSize);
        $p                 = IsoReportLoader::PREFIX . ':';
        $root              = '/' . $p . 'Document/' . $p . 'CstmrPmtStsRpt/';
        $group             = IsoReportLoader::elements($xpath, $root . $p . 'OrgnlGrpInfAndSts')[0]
            ?? throw new InvalidArgumentException('The report has no OrgnlGrpInfAndSts.');

        return new PaymentStatusReport(
            $version,
            (string) IsoReportLoader::text($xpath, $root . $p . 'GrpHdr/' . $p . 'MsgId'),
            IsoReportLoader::date(IsoReportLoader::text($xpath, $root . $p . 'GrpHdr/' . $p . 'CreDtTm')),
            (string) IsoReportLoader::text($xpath, $p . 'OrgnlMsgId', $group),
            (string) IsoReportLoader::text($xpath, $p . 'OrgnlMsgNmId', $group),
            IsoReportLoader::text($xpath, $p . 'GrpSts', $group),
            IsoReportLoader::reasons($xpath, $p . 'StsRsnInf', $group),
            array_map(
                fn (DOMElement $block): PaymentInformationStatus => $this->paymentInformation($xpath, $block),
                IsoReportLoader::elements($xpath, $root . $p . 'OrgnlPmtInfAndSts'),
            ),
        );
    }

    private function paymentInformation(DOMXPath $xpath, DOMElement $block): PaymentInformationStatus
    {
        $p = IsoReportLoader::PREFIX . ':';

        return new PaymentInformationStatus(
            (string) IsoReportLoader::text($xpath, $p . 'OrgnlPmtInfId', $block),
            IsoReportLoader::text($xpath, $p . 'PmtInfSts', $block),
            IsoReportLoader::reasons($xpath, $p . 'StsRsnInf', $block),
            array_map(
                fn (DOMElement $transaction): TransactionStatus => $this->transaction($xpath, $transaction),
                IsoReportLoader::elements($xpath, $p . 'TxInfAndSts', $block),
            ),
        );
    }

    private function transaction(DOMXPath $xpath, DOMElement $transaction): TransactionStatus
    {
        $p      = IsoReportLoader::PREFIX . ':';
        $amount = IsoReportLoader::elements($xpath, $p . 'OrgnlTxRef/' . $p . 'Amt/' . $p . 'InstdAmt', $transaction)[0] ?? null;

        return new TransactionStatus(
            IsoReportLoader::text($xpath, $p . 'StsId', $transaction),
            IsoReportLoader::text($xpath, $p . 'OrgnlInstrId', $transaction),
            IsoReportLoader::text($xpath, $p . 'OrgnlEndToEndId', $transaction),
            IsoReportLoader::text($xpath, $p . 'TxSts', $transaction),
            IsoReportLoader::reasons($xpath, $p . 'StsRsnInf', $transaction),
            IsoReportLoader::cents($amount?->textContent),
            $amount?->getAttribute('Ccy'),
            IsoReportLoader::date(IsoReportLoader::text($xpath, $p . 'OrgnlTxRef/' . $p . 'ReqdColltnDt', $transaction)),
            IsoReportLoader::text($xpath, $p . 'OrgnlTxRef/' . $p . 'MndtRltdInf/' . $p . 'MndtId', $transaction),
        );
    }
}
