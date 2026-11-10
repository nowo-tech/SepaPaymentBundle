<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model;

use InvalidArgumentException;

use function abs;
use function floor;
use function is_finite;
use function is_float;
use function is_int;
use function is_numeric;
use function log10;
use function max;
use function min;
use function preg_match;
use function sprintf;
use function str_pad;
use function str_repeat;
use function strcmp;
use function strlen;
use function substr;
use function trigger_error;

use const E_USER_DEPRECATED;
use const STR_PAD_LEFT;
use const STR_PAD_RIGHT;

/**
 * Exact money conversions for the transaction DTOs: amounts are kept as integer cents.
 *
 * - a decimal string (`"1500.25"`, `"0.3"`) is read digit by digit, never through a float;
 * - a float is first written with 15 significant digits in fixed notation (`sprintf('%.{n}F')`, the precision of a
 *   double, which removes binary noise such as `0.1 + 0.2 = 0.30000000000000004` or `3.5 * 1.21 = 4.2349999…`) and
 *   then read as a decimal string, so it is rounded only once (D-P-I1: `0.0049999999` gives 0 cents, not 1). This
 *   never calls PHP's `round()`, whose behaviour changed in PHP 8.4, so the cents are the same on every PHP version
 *   (LB-M1);
 * - digits of a decimal string beyond the cent are rounded **half up** (half away from zero, the euro convention of
 *   Regulation (EC) 1103/97 and PHP's `round($x, 2)`): `"0.125"` → 13 cents, `"-0.125"` → -13, `1.005` → 101,
 *   `2.675` → 268. The result does not depend on the PHP version.
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

    /** Significant digits kept from a float (the precision of an IEEE 754 double). */
    private const FLOAT_SIGNIFICANT_DIGITS = 15;

    /** Most decimals written for a float (amounts below 1e-6 EUR are 0 cents anyway). */
    private const MAX_FLOAT_DECIMALS = 20;

    /** Largest absolute amount in cents (15 integer digits and 2 decimals: 999 999 999 999 999.99). */
    public const MAX_ABS_CENTS = 99_999_999_999_999_999;

    /**
     * Cents of a constructor amount in euros: an int (whole euros) or a plain decimal string is exact; a float or
     * another numeric string ("1e3", " 1.5") goes through {@see self::centsFromFloat()} and triggers a silenced
     * `E_USER_DEPRECATED` naming `$owner`; any other string throws.
     *
     * @param string $owner The class the amount is given to (used in the deprecation messages)
     * @param string $alternative What to use instead (used in the deprecation messages)
     *
     * @throws InvalidArgumentException If the amount is not a finite number or a plain decimal string
     */
    public static function centsFromValue(int|float|string $amount, string $owner, string $alternative): int
    {
        if (is_int($amount)) {
            return self::centsFromDecimal((string) $amount);
        }
        if (is_float($amount)) {
            @trigger_error(sprintf('Passing the amount of a %s as a float is deprecated since nowo-tech/sepa-payment-bundle 2.1: use %s or a decimal string.', $owner, $alternative), E_USER_DEPRECATED);

            return self::centsFromFloat($amount);
        }
        if (!self::isPlainDecimal($amount) && is_numeric($amount)) {
            @trigger_error(sprintf('Passing the amount of a %s as a numeric string that is not a plain decimal ("%s") is deprecated since nowo-tech/sepa-payment-bundle 2.1: use %s or a decimal string such as "1500.25".', $owner, $amount, $alternative), E_USER_DEPRECATED);

            return self::centsFromFloat((float) $amount);
        }

        return self::centsFromDecimal($amount);
    }

    /**
     * Whether the string is a plain decimal number in euros ("1500.25", "-0.3"; no sign "+", exponent, spaces or comma).
     */
    public static function isPlainDecimal(string $amount): bool
    {
        return preg_match('/^-?\d+(?:\.\d+)?$/D', $amount) === 1;
    }

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
            // Half up on the absolute value (the sign is applied below), i.e. half away from zero.
            if ($order >= 0) {
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

        if ($amount === 0.0) {
            return 0;
        }

        // 15 significant digits in fixed notation, then the exact decimal path: one rounding only (D-P-I1) and no
        // dependency on round(), which PHP 8.4 changed (LB-M1). Below 1e-6 every value is 0 cents, so cap the decimals.
        $decimals = min(self::MAX_FLOAT_DECIMALS, max(0, self::FLOAT_SIGNIFICANT_DIGITS - 1 - (int) floor(log10(abs($amount)))));

        return self::centsFromDecimal(sprintf('%.' . $decimals . 'F', $amount));
    }

    /**
     * The decimal string in euros with two decimals ("0.30") of an amount in cents.
     *
     * @throws InvalidArgumentException If the absolute amount exceeds {@see self::MAX_ABS_CENTS}
     */
    public static function decimalFromCents(int $cents): string
    {
        if ($cents > self::MAX_ABS_CENTS || $cents < -self::MAX_ABS_CENTS) {
            throw new InvalidArgumentException(sprintf('Invalid amount in cents %d: the absolute value must not exceed %d (EUR 999999999999999.99).', $cents, self::MAX_ABS_CENTS));
        }
        $abs = str_pad((string) ($cents < 0 ? -$cents : $cents), 3, '0', STR_PAD_LEFT);

        return ($cents < 0 ? '-' : '') . substr($abs, 0, -2) . '.' . substr($abs, -2);
    }
}
