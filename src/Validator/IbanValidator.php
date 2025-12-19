<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * IBAN validator and utility class.
 * Validates IBAN format and calculates check digits according to ISO 13616 standard.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class IbanValidator
{
    public const SERVICE_NAME = 'nowo_sepa_payment.validator.iban_validator';

    /**
     * Validates an IBAN according to ISO 13616 standard.
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
        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/', $iban)) {
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
        $formatted = '';

        for ($i = 0; $i < strlen($normalized); ++$i) {
            if ($i > 0 && 0 === $i % 4) {
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
     * @throws \InvalidArgumentException If the IBAN format is invalid
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
        return 1 === $this->mod97($numeric);
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
