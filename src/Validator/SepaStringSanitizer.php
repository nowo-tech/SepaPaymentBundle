<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * SEPA string sanitizer and validator.
 * Validates and sanitizes strings according to SEPA character rules.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class SepaStringSanitizer
{
    public const SERVICE_NAME = 'nowo_sepa_payment.validator.sepa_string_sanitizer';

    /**
     * Maximum length for name fields in SEPA (70 characters).
     */
    public const MAX_NAME_LENGTH = 70;

    /**
     * Maximum length for address street (70 characters).
     */
    public const MAX_STREET_LENGTH = 70;

    /**
     * Maximum length for address city (35 characters).
     */
    public const MAX_CITY_LENGTH = 35;

    /**
     * Maximum length for postal code (16 characters).
     */
    public const MAX_POSTAL_CODE_LENGTH = 16;

    /**
     * Maximum length for remittance information (140 characters).
     */
    public const MAX_REMITTANCE_INFO_LENGTH = 140;

    /**
     * Allowed characters in SEPA names (letters, digits, spaces, and common punctuation).
     * Based on SEPA character set: a-z, A-Z, 0-9, / - ? : ( ) . , ' + Space.
     */
    private const ALLOWED_CHARS_PATTERN = '/^[a-zA-Z0-9\/\-\?\(\)\.\,\'\+ ]+$/u';

    /**
     * Characters that should be replaced with their ASCII equivalents.
     */
    private const CHAR_REPLACEMENTS = [
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'Ç' => 'C', 'ç' => 'c',
        'Ñ' => 'N', 'ñ' => 'n',
        'ß' => 'ss',
    ];

    /**
     * Validates if a string contains only allowed SEPA characters.
     *
     * @param string $value The string to validate
     *
     * @return bool True if valid, false otherwise
     */
    public function isValid(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        return (bool) preg_match(self::ALLOWED_CHARS_PATTERN, $value);
    }

    /**
     * Sanitizes a string by replacing invalid characters and removing disallowed ones.
     *
     * @param string $value The string to sanitize
     *
     * @return string The sanitized string
     */
    public function sanitize(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        // Replace accented characters with ASCII equivalents
        $value = strtr($value, self::CHAR_REPLACEMENTS);

        // Remove any remaining characters that are not allowed
        $value = preg_replace('/[^a-zA-Z0-9\/\-\?\(\)\.\,\'\+ ]/u', ' ', $value);

        // Remove multiple consecutive spaces
        $value = preg_replace('/\s+/', ' ', (string) $value);

        // Trim whitespace
        return trim((string) $value);
    }

    /**
     * Validates and sanitizes a string.
     *
     * @param string $value The string to validate and sanitize
     *
     * @return string The sanitized string
     */
    public function validateAndSanitize(string $value): string
    {
        return $this->sanitize($value);
    }

    /**
     * Validates the length of a name field.
     *
     * @param string $name The name to validate
     *
     * @return bool True if valid length, false otherwise
     */
    public function isValidNameLength(string $name): bool
    {
        return mb_strlen($name) <= self::MAX_NAME_LENGTH;
    }

    /**
     * Validates the length of a street address.
     *
     * @param string $street The street address to validate
     *
     * @return bool True if valid length, false otherwise
     */
    public function isValidStreetLength(string $street): bool
    {
        return mb_strlen($street) <= self::MAX_STREET_LENGTH;
    }

    /**
     * Validates the length of a city name.
     *
     * @param string $city The city name to validate
     *
     * @return bool True if valid length, false otherwise
     */
    public function isValidCityLength(string $city): bool
    {
        return mb_strlen($city) <= self::MAX_CITY_LENGTH;
    }

    /**
     * Validates the length of a postal code.
     *
     * @param string $postalCode The postal code to validate
     *
     * @return bool True if valid length, false otherwise
     */
    public function isValidPostalCodeLength(string $postalCode): bool
    {
        return mb_strlen($postalCode) <= self::MAX_POSTAL_CODE_LENGTH;
    }

    /**
     * Validates the length of remittance information.
     *
     * @param string $remittanceInfo The remittance information to validate
     *
     * @return bool True if valid length, false otherwise
     */
    public function isValidRemittanceInfoLength(string $remittanceInfo): bool
    {
        return mb_strlen($remittanceInfo) <= self::MAX_REMITTANCE_INFO_LENGTH;
    }

    /**
     * Truncates a string to the maximum allowed length for names.
     *
     * @param string $name The name to truncate
     *
     * @return string The truncated name
     */
    public function truncateName(string $name): string
    {
        if (mb_strlen($name) <= self::MAX_NAME_LENGTH) {
            return $name;
        }

        return mb_substr($name, 0, self::MAX_NAME_LENGTH);
    }

    /**
     * Truncates a string to the maximum allowed length for remittance information.
     *
     * @param string $remittanceInfo The remittance information to truncate
     *
     * @return string The truncated remittance information
     */
    public function truncateRemittanceInfo(string $remittanceInfo): string
    {
        if (mb_strlen($remittanceInfo) <= self::MAX_REMITTANCE_INFO_LENGTH) {
            return $remittanceInfo;
        }

        return mb_substr($remittanceInfo, 0, self::MAX_REMITTANCE_INFO_LENGTH);
    }
}
