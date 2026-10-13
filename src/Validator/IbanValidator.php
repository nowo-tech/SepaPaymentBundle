<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

use function ord;
use function strlen;

use const STR_PAD_LEFT;

/**
 * IBAN validator and utility class.
 * Validates IBAN format and calculates check digits according to ISO 13616 standard.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class IbanValidator
{
    public const SERVICE_NAME = 'nowo_sepa_payment.validator.iban_validator';

    /**
     * IBAN length per country code, from the ISO 13616 IBAN registry (SWIFT, registration authority).
     * Covers every SEPA country plus the other registry countries. French overseas departments/collectivities
     * use FR, Åland uses FI and Guernsey/Jersey/Isle of Man use GB, so they have no entry of their own.
     *
     * @var array<string, int>
     */
    private const IBAN_LENGTHS = [
        'AD' => 24, 'AE' => 23, 'AL' => 28, 'AT' => 20, 'AZ' => 28, 'BA' => 20, 'BE' => 16, 'BG' => 22,
        'BH' => 22, 'BI' => 27, 'BR' => 29, 'BY' => 28, 'CH' => 21, 'CR' => 22, 'CY' => 28, 'CZ' => 24,
        'DE' => 22, 'DJ' => 27, 'DK' => 18, 'DO' => 28, 'EE' => 20, 'EG' => 29, 'ES' => 24, 'FI' => 18,
        'FK' => 18, 'FO' => 18, 'FR' => 27, 'GB' => 22, 'GE' => 22, 'GI' => 23, 'GL' => 18, 'GR' => 27,
        'GT' => 28, 'HN' => 28, 'HR' => 21, 'HU' => 28, 'IE' => 22, 'IL' => 23, 'IQ' => 23, 'IS' => 26,
        'IT' => 27, 'JO' => 30, 'KW' => 30, 'KZ' => 20, 'LB' => 28, 'LC' => 32, 'LI' => 21, 'LT' => 20,
        'LU' => 20, 'LV' => 21, 'LY' => 25, 'MC' => 27, 'MD' => 24, 'ME' => 22, 'MK' => 19, 'MN' => 20,
        'MR' => 27, 'MT' => 31, 'MU' => 30, 'NI' => 28, 'NL' => 18, 'NO' => 15, 'OM' => 23, 'PK' => 24,
        'PL' => 28, 'PS' => 29, 'PT' => 25, 'QA' => 29, 'RO' => 24, 'RS' => 22, 'RU' => 33, 'SA' => 24,
        'SC' => 31, 'SD' => 18, 'SE' => 24, 'SI' => 19, 'SK' => 24, 'SM' => 27, 'SO' => 23, 'ST' => 25,
        'SV' => 28, 'TL' => 23, 'TN' => 24, 'TR' => 26, 'UA' => 29, 'VA' => 22, 'VG' => 24, 'XK' => 20,
        'YE' => 30,
    ];

    /**
     * Validates an IBAN according to ISO 13616 standard.
     * Rejects unknown country codes and lengths that do not match the country's registered IBAN length.
     *
     * @param string $iban The IBAN to validate
     *
     * @return bool True if the IBAN is valid, false otherwise
     */
    public function isValid(string $iban): bool
    {
        // Remove spaces and convert to uppercase
        $iban = $this->normalize($iban);

        // Check length (IBAN must be between 15 and 34 characters)
        if (strlen($iban) < 15 || strlen($iban) > 34) {
            return false;
        }

        // Check format: 2 letters (country code) + 2 digits (check digits) + alphanumeric (BBAN)
        if (!preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]+$/', $iban)) {
            return false;
        }

        // Check country code is in the IBAN registry and the length matches that country
        $expectedLength = self::IBAN_LENGTHS[substr($iban, 0, 2)] ?? null;
        if ($expectedLength === null || strlen($iban) !== $expectedLength) {
            return false;
        }

        // Validate check digits using mod-97 algorithm
        return $this->validateCheckDigits($iban);
    }

    /**
     * Normalizes an IBAN by removing spaces and converting to uppercase.
     *
     * @param string $iban The IBAN to normalize
     *
     * @return string The normalized IBAN
     */
    public function normalize(string $iban): string
    {
        return strtoupper(str_replace(' ', '', trim($iban)));
    }

    /**
     * Formats an IBAN with spaces every 4 characters for readability.
     *
     * @param string $iban The IBAN to format
     *
     * @return string The formatted IBAN
     */
    public function format(string $iban): string
    {
        $normalized = $this->normalize($iban);
        $formatted  = '';

        for ($i = 0; $i < strlen($normalized); ++$i) {
            if ($i > 0 && $i % 4 === 0) {
                $formatted .= ' ';
            }
            $formatted .= $normalized[$i];
        }

        return $formatted;
    }

    /**
     * Extracts the country code from an IBAN.
     *
     * @param string $iban The IBAN
     *
     * @return string The country code (2 letters)
     */
    public function getCountryCode(string $iban): string
    {
        $normalized = $this->normalize($iban);

        return substr($normalized, 0, 2);
    }

    /**
     * Extracts the check digits from an IBAN.
     *
     * @param string $iban The IBAN
     *
     * @return string The check digits (2 digits)
     */
    public function getCheckDigits(string $iban): string
    {
        $normalized = $this->normalize($iban);

        return substr($normalized, 2, 2);
    }

    /**
     * Extracts the BBAN (Basic Bank Account Number) from an IBAN.
     *
     * @param string $iban The IBAN
     *
     * @return string The BBAN
     */
    public function getBban(string $iban): string
    {
        $normalized = $this->normalize($iban);

        return substr($normalized, 4);
    }

    /**
     * Calculates the check digits for an IBAN.
     * The IBAN should have '00' as check digits, and this method will calculate the correct ones.
     *
     * @param string $iban The IBAN with '00' as check digits
     *
     * @throws InvalidArgumentException If the IBAN format is invalid
     *
     * @return string The calculated check digits (2 digits)
     */
    public function calculateCheckDigits(string $iban): string
    {
        $normalized = $this->normalize($iban);

        // Replace check digits with '00'
        $ibanWithPlaceholder = substr($normalized, 0, 2) . '00' . substr($normalized, 4);

        // Rearrange: move first 4 characters to the end
        $rearranged = substr($ibanWithPlaceholder, 4) . substr($ibanWithPlaceholder, 0, 4);

        // Convert letters to numbers (A=10, B=11, ..., Z=35)
        $numeric = '';
        for ($i = 0; $i < strlen($rearranged); ++$i) {
            $char = $rearranged[$i];
            if (ctype_alpha($char)) {
                $numeric .= (ord($char) - ord('A') + 10);
            } else {
                $numeric .= $char;
            }
        }

        // Calculate mod-97
        $remainder = $this->mod97($numeric);

        // Check digits = 98 - remainder
        $checkDigits = 98 - $remainder;

        return str_pad((string) $checkDigits, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Validates check digits using mod-97 algorithm.
     *
     * @param string $iban The normalized IBAN
     *
     * @return bool True if check digits are valid, false otherwise
     */
    private function validateCheckDigits(string $iban): bool
    {
        // Rearrange: move first 4 characters to the end
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);

        // Convert letters to numbers (A=10, B=11, ..., Z=35)
        $numeric = '';
        for ($i = 0; $i < strlen($rearranged); ++$i) {
            $char = $rearranged[$i];
            if (ctype_alpha($char)) {
                $numeric .= (ord($char) - ord('A') + 10);
            } else {
                $numeric .= $char;
            }
        }

        // Calculate mod-97, result should be 1 for valid IBAN
        return $this->mod97($numeric) === 1;
    }

    /**
     * Calculates mod-97 of a large number represented as a string.
     *
     * @param string $number The number as a string
     *
     * @return int The remainder of mod-97
     */
    private function mod97(string $number): int
    {
        $remainder = 0;
        for ($i = 0; $i < strlen($number); ++$i) {
            $remainder = ($remainder * 10 + (int) $number[$i]) % 97;
        }

        return $remainder;
    }
}
