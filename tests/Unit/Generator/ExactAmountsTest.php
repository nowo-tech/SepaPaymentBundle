<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Generator;

use DateTime;
use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\CreditTransferData;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\Transaction;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitData;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitTransaction;
use Nowo\SepaPaymentBundle\Tests\Unit\Helper\TranslationHelper;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

use function restore_error_handler;
use function set_error_handler;

use const E_USER_DEPRECATED;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * Amounts reach the XML as exact cents: integer cents and decimal strings without a float, floats (deprecated) rounded
 * half up (like round($x, 2)) after removing binary noise; totals are summed in cents.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class ExactAmountsTest extends TestCase
{
    public function testDirectDebitXmlFromCentsDecimalsAndFloats(): void
    {
        $data = $this->directDebitData();
        $data->addTransaction(DirectDebitTransaction::fromCents(30, 'GB82WEST12345698765432', 'A', 'M-1', new DateTime('2023-12-01'), 'E2E-1'));
        $data->addTransaction(new DirectDebitTransaction('0.10', 'GB82WEST12345698765432', 'B', 'M-2', new DateTime('2023-12-01'), 'E2E-2'));
        $data->addTransaction($this->silenced(static fn (): DirectDebitTransaction => new DirectDebitTransaction(0.1 + 0.2, 'GB82WEST12345698765432', 'C', 'M-3', new DateTime('2023-12-01'), 'E2E-3')));
        $data->addTransaction(new DirectDebitTransaction(7, 'GB82WEST12345698765432', 'D', 'M-4', new DateTime('2023-12-01'), 'E2E-4'));

        self::assertSame(770, $data->getTotalAmountCents());
        self::assertSame(7.7, $data->getTotalAmount());

        $xpath = $this->xpath((new DirectDebitGenerator(new IbanValidator()))->generate($data));
        self::assertSame(['0.30', '0.10', '0.30', '7.00'], $this->values($xpath, '//ns:DrctDbtTxInf/ns:InstdAmt'));
        self::assertSame('7.70', $xpath->evaluate('string(//ns:GrpHdr/ns:CtrlSum)'));
    }

    public function testCreditTransferXmlFromCentsDecimalsAndFloats(): void
    {
        $data = new CreditTransferData('MSG-1', new DateTime('2024-01-15 10:00:00'), 'My Company', 'PMT-1', 'ES9121000418450200051332', 'My Company', new DateTime('2024-01-20'));
        $data->addTransaction(Transaction::fromCents('E2E-1', 1500025, 'EUR', 'GB82WEST12345698765432', 'A'));
        $data->addTransaction(new Transaction('E2E-2', '0.125', 'EUR', 'GB82WEST12345698765432', 'B'));
        $data->addTransaction($this->silenced(static fn (): Transaction => new Transaction('E2E-3', 0.1 + 0.2, 'EUR', 'GB82WEST12345698765432', 'C')));
        $data->addTransaction(new Transaction('E2E-4', 7, 'EUR', 'GB82WEST12345698765432', 'D'));

        self::assertSame(1500768, $data->getTotalAmountCents());

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(TranslationHelper::createTranslatorCallback());
        $xpath = $this->xpath((new CreditTransferGenerator(new IbanValidator(), $translator))->generate($data));
        self::assertSame(['15000.25', '0.13', '0.30', '7.00'], $this->values($xpath, '//ns:CdtTrfTxInf/ns:Amt/ns:InstdAmt'));
        self::assertSame('15007.68', $xpath->evaluate('string(//ns:GrpHdr/ns:CtrlSum)'));
    }

    public function testTheArrayPathKeepsDecimalStringsExact(): void
    {
        $xml = (new DirectDebitGenerator(new IbanValidator()))->generateFromArray([
            'reference'           => 'MSG-1',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-1',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company',
            'creditorIban'        => 'ES9121000418450200051332',
            'seqType'             => 'FRST',
            'creditorId'          => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
            'items'               => [
                ['amount' => '0.30', 'debtorIban' => 'GB82WEST12345698765432', 'debtorName' => 'A', 'debtorMandate' => 'M-1', 'debtorMandateSignDate' => '2023-12-01', 'endToEndId' => 'E2E-1'],
                ['amount' => 0.1 + 0.2, 'debtorIban' => 'GB82WEST12345698765432', 'debtorName' => 'B', 'debtorMandate' => 'M-2', 'debtorMandateSignDate' => '2023-12-01', 'endToEndId' => 'E2E-2'],
                ['amount' => 2, 'debtorIban' => 'GB82WEST12345698765432', 'debtorName' => 'C', 'debtorMandate' => 'M-3', 'debtorMandateSignDate' => '2023-12-01', 'endToEndId' => 'E2E-3'],
                ['amount' => '1e1', 'debtorIban' => 'GB82WEST12345698765432', 'debtorName' => 'D', 'debtorMandate' => 'M-4', 'debtorMandateSignDate' => '2023-12-01', 'endToEndId' => 'E2E-4'],
                ['amountCents' => 5, 'debtorIban' => 'GB82WEST12345698765432', 'debtorName' => 'E', 'debtorMandate' => 'M-5', 'debtorMandateSignDate' => '2023-12-01', 'endToEndId' => 'E2E-5'],
            ],
        ]);

        self::assertSame(['0.30', '0.30', '2.00', '10.00', '0.05'], $this->values($this->xpath($xml), '//ns:DrctDbtTxInf/ns:InstdAmt'));
    }

    public function testAFloatAmountIsDeprecated(): void
    {
        $messages = [];
        set_error_handler(static function (int $level, string $message) use (&$messages): bool {
            $messages[] = $message;

            return true;
        }, E_USER_DEPRECATED);
        try {
            $debit    = new DirectDebitTransaction(19.99, 'GB82WEST12345698765432', 'A', 'M-1', new DateTime('2023-12-01'), 'E2E-1');
            $transfer = new Transaction('E2E-1', 19.99, 'EUR', 'GB82WEST12345698765432', 'A');
            new DirectDebitTransaction('19.99', 'GB82WEST12345698765432', 'A', 'M-1', new DateTime('2023-12-01'), 'E2E-1');
            Transaction::fromCents('E2E-1', 1999, 'EUR', 'GB82WEST12345698765432', 'A');
            $exponent = new Transaction('E2E-1', '1e3', 'EUR', 'GB82WEST12345698765432', 'A');
            $spaced   = new DirectDebitTransaction(' 1.5', 'GB82WEST12345698765432', 'A', 'M-1', new DateTime('2023-12-01'), 'E2E-1');
        } finally {
            restore_error_handler();
        }

        self::assertCount(4, $messages);
        self::assertStringContainsString('DirectDebitTransaction::fromCents()', $messages[0]);
        self::assertStringContainsString('CreditTransfer\\Transaction as a float', $messages[1]);
        self::assertStringContainsString('Transaction::fromCents()', $messages[1]);
        self::assertStringContainsString('CreditTransfer\\Transaction as a numeric string that is not a plain decimal ("1e3")', $messages[2]);
        self::assertStringContainsString('DirectDebitTransaction as a numeric string that is not a plain decimal (" 1.5")', $messages[3]);
        self::assertSame(100000, $exponent->getAmountCents());
        self::assertSame(150, $spaced->getAmountCents());
        self::assertSame([1999, '19.99', 19.99], [$debit->getAmountCents(), $debit->getAmountDecimal(), $debit->getAmount()]);
        self::assertSame([1999, '19.99', 19.99], [$transfer->getAmountCents(), $transfer->getAmountDecimal(), $transfer->getAmount()]);
    }

    public function testANonNumericStringStillThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Transaction('E2E-1', '1,50', 'EUR', 'GB82WEST12345698765432', 'A');
    }

    public function testFromCentsAtTheIntegerExtremesThrowsAClearMessage(): void
    {
        foreach ([PHP_INT_MAX, PHP_INT_MIN] as $cents) {
            try {
                DirectDebitTransaction::fromCents($cents, 'GB82WEST12345698765432', 'A', 'M-1', new DateTime('2023-12-01'), 'E2E-1');
                self::fail('No exception.');
            } catch (InvalidArgumentException $e) {
                self::assertStringContainsString('Invalid amount in cents ' . $cents, $e->getMessage());
            }
            try {
                Transaction::fromCents('E2E-1', $cents, 'EUR', 'GB82WEST12345698765432', 'A');
                self::fail('No exception.');
            } catch (InvalidArgumentException $e) {
                self::assertStringContainsString('Invalid amount in cents ' . $cents, $e->getMessage());
            }
        }
    }

    private function directDebitData(): DirectDebitData
    {
        return new DirectDebitData('MSG-1', 'My Company', 'PMT-1', new DateTime('2024-01-20'), 'My Company', 'ES9121000418450200051332', 'FRST', 'ES1234567890123456789012', 'CORE');
    }

    /**
     * @template T
     *
     * @param callable(): T $build
     *
     * @return T
     */
    private function silenced(callable $build): mixed
    {
        set_error_handler(static fn (): bool => true, E_USER_DEPRECATED);
        try {
            return $build();
        } finally {
            restore_error_handler();
        }
    }

    private function xpath(string $xml): DOMXPath
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('ns', (string) $dom->documentElement?->namespaceURI);

        return $xpath;
    }

    /**
     * @return list<string>
     */
    private function values(DOMXPath $xpath, string $query): array
    {
        $values = [];
        foreach ($xpath->query($query) ?: [] as $node) {
            $values[] = $node->textContent;
        }

        return $values;
    }
}
