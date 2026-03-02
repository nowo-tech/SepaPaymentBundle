<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

use function ord;
use function strlen;

use const STR_PAD_LEFT;

/**
 * SEPA Creditor Identifier validator.
 * Validates SEPA Creditor Identifier format and check digits according to ISO 7064 MOD97-10.
 *
 * Structure: CCKKKSSSNNNNNNNNN
 * - CC: Country code (2 letters, ISO 3166-1 alpha-2)
 * - KK: Check digits (2 digits, MOD97-10)
 * - SSS: Suffix (3 characters, usually "ZZZ" or "000")
 * - NNNNNNNNN: National identifier (variable length, up to 28 characters)
 *
 * For Spain (ES): ESKKZZZNIF
 * - ES: Country code
 * - KK: Check digits (MOD97-10)
 * - ZZZ: Suffix (usually "ZZZ" or "000")
 * - NIF: Spanish tax identifier (1 letter + 8 digits)
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class SepaCreditorIdentifierValidator
{
    public const SERVICE_NAME = 'nowo_sepa_payment.validator.sepa_creditor_identifier_validator';

    /**
     * Minimum length of SEPA Creditor Identifier (9 characters: CC + KK + SSS + at least 1 char for identifier).
     */
    private const MIN_LENGTH = 9;

    /**
     * Maximum length of SEPA Creditor Identifier (35 characters: CC + KK + SSS + up to 28 chars for identifier).
     */
    private const MAX_LENGTH = 35;

    /**
     * Validates a SEPA Creditor Identifier.
     *
     * @param string $creditorId The SEPA Creditor Identifier to validate
     *
     * @return bool True if valid, false otherwise
     */
    public function isValid(string $creditorId): bool
    {
        // Normalize (remove spaces, convert to uppercase)
        $normalized = $this->normalize($creditorId);

        // Check length
        $length = strlen($normalized);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            return false;
        }

        // Extract components
        $countryCode = substr($normalized, 0, 2);
        $checkDigits = substr($normalized, 2, 2);
        $suffix      = substr($normalized, 4, 3);
        $nationalId  = substr($normalized, 7);

        // Validate country code format (2 letters)
        if (!preg_match('/^[A-Z]{2}$/', $countryCode)) {
            return false;
        }

        // Validate check digits format (2 digits)
        if (!preg_match('/^\d{2}$/', $checkDigits)) {
            return false;
        }

        // Validate suffix format (3 alphanumeric characters)
        if (!preg_match('/^[A-Z0-9]{3}$/', $suffix)) {
            return false;
        }

        // Validate national identifier (at least 1 character, alphanumeric)
        if ($nationalId === '' || !preg_match('/^[A-Z0-9]+$/', $nationalId)) {
            return false;
        }

        // Validate check digits using MOD97-10 algorithm
        return $this->validateCheckDigits($normalized, $countryCode, $checkDigits, $suffix, $nationalId);
    }

    /**
     * Normalizes a SEPA Creditor Identifier by removing spaces and converting to uppercase.
     *
     * @param string $creditorId The identifier to normalize
     *
     * @return string The normalized identifier
     */
    public function normalize(string $creditorId): string
    {
        return strtoupper(str_replace(' ', '', trim($creditorId)));
    }

    /**
     * Validates check digits using MOD97-10 algorithm (ISO 7064).
     *
     * Algorithm:
     * 1. Remove check digits (positions 2-3)
     * 2. Remove suffix (positions 4-6)
     * 3. Take national identifier and add country code + "00" at the end
     * 4. Convert letters to digits (A=10, B=11, ..., Z=35)
     * 5. Apply MOD97-10: remainder = 1 if valid
     *
     * @param string $fullIdentifier The full identifier
     * @param string $countryCode Country code (2 letters)
     * @param string $checkDigits Check digits (2 digits)
     * @param string $suffix Suffix (3 characters)
     * @param string $nationalId National identifier
     *
     * @return bool True if check digits are valid
     */
    private function validateCheckDigits(
        string $fullIdentifier,
        string $countryCode,
        string $checkDigits,
        string $suffix,
        string $nationalId
    ): bool {
        // Build string for MOD97-10 calculation:
        // National ID + Country Code + "00"
        $calculationString = $nationalId . $countryCode . '00';

        // Convert letters to digits (A=10, B=11, ..., Z=35)
        $numericString = '';
        for ($i = 0; $i < strlen($calculationString); ++$i) {
            $char = $calculationString[$i];
            if (ctype_digit($char)) {
                $numericString .= $char;
            } else {
                // Convert letter to number (A=10, B=11, ..., Z=35)
                $numericString .= (string) (ord($char) - ord('A') + 10);
            }
        }

        // Apply MOD97-10 algorithm
        // Calculate modulo 97, then subtract from 98 to get check digits
        $remainder             = $this->mod97($numericString);
        $calculatedCheckDigits = 98 - $remainder;

        // Format check digits to 2 digits (pad with zero if needed)
        $calculatedCheckDigitsFormatted = str_pad((string) $calculatedCheckDigits, 2, '0', STR_PAD_LEFT);

        return $calculatedCheckDigitsFormatted === $checkDigits;
    }

    /**
     * Calculates modulo 97 of a large number (represented as string).
     *
     * @param string $number The number as string
     *
     * @return int The remainder after dividing by 97
     */
    private function mod97(string $number): int
    {
        $remainder = 0;
        $length    = strlen($number);

        for ($i = 0; $i < $length; ++$i) {
            $remainder = ($remainder * 10 + (int) $number[$i]) % 97;
        }

        return $remainder;
    }

    /**
     * Extracts the country code from a SEPA Creditor Identifier.
     *
     * @param string $creditorId The identifier
     *
     * @return string The country code (2 letters)
     */
    public function getCountryCode(string $creditorId): string
    {
        $normalized = $this->normalize($creditorId);

        return substr($normalized, 0, 2);
    }

    /**
     * Extracts the national identifier from a SEPA Creditor Identifier.
     *
     * @param string $creditorId The identifier
     *
     * @return string The national identifier
     */
    public function getNationalIdentifier(string $creditorId): string
    {
        $normalized = $this->normalize($creditorId);

        return substr($normalized, 7);
    }

    /**
     * Validates Spanish NIF/CIF format (for use in Spanish SEPA Creditor Identifiers).
     *
     * Spanish NIF format: 1 letter + 8 digits (e.g., "M12345678")
     * Spanish CIF format: 1 letter + 7 digits + 1 letter/digit (e.g., "A12345674")
     *
     * This method validates basic format. Full NIF/CIF validation with check digit
     * would require additional logic specific to Spanish tax identifiers.
     *
     * @param string $nif The NIF/CIF to validate
     *
     * @return bool True if format is valid, false otherwise
     */
    public function isValidSpanishNifFormat(string $nif): bool
    {
        $nif = strtoupper(trim($nif));

        // NIF format: 1 letter + 8 digits (e.g., M12345678)
        if (preg_match('/^[A-Z]\d{8}$/', $nif)) {
            return true;
        }

        // CIF format: 1 letter + 7 digits + 1 letter/digit (e.g., A12345674)
        return (bool) preg_match('/^[A-Z]\d{7}[A-Z0-9]$/', $nif)

        ;
    }
}
