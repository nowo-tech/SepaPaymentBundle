<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model;

use InvalidArgumentException;

use function is_finite;
use function preg_match;
use function sprintf;
use function str_pad;
use function str_repeat;
use function strcmp;
use function strlen;
use function substr;

use const STR_PAD_LEFT;
use const STR_PAD_RIGHT;

/**
 * Exact money conversions for the transaction DTOs: amounts are kept as integer cents.
 *
 * - a decimal string (`"1500.25"`, `"0.3"`) is read digit by digit, never through a float;
 * - a float is first written with 6 decimals (`sprintf('%.6F')`, which removes binary noise such as
 *   `0.1 + 0.2 = 0.30000000000000004`) and then read as a decimal string;
 * - digits beyond the cent are rounded **half to even** (banker's rounding): `"0.125"` → 12 cents, `"0.135"` → 14.
 *
 * @internal
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class Amount
{
    /** Longest integer part accepted (keeps the cents inside a 64-bit integer). */
    private const MAX_INTEGER_DIGITS = 15;

    /**
     * Cents of a decimal amount in euros such as "1500.25" (optional leading "-", "." as separator).
     *
     * @throws InvalidArgumentException If the string is not a plain decimal number
     */
    public static function centsFromDecimal(string $amount): int
    {
        if (preg_match('/^(-?)(\d+)(?:\.(\d+))?$/D', $amount, $m) !== 1 || strlen($m[2]) > self::MAX_INTEGER_DIGITS) {
            throw new InvalidArgumentException(sprintf('Invalid amount "%s": use a decimal number in euros such as "1500.25".', $amount));
        }
        $fraction = $m[3] ?? '';
        $cents    = (int) $m[2] * 100 + (int) str_pad(substr($fraction, 0, 2), 2, '0', STR_PAD_RIGHT);
        $rest     = substr($fraction, 2);
        if ($rest !== '') {
            $half  = '5' . str_repeat('0', strlen($rest) - 1);
            $order = strcmp($rest, $half);
            if ($order > 0 || ($order === 0 && $cents % 2 === 1)) {
                ++$cents;
            }
        }

        return $m[1] === '-' ? -$cents : $cents;
    }

    /**
     * Cents of a float amount in euros (see the class doc for the rounding).
     *
     * @throws InvalidArgumentException If the amount is not finite
     */
    public static function centsFromFloat(float $amount): int
    {
        if (!is_finite($amount)) {
            throw new InvalidArgumentException('Invalid amount: not a finite number.');
        }

        return self::centsFromDecimal(sprintf('%.6F', $amount));
    }

    /**
     * The decimal string in euros with two decimals ("0.30") of an amount in cents.
     */
    public static function decimalFromCents(int $cents): string
    {
        $abs = str_pad((string) ($cents < 0 ? -$cents : $cents), 3, '0', STR_PAD_LEFT);

        return ($cents < 0 ? '-' : '') . substr($abs, 0, -2) . '.' . substr($abs, -2);
    }
}
