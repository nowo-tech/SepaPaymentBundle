<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Model;

use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Model\Amount;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function restore_error_handler;
use function round;
use function set_error_handler;

use const E_USER_DEPRECATED;
use const INF;
use const NAN;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * Exact cents conversions with half-up (away from zero) rounding, like round($x, 2).
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
        yield 'half up (even cent below)' => ['0.125', 13];
        yield 'half up (odd cent below)' => ['0.135', 14];
        yield 'half up, negative (away from zero)' => ['-0.125', -13];
        yield 'half up, 1.005' => ['1.005', 101];
        yield 'half up, 0.285' => ['0.285', 29];
        yield 'half up, 2.675' => ['2.675', 268];
        yield 'above half' => ['0.12500001', 13];
        yield 'long tail' => ['0.12499999999999999999999999', 12];
        yield 'negative' => ['-1.005', -101];
        yield 'negative below half' => ['-1.0049', -100];
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
        self::assertSame(101, Amount::centsFromFloat(1.005), '1.005 is 1.00499999… in binary: read as 1.005, half up');
        self::assertSame(29, Amount::centsFromFloat(0.285));
        self::assertSame(268, Amount::centsFromFloat(2.675));
        self::assertSame(13, Amount::centsFromFloat(0.125));
        self::assertSame(-13, Amount::centsFromFloat(-0.125));
        self::assertSame(1234567891, Amount::centsFromFloat(12345678.905));
        self::assertSame(1999, Amount::centsFromFloat(19.99));
        self::assertSame(-30, Amount::centsFromFloat(-0.3));
    }

    public function testFloatsRoundLikePhpRoundToTwoDecimals(): void
    {
        for ($i = -20000; $i <= 20000; ++$i) {
            $amount = $i / 1000;
            self::assertSame((int) round(round($amount, 2) * 100), Amount::centsFromFloat($amount), (string) $amount);
        }
    }

    /**
     * D-P-I1: a float with more than 6 decimals just below a half cent is rounded once, like round($x, 2), not twice.
     */
    public function testFloatsJustBelowAHalfCentAreNotRoundedTwice(): void
    {
        self::assertSame(0, Amount::centsFromFloat(0.0049999999));
        self::assertSame(0, Amount::centsFromFloat(0.0049999996));
        self::assertSame(100, Amount::centsFromFloat(1.0049999997));
        self::assertSame(-100, Amount::centsFromFloat(-1.0049999997));
        self::assertSame(1, Amount::centsFromFloat(0.005));
        foreach ([0.0049999999, 1.0049999997, 12.3449999991, 7.1249999999] as $amount) {
            self::assertSame((int) round(round($amount, 2) * 100), Amount::centsFromFloat($amount), (string) $amount);
        }
    }

    public function testCentsAreWrittenWithTwoDecimals(): void
    {
        self::assertSame('0.30', Amount::decimalFromCents(30));
        self::assertSame('0.05', Amount::decimalFromCents(5));
        self::assertSame('15000.25', Amount::decimalFromCents(1500025));
        self::assertSame('-0.05', Amount::decimalFromCents(-5));
        self::assertSame('0.00', Amount::decimalFromCents(0));
        self::assertSame('999999999999999.99', Amount::decimalFromCents(Amount::MAX_ABS_CENTS));
        self::assertSame('-999999999999999.99', Amount::decimalFromCents(-Amount::MAX_ABS_CENTS));
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function outOfRangeCents(): iterable
    {
        yield 'PHP_INT_MAX' => [PHP_INT_MAX];
        yield 'PHP_INT_MIN' => [PHP_INT_MIN];
        yield 'just above the maximum' => [Amount::MAX_ABS_CENTS + 1];
        yield 'just below the minimum' => [-Amount::MAX_ABS_CENTS - 1];
    }

    #[DataProvider('outOfRangeCents')]
    public function testOutOfRangeCentsAreRefusedWithAClearMessage(int $cents): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid amount in cents ' . $cents . ': the absolute value must not exceed 99999999999999999');
        Amount::decimalFromCents($cents);
    }

    /**
     * @return iterable<string, array{float|int|string, int, bool}>
     */
    public static function values(): iterable
    {
        yield 'int' => [7, 700, false];
        yield 'plain decimal string' => ['0.125', 13, false];
        yield 'float' => [0.1 + 0.2, 30, true];
        yield 'exponent string' => ['1e3', 100000, true];
        yield 'leading space' => [' 1.5', 150, true];
        yield 'trailing space' => ['1.5 ', 150, true];
        yield 'leading dot' => ['.5', 50, true];
        yield 'trailing dot' => ['1.', 100, true];
        yield 'plus sign' => ['+2.675', 268, true];
    }

    #[DataProvider('values')]
    public function testConstructorValuesAcceptNumericStringsThroughTheDeprecatedFloatPath(int|float|string $amount, int $cents, bool $deprecated): void
    {
        $messages = [];
        set_error_handler(static function (int $level, string $message) use (&$messages): bool {
            $messages[] = $message;

            return true;
        }, E_USER_DEPRECATED);
        try {
            self::assertSame($cents, Amount::centsFromValue($amount, 'Owner', 'Owner::fromCents()'));
        } finally {
            restore_error_handler();
        }

        self::assertCount($deprecated ? 1 : 0, $messages);
        if ($deprecated) {
            self::assertStringContainsString('Passing the amount of a Owner as a', $messages[0]);
            self::assertStringContainsString('use Owner::fromCents()', $messages[0]);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function refusedValues(): iterable
    {
        yield 'empty' => [''];
        yield 'comma' => ['1,50'];
        yield 'letters' => ['abc'];
        yield 'too large' => ['1234567890123456'];
        yield 'too large exponent' => ['1e16'];
    }

    #[DataProvider('refusedValues')]
    public function testNonNumericOrTooLargeValuesAreRefused(string $amount): void
    {
        $this->expectException(InvalidArgumentException::class);
        set_error_handler(static fn (): bool => true, E_USER_DEPRECATED);
        try {
            Amount::centsFromValue($amount, 'Owner', 'Owner::fromCents()');
        } finally {
            restore_error_handler();
        }
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
