<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Parser;

use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Model\Report\AccountNotification;
use Nowo\SepaPaymentBundle\Model\Report\DebitCreditNotification;
use Nowo\SepaPaymentBundle\Model\Report\NotificationEntry;
use Nowo\SepaPaymentBundle\Model\Report\NotificationTransaction;
use Nowo\SepaPaymentBundle\Parser\Report\IsoReportLoader;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

use function array_filter;
use function array_map;
use function implode;
use function sprintf;

/**
 * Parses bank-to-customer debit / credit notifications (camt.054.001.02 and camt.054.001.08): booked collections,
 * returns and refunds, with their references and ISO return reasons. Every document is checked against the official
 * XSD shipped with the bundle, loaded without network access or DTD, and limited in size. Pure parsing: the host
 * matches {@see NotificationTransaction::getEndToEndId()} to its collections. Stateless (worker safe).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
#[AsAlias(id: DebitCreditNotificationParserInterface::class, public: true)]
final class DebitCreditNotificationParser implements DebitCreditNotificationParserInterface
{
    public const SERVICE_NAME = 'nowo_sepa_payment.parser.debit_credit_notification_parser';

    /** Supported versions. */
    public const VERSIONS = ['camt.054.001.02', 'camt.054.001.08'];

    /**
     * @param int $maxSize Largest accepted document in bytes (bundle config `reports.max_size`)
     */
    public function __construct(
        private readonly int $maxSize = PaymentStatusReportParser::DEFAULT_MAX_SIZE,
    ) {
        if ($maxSize < 1) {
            throw new InvalidArgumentException(sprintf('Invalid report size limit %d.', $maxSize));
        }
    }

    public function parse(string $xml): DebitCreditNotification
    {
        [$xpath, $version] = IsoReportLoader::load($xml, self::VERSIONS, $this->maxSize);
        $p                 = IsoReportLoader::PREFIX . ':';
        $root              = '/' . $p . 'Document/' . $p . 'BkToCstmrDbtCdtNtfctn/';

        return new DebitCreditNotification(
            $version,
            (string) IsoReportLoader::text($xpath, $root . $p . 'GrpHdr/' . $p . 'MsgId'),
            IsoReportLoader::date(IsoReportLoader::text($xpath, $root . $p . 'GrpHdr/' . $p . 'CreDtTm')),
            array_map(
                fn (DOMElement $notification): AccountNotification => new AccountNotification(
                    (string) IsoReportLoader::text($xpath, $p . 'Id', $notification),
                    IsoReportLoader::date(IsoReportLoader::text($xpath, $p . 'CreDtTm', $notification)),
                    IsoReportLoader::text($xpath, $p . 'Acct/' . $p . 'Id/' . $p . 'IBAN', $notification),
                    array_map(
                        fn (DOMElement $entry): NotificationEntry => $this->entry($xpath, $entry),
                        IsoReportLoader::elements($xpath, $p . 'Ntry', $notification),
                    ),
                ),
                IsoReportLoader::elements($xpath, $root . $p . 'Ntfctn'),
            ),
        );
    }

    private function entry(DOMXPath $xpath, DOMElement $entry): NotificationEntry
    {
        $p      = IsoReportLoader::PREFIX . ':';
        $amount = IsoReportLoader::elements($xpath, $p . 'Amt', $entry)[0];

        return new NotificationEntry(
            IsoReportLoader::text($xpath, $p . 'NtryRef', $entry),
            (int) IsoReportLoader::cents($amount->textContent),
            $amount->getAttribute('Ccy'),
            (string) IsoReportLoader::text($xpath, $p . 'CdtDbtInd', $entry),
            IsoReportLoader::text($xpath, $p . 'RvslInd', $entry) === 'true',
            // .02: <Sts>BOOK</Sts>; .08: <Sts><Cd>BOOK</Cd></Sts> (or Prtry)
            IsoReportLoader::text($xpath, $p . 'Sts/' . $p . 'Cd | ' . $p . 'Sts/' . $p . 'Prtry', $entry) ?? IsoReportLoader::text($xpath, $p . 'Sts', $entry),
            IsoReportLoader::date(IsoReportLoader::text($xpath, $p . 'BookgDt/' . $p . 'Dt | ' . $p . 'BookgDt/' . $p . 'DtTm', $entry)),
            IsoReportLoader::date(IsoReportLoader::text($xpath, $p . 'ValDt/' . $p . 'Dt | ' . $p . 'ValDt/' . $p . 'DtTm', $entry)),
            IsoReportLoader::text($xpath, $p . 'AcctSvcrRef', $entry),
            $this->bankTransactionCode($xpath, $entry),
            array_map(
                fn (DOMElement $transaction): NotificationTransaction => $this->transaction($xpath, $transaction),
                IsoReportLoader::elements($xpath, $p . 'NtryDtls/' . $p . 'TxDtls', $entry),
            ),
        );
    }

    private function bankTransactionCode(DOMXPath $xpath, DOMElement $entry): ?string
    {
        $p      = IsoReportLoader::PREFIX . ':';
        $domain = IsoReportLoader::text($xpath, $p . 'BkTxCd/' . $p . 'Domn/' . $p . 'Cd', $entry);
        if ($domain !== null) {
            return implode('/', array_filter([
                $domain,
                IsoReportLoader::text($xpath, $p . 'BkTxCd/' . $p . 'Domn/' . $p . 'Fmly/' . $p . 'Cd', $entry),
                IsoReportLoader::text($xpath, $p . 'BkTxCd/' . $p . 'Domn/' . $p . 'Fmly/' . $p . 'SubFmlyCd', $entry),
            ], static fn (?string $part): bool => $part !== null));
        }

        return IsoReportLoader::text($xpath, $p . 'BkTxCd/' . $p . 'Prtry/' . $p . 'Cd', $entry);
    }

    private function transaction(DOMXPath $xpath, DOMElement $transaction): NotificationTransaction
    {
        $p = IsoReportLoader::PREFIX . ':';
        // .08: TxDtls/Amt; .02 (and .08 without Amt): AmtDtls/TxAmt/Amt, then AmtDtls/InstdAmt/Amt
        $amount = IsoReportLoader::elements($xpath, $p . 'Amt', $transaction)[0]
            ?? IsoReportLoader::elements($xpath, $p . 'AmtDtls/' . $p . 'TxAmt/' . $p . 'Amt', $transaction)[0]
            ?? IsoReportLoader::elements($xpath, $p . 'AmtDtls/' . $p . 'InstdAmt/' . $p . 'Amt', $transaction)[0]
            ?? null;
        $return = IsoReportLoader::reasons($xpath, $p . 'RtrInf', $transaction)[0] ?? null;
        // .02: Dbtr/Nm; .08: Dbtr/Pty/Nm (Party40Choice)
        $party = static fn (string $role): ?string => IsoReportLoader::text($xpath, $p . 'RltdPties/' . $p . $role . '/' . $p . 'Pty/' . $p . 'Nm | ' . $p . 'RltdPties/' . $p . $role . '/' . $p . 'Nm', $transaction);

        return new NotificationTransaction(
            IsoReportLoader::text($xpath, $p . 'Refs/' . $p . 'EndToEndId', $transaction),
            IsoReportLoader::text($xpath, $p . 'Refs/' . $p . 'InstrId', $transaction),
            IsoReportLoader::text($xpath, $p . 'Refs/' . $p . 'PmtInfId', $transaction),
            IsoReportLoader::text($xpath, $p . 'Refs/' . $p . 'MndtId', $transaction),
            IsoReportLoader::text($xpath, $p . 'Refs/' . $p . 'AcctSvcrRef', $transaction),
            IsoReportLoader::cents($amount?->textContent),
            $amount?->getAttribute('Ccy'),
            IsoReportLoader::text($xpath, $p . 'CdtDbtInd', $transaction),
            $party('Dbtr'),
            IsoReportLoader::text($xpath, $p . 'RltdPties/' . $p . 'DbtrAcct/' . $p . 'Id/' . $p . 'IBAN', $transaction),
            $party('Cdtr'),
            IsoReportLoader::text($xpath, $p . 'RltdPties/' . $p . 'CdtrAcct/' . $p . 'Id/' . $p . 'IBAN', $transaction),
            IsoReportLoader::texts($xpath, $p . 'RmtInf/' . $p . 'Ustrd', $transaction),
            $return,
        );
    }
}
