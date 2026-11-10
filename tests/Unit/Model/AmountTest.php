<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Model;

use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Model\Amount;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use const INF;
use const NAN;

/**
 * Exact cents conversions with half-to-even rounding.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class AmountTest extends TestCase
{
    /**
     * @return iterable<string, array{string, int}>
     */
    public static function decimals(): iterable
    {
        yield 'whole' => ['15000', 1500000];
        yield 'two decimals' => ['15000.25', 1500025];
        yield 'one decimal' => ['0.3', 30];
        yield 'below the cent, down' => ['0.124', 12];
        yield 'below the cent, up' => ['0.126', 13];
        yield 'half to even (down)' => ['0.125', 12];
        yield 'half to even (up)' => ['0.135', 14];
        yield 'above half' => ['0.12500001', 13];
        yield 'long tail' => ['0.12499999999999999999999999', 12];
        yield 'negative' => ['-1.005', -100];
        yield 'zero' => ['0.00', 0];
    }

    #[DataProvider('decimals')]
    public function testDecimalStringsAreReadExactly(string $amount, int $cents): void
    {
        self::assertSame($cents, Amount::centsFromDecimal($amount));
    }

    public function testFloatNoiseIsRemovedBeforeRounding(): void
    {
        self::assertSame(30, Amount::centsFromFloat(0.1 + 0.2));
        self::assertSame(100, Amount::centsFromFloat(1.005), '1.005 is 1.00499999… in binary: read as 1.005, half to even');
        self::assertSame(268, Amount::centsFromFloat(2.675));
        self::assertSame(1999, Amount::centsFromFloat(19.99));
        self::assertSame(-30, Amount::centsFromFloat(-0.3));
    }

    public function testCentsAreWrittenWithTwoDecimals(): void
    {
        self::assertSame('0.30', Amount::decimalFromCents(30));
        self::assertSame('0.05', Amount::decimalFromCents(5));
        self::assertSame('15000.25', Amount::decimalFromCents(1500025));
        self::assertSame('-0.05', Amount::decimalFromCents(-5));
        self::assertSame('0.00', Amount::decimalFromCents(0));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalid(): iterable
    {
        yield 'empty' => [''];
        yield 'comma' => ['1,50'];
        yield 'exponent' => ['1e3'];
        yield 'spaces' => [' 1.50'];
        yield 'trailing dot' => ['1.'];
        yield 'too large' => ['1234567890123456'];
    }

    #[DataProvider('invalid')]
    public function testInvalidDecimalsAreRefused(string $amount): void
    {
        $this->expectException(InvalidArgumentException::class);
        Amount::centsFromDecimal($amount);
    }

    public function testNonFiniteFloatsAreRefused(): void
    {
        foreach ([NAN, INF] as $amount) {
            try {
                Amount::centsFromFloat($amount);
                self::fail('No exception.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
