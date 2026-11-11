<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Parser;

use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Model\ReasonCode;
use Nowo\SepaPaymentBundle\Parser\DebitCreditNotificationParser;
use Nowo\SepaPaymentBundle\Parser\PaymentStatusReportParser;
use Nowo\SepaPaymentBundle\Parser\Report\IsoReportLoader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function array_slice;
use function count;
use function dirname;
use function file_get_contents;
use function memory_get_usage;
use function str_repeat;
use function str_replace;
use function strlen;

/**
 * P6: pain.002 (.03, .10) and camt.054 (.02, .08) parsers. The fixtures are shaped like real bank files and validate
 * against the official ISO 20022 XSDs shipped in src/Resources/schemas (the parsers validate every document).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class BankReportParsersTest extends TestCase
{
    public function testPain00200103(): void
    {
        $report = (new PaymentStatusReportParser())->parse(self::fixture('pain.002.001.03-rejections.xml'));

        self::assertSame('pain.002.001.03', $report->getVersion());
        self::assertSame('STS-20261202-0001', $report->getMessageId());
        self::assertSame('2026-12-02T08:15:00', $report->getCreationDateTime()?->format('Y-m-d\TH:i:s'));
        self::assertSame('REM-2026-11', $report->getOriginalMessageId());
        self::assertSame('pain.008.001.02', $report->getOriginalMessageNameId());
        self::assertSame('PART', $report->getGroupStatus());
        self::assertSame([], $report->getGroupReasons());
        self::assertCount(1, $report->getPaymentInformationStatuses());
        self::assertSame('REM-2026-11-1', $report->getPaymentInformationStatuses()[0]->getOriginalPaymentInformationId());
        self::assertSame('PART', $report->getPaymentInformationStatuses()[0]->getStatus());

        [$first, $second] = $report->getTransactionStatuses();
        self::assertSame('STS-1', $first->getStatusId());
        self::assertSame('INSTR-1', $first->getOriginalInstructionId());
        self::assertSame('E2E-INV-1001', $first->getOriginalEndToEndId());
        self::assertTrue($first->isRejected());
        self::assertSame(ReasonCode::AM04, $first->getReason()?->getReasonCode());
        self::assertSame('Banco Ejemplo', $first->getReason()?->getOriginatorName());
        self::assertSame(['Saldo insuficiente'], $first->getReason()?->getAdditionalInformation());
        self::assertSame(4235, $first->getAmountCents());
        self::assertSame('EUR', $first->getCurrency());
        self::assertSame('2026-12-01', $first->getRequestedCollectionDate()?->format('Y-m-d'));
        self::assertSame('MNDT-0001', $first->getMandateId());

        self::assertNull($second->getReason()?->getCode());
        self::assertSame('BANK-123', $second->getReason()?->getProprietaryCode());
        self::assertNull($second->getReason()?->getReasonCode());
        self::assertNull($second->getAmountCents());
        self::assertNull($second->getCurrency());
    }

    public function testPain00200110(): void
    {
        $report = (new PaymentStatusReportParser())->parse(self::fixture('pain.002.001.10-partially-accepted.xml'));

        self::assertSame('pain.002.001.10', $report->getVersion());
        self::assertSame('pain.008.001.08', $report->getOriginalMessageNameId());
        self::assertSame(ReasonCode::FF01, $report->getGroupReasons()[0]->getReasonCode());
        self::assertSame(['ACCP', 'PART'], [$report->getPaymentInformationStatuses()[0]->getStatus(), $report->getPaymentInformationStatuses()[1]->getStatus()]);
        self::assertSame([], $report->getPaymentInformationStatuses()[0]->getTransactions());

        $transaction = $report->getTransactionStatuses()[0];
        self::assertSame('E2E-INV-2002', $transaction->getOriginalEndToEndId());
        self::assertSame(ReasonCode::MD01, $transaction->getReason()?->getReasonCode());
        self::assertSame(125050, $transaction->getAmountCents(), '1250.5 EUR');
        self::assertSame('MNDT-0002', $transaction->getMandateId());
        self::assertNull($transaction->getStatusId());
    }

    public function testCamt05400102(): void
    {
        $notification = (new DebitCreditNotificationParser())->parse(self::fixture('camt.054.001.02-returns.xml'));

        self::assertSame('camt.054.001.02', $notification->getVersion());
        self::assertSame('CAMT054-20261206-01', $notification->getMessageId());
        $account = $notification->getNotifications()[0];
        self::assertSame('NTF-20261206-01', $account->getId());
        self::assertSame('ES9121000418450200051332', $account->getAccountIban());

        $entry = $account->getEntries()[0];
        self::assertSame('NTRY-1', $entry->getEntryReference());
        self::assertSame(4235, $entry->getAmountCents());
        self::assertSame('EUR', $entry->getCurrency());
        self::assertSame('DBIT', $entry->getCreditDebitIndicator());
        self::assertFalse($entry->isReversal());
        self::assertSame('BOOK', $entry->getStatus());
        self::assertSame('2026-12-06', $entry->getBookingDate()?->format('Y-m-d'));
        self::assertSame('2026-12-06', $entry->getValueDate()?->format('Y-m-d'));
        self::assertSame('BANKREF-0001', $entry->getAccountServicerReference());
        self::assertSame('PMNT/RDDT/UPDD', $entry->getBankTransactionCode());

        $transaction = $notification->getTransactions()[0];
        self::assertSame('E2E-INV-1001', $transaction->getEndToEndId());
        self::assertSame('REM-2026-11-1', $transaction->getPaymentInformationId());
        self::assertSame('MNDT-0001', $transaction->getMandateId());
        self::assertSame('BANKREF-0001-1', $transaction->getAccountServicerReference());
        self::assertNull($transaction->getInstructionId());
        self::assertSame(4235, $transaction->getAmountCents());
        self::assertSame('DBIT', $transaction->getCreditDebitIndicator(), 'the entry indicator (.02 has none per transaction)');
        self::assertSame('Maria Garcia Lopez', $transaction->getDebtorName());
        self::assertSame('ES7921000813610123456789', $transaction->getDebtorIban());
        self::assertSame('Nowo Creditor SL', $transaction->getCreditorName());
        self::assertNull($transaction->getCreditorIban());
        self::assertSame(['Invoice 2026-1001'], $transaction->getRemittanceInformation());
        self::assertTrue($transaction->isReturn());
        self::assertSame(ReasonCode::MD06, $transaction->getReturnReason()?->getReasonCode());
        self::assertSame(['Refund requested by the debtor'], $transaction->getReturnReason()?->getAdditionalInformation());
    }

    public function testCamt05400108(): void
    {
        $notification = (new DebitCreditNotificationParser())->parse(self::fixture('camt.054.001.08-batch.xml'));

        self::assertSame('camt.054.001.08', $notification->getVersion());
        [$credit, $reversal] = $notification->getNotifications()[0]->getEntries();
        self::assertSame(126050, $credit->getAmountCents());
        self::assertSame('CRDT', $credit->getCreditDebitIndicator());
        self::assertSame('BOOK', $credit->getStatus());
        self::assertSame('PMNT/IDDT/ESDD', $credit->getBankTransactionCode());
        self::assertSame('2026-12-05T00:00:00+01:00', $credit->getValueDate()?->format('c'));
        self::assertCount(2, $credit->getTransactions());
        self::assertSame('Jan Jansen', $credit->getTransactions()[0]->getDebtorName());
        self::assertSame('NL91ABNA0417164300', $credit->getTransactions()[0]->getDebtorIban());
        self::assertSame('CRDT', $credit->getTransactions()[0]->getCreditDebitIndicator());
        self::assertFalse($credit->getTransactions()[0]->isReturn());
        self::assertSame(125050, $credit->getTransactions()[1]->getAmountCents());
        self::assertSame(['Invoice 2026-2002', 'Second line'], $credit->getTransactions()[1]->getRemittanceInformation());

        self::assertTrue($reversal->isReversal());
        self::assertSame('REVERSED', $reversal->getStatus());
        self::assertSame('RET-DD', $reversal->getBankTransactionCode());
        self::assertNull($reversal->getEntryReference());
        self::assertNull($reversal->getBookingDate());
        self::assertSame(ReasonCode::AC04, $reversal->getTransactions()[0]->getReturnReason()?->getReasonCode());
        self::assertCount(3, $notification->getTransactions());
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function invalidProvider(): iterable
    {
        $pain = self::fixture('pain.002.001.03-rejections.xml');

        yield 'empty' => ['pain', '  ', 'empty'];
        yield 'malformed' => ['pain', '<Document><x>', 'not well-formed'];
        yield 'DTD / XXE' => ['pain', '<?xml version="1.0"?><!DOCTYPE d [<!ENTITY x SYSTEM "file:///etc/passwd">]><Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.002.001.03">&x;</Document>', 'document type declaration'];
        yield 'other message' => ['pain', self::fixture('camt.054.001.02-returns.xml'), 'Unsupported report namespace'];
        yield 'other message for camt' => ['camt', $pain, 'Unsupported report namespace'];
        yield 'unsupported version' => ['pain', str_replace('pain.002.001.03', 'pain.002.001.05', $pain), 'Unsupported report namespace'];
        yield 'invalid against XSD' => ['pain', str_replace('<GrpSts>PART</GrpSts>', '<GrpSts>WHATEVER</GrpSts>', $pain), 'not valid against the pain.002.001.03 schema'];
        yield 'no namespace' => ['camt', '<Document/>', 'Unsupported report namespace'];
    }

    #[DataProvider('invalidProvider')]
    public function testInvalidDocumentsAreRejected(string $type, string $xml, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $type === 'pain' ? (new PaymentStatusReportParser())->parse($xml) : (new DebitCreditNotificationParser())->parse($xml);
    }

    public function testSizeCap(): void
    {
        $xml = self::fixture('camt.054.001.02-returns.xml');
        self::assertSame('camt.054.001.02', (new DebitCreditNotificationParser(strlen($xml)))->parse($xml)->getVersion());

        $this->expectExceptionMessage('too large');
        (new DebitCreditNotificationParser(strlen($xml) - 1))->parse($xml);
    }

    public function testSizeCapAppliesToPain002AndBadLimitsAreRefused(): void
    {
        try {
            (new PaymentStatusReportParser(100))->parse(str_repeat(' ', 101));
            self::fail('expected the size cap');
        } catch (InvalidArgumentException $e) {
            self::assertStringContainsString('too large', $e->getMessage());
        }

        $this->expectExceptionMessage('Invalid report size limit 0');
        new PaymentStatusReportParser(0);
    }

    public function testDebitCreditParserRefusesBadLimit(): void
    {
        $this->expectExceptionMessage('Invalid report size limit -1');
        new DebitCreditNotificationParser(-1);
    }

    /**
     * Worker mode: one instance parses many reports of two "tenants" without state or memory growth, and a failing
     * document does not affect the next one (libxml error state restored).
     */
    public function testRepeatedParsingKeepsNoState(): void
    {
        $pain     = new PaymentStatusReportParser();
        $camt     = new DebitCreditNotificationParser();
        $a        = self::fixture('pain.002.001.03-rejections.xml');
        $b        = self::fixture('pain.002.001.10-partially-accepted.xml');
        $c        = self::fixture('camt.054.001.08-batch.xml');
        $baseline = 0;
        for ($i = 0; $i < 1000; ++$i) {
            self::assertSame('E2E-INV-1001', $pain->parse($a)->getTransactionStatuses()[0]->getOriginalEndToEndId());
            self::assertSame('E2E-INV-2002', $pain->parse($b)->getTransactionStatuses()[0]->getOriginalEndToEndId());
            self::assertCount(3, $camt->parse($c)->getTransactions());
            try {
                $pain->parse('<broken');
            } catch (InvalidArgumentException) {
            }
            if ($i === 50) {
                $baseline = memory_get_usage();
            }
        }
        self::assertLessThan(512 * 1024, memory_get_usage() - $baseline);
        self::assertSame([], libxml_get_errors());
    }

    /**
     * Every schema-valid xs:decimal form of an amount is read (CodeRabbit on #48).
     *
     * @return iterable<string, array{string, int}>
     */
    public static function amountFormProvider(): iterable
    {
        yield 'plus sign' => ['+42.35', 4235];
        yield 'no integer digits' => ['.5', 50];
        yield 'no fraction digits' => ['42.', 4200];
        yield 'leading zeros beyond 15 digits' => ['0000000000000042.35', 4235];
        yield 'negative zero' => ['-0.00', 0];
        yield 'trailing zeros up to 5 decimals' => ['42.35000', 4235];
        yield 'integer' => ['42', 4200];
    }

    #[DataProvider('amountFormProvider')]
    public function testAmountForms(string $amount, int $cents): void
    {
        $xml          = str_replace('>42.35<', '>' . $amount . '<', self::fixture('camt.054.001.02-returns.xml'));
        $notification = (new DebitCreditNotificationParser())->parse($xml);

        self::assertSame($cents, $notification->getNotifications()[0]->getEntries()[0]->getAmountCents());
        self::assertSame($cents, $notification->getTransactions()[0]->getAmountCents());

        $report = (new PaymentStatusReportParser())->parse(str_replace('>42.35<', '>' . $amount . '<', self::fixture('pain.002.001.03-rejections.xml')));
        self::assertSame($cents, $report->getTransactionStatuses()[0]->getAmountCents());
    }

    /**
     * Schema-valid amounts the bundle cannot represent get a clear error.
     *
     * @return iterable<string, array{string, string}>
     */
    public static function unrepresentableAmountProvider(): iterable
    {
        yield 'sub-cent digits' => ['42.351', 'digits beyond the cent'];
        yield 'five decimals' => ['42.35001', 'digits beyond the cent'];
        yield 'more than 15 integer digits' => ['1000000000000000', 'more than 15 integer digits'];
    }

    #[DataProvider('unrepresentableAmountProvider')]
    public function testUnrepresentableAmountsAreRejected(string $amount, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        (new DebitCreditNotificationParser())->parse(str_replace('>42.35<', '>' . $amount . '<', self::fixture('camt.054.001.02-returns.xml')));
    }

    public function testNonDecimalAmountTextIsRejected(): void
    {
        self::assertNull(IsoReportLoader::cents(null));
        $this->expectExceptionMessage('Invalid report amount "1e3": not a decimal number.');
        IsoReportLoader::cents('1e3');
    }

    /**
     * LF-M1: a "total reject" (GrpSts RJCT) without any block or transaction is visible at report level.
     */
    public function testGroupRejectWithoutTransactions(): void
    {
        $report = (new PaymentStatusReportParser())->parse(self::fixture('pain.002.001.03-group-rejected.xml'));

        self::assertSame('RJCT', $report->getGroupStatus());
        self::assertTrue($report->isFullyRejected());
        self::assertSame('REM-2026-12-B', $report->getOriginalMessageId());
        self::assertSame(ReasonCode::MS03, $report->getGroupReasons()[0]->getReasonCode());
        self::assertSame([], $report->getTransactionStatuses());
        self::assertSame([], $report->getRejectedPaymentInformationIds());
        self::assertSame([], $report->getRejectedEndToEndIds());
    }

    /**
     * LF-M1: transactions without TxSts in a rejected block are rejected, and a block without lines is reported.
     */
    public function testBlockRejectWithoutTransactionStatus(): void
    {
        $report = (new PaymentStatusReportParser())->parse(self::fixture('pain.002.001.10-block-rejected.xml'));

        self::assertTrue($report->isFullyRejected());
        self::assertSame(['REM-2026-12-C-FRST', 'REM-2026-12-C-RCUR'], $report->getRejectedPaymentInformationIds());
        self::assertSame(ReasonCode::AM05, $report->getPaymentInformationStatuses()[0]->getReasons()[0]->getReasonCode());

        $transaction = $report->getTransactionStatuses()[0];
        self::assertNull($transaction->getStatus());
        self::assertSame('RJCT', $transaction->getEffectiveStatus());
        self::assertTrue($transaction->isStatusInherited());
        self::assertTrue($transaction->isRejected());
        self::assertSame(['E2E-INV-2002'], $report->getRejectedEndToEndIds());
        self::assertSame([$transaction], $report->getRejectedTransactionStatuses());
    }

    /**
     * LF-M1: without PmtInfSts, the block and its transactions take the group status.
     */
    public function testBlockWithoutStatusInheritsTheGroupReject(): void
    {
        $xml    = str_replace("<PmtInfSts>RJCT</PmtInfSts>\n      <TxInfAndSts>", '<TxInfAndSts>', self::fixture('pain.002.001.10-block-rejected.xml'));
        $report = (new PaymentStatusReportParser())->parse($xml);
        $block  = $report->getPaymentInformationStatuses()[1];

        self::assertNull($block->getStatus());
        self::assertSame('RJCT', $block->getEffectiveStatus());
        self::assertTrue($block->isRejected());
        self::assertTrue($report->getTransactionStatuses()[0]->isRejected());
    }

    /**
     * LF-M1: PART is never inherited, and an accepted block or group never makes a rejected line accepted.
     */
    public function testPartIsNeverInherited(): void
    {
        $report = (new PaymentStatusReportParser())->parse(self::fixture('pain.002.001.10-partially-accepted.xml'));
        self::assertFalse($report->isFullyRejected());
        self::assertSame([], $report->getRejectedPaymentInformationIds());
        self::assertSame(['E2E-INV-2002'], $report->getRejectedEndToEndIds(), 'own TxSts RJCT in a PART block');
        self::assertFalse($report->getPaymentInformationStatuses()[0]->isRejected());

        $withoutTxSts = str_replace('<TxSts>RJCT</TxSts>', '', self::fixture('pain.002.001.10-partially-accepted.xml'));
        $transaction  = (new PaymentStatusReportParser())->parse($withoutTxSts)->getTransactionStatuses()[0];
        self::assertNull($transaction->getEffectiveStatus());
        self::assertFalse($transaction->isStatusInherited());
        self::assertFalse($transaction->isRejected());

        $groupPartNoBlockStatus = str_replace('<PmtInfSts>PART</PmtInfSts>', '', $withoutTxSts);
        $report                 = (new PaymentStatusReportParser())->parse($groupPartNoBlockStatus);
        self::assertNull($report->getPaymentInformationStatuses()[1]->getEffectiveStatus());
        self::assertNull($report->getTransactionStatuses()[0]->getEffectiveStatus());
    }

    /**
     * LF-L2: every camt.054 transaction carries its entry's status, reversal flag and credit / debit indicator.
     */
    public function testCamtTransactionsCarryTheEntryContext(): void
    {
        $notification = (new DebitCreditNotificationParser())->parse(self::fixture('camt.054.001.02-returns.xml'));
        $transaction  = $notification->getTransactions()[0];
        self::assertSame('BOOK', $transaction->getEntryStatus());
        self::assertTrue($transaction->isBooked());
        self::assertFalse($transaction->isReversal());
        self::assertSame('DBIT', $transaction->getCreditDebitIndicator(), '.02 has no TxDtls/CdtDbtInd: the entry value applies');
        self::assertSame($notification->getTransactions(), $notification->getBookedTransactions());

        $pending = (new DebitCreditNotificationParser())->parse(str_replace('<Sts>BOOK</Sts>', '<Sts>PDNG</Sts>', self::fixture('camt.054.001.02-returns.xml')));
        self::assertFalse($pending->getTransactions()[0]->isBooked());
        self::assertSame('PDNG', $pending->getTransactions()[0]->getEntryStatus());
        self::assertSame([], $pending->getBookedTransactions());

        $batch    = (new DebitCreditNotificationParser())->parse(self::fixture('camt.054.001.08-batch.xml'));
        $reversal = $batch->getTransactions()[2];
        self::assertSame('E2E-INV-2001', $reversal->getEndToEndId());
        self::assertTrue($reversal->isReversal());
        self::assertSame('REVERSED', $reversal->getEntryStatus());
        self::assertFalse($reversal->isBooked());
        self::assertSame('DBIT', $reversal->getCreditDebitIndicator());
        self::assertSame([$batch->getTransactions()[0], $batch->getTransactions()[1]], $batch->getBookedTransactions());
    }

    /**
     * LF-L2: an entry without TxDtls (batch booked as one amount) is listed separately.
     */
    public function testEntryWithoutTransactionDetails(): void
    {
        $xml          = (string) preg_replace('#<NtryDtls>.*?</NtryDtls>#s', '', self::fixture('camt.054.001.02-returns.xml'), 1);
        $notification = (new DebitCreditNotificationParser())->parse($xml);

        self::assertSame([], $notification->getTransactions());
        self::assertCount(1, $notification->getEntriesWithoutTransactions());
        self::assertSame(4235, $notification->getEntriesWithoutTransactions()[0]->getAmountCents());
    }

    /**
     * LF-I4: the host's pending libxml errors are kept.
     */
    public function testHostLibxmlErrorsAreKept(): void
    {
        $previous = libxml_use_internal_errors(true);
        try {
            simplexml_load_string('<host-broken');
            $hostErrors = libxml_get_errors();
            self::assertNotSame([], $hostErrors);

            (new PaymentStatusReportParser())->parse(self::fixture('pain.002.001.03-rejections.xml'));
            try {
                (new PaymentStatusReportParser())->parse('<broken');
                self::fail('A broken report must throw.');
            } catch (InvalidArgumentException $e) {
                self::assertStringContainsString('not well-formed', $e->getMessage());
            }

            self::assertEquals($hostErrors, array_slice(libxml_get_errors(), 0, count($hostErrors)));
            self::assertTrue(libxml_use_internal_errors(true));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private static function fixture(string $name): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2) . '/Fixtures/Reports/' . $name);
    }
}
