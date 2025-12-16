<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator;

/**
 * Credit card validator.
 * Validates credit card numbers using the Luhn algorithm and detects card types.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class CreditCardValidator
{
    /**
     * Card type constants.
     */
    public const TYPE_VISA = 'visa';
    public const TYPE_MASTERCARD = 'mastercard';
    public const TYPE_AMEX = 'amex';
    public const TYPE_DISCOVER = 'discover';
    public const TYPE_DINERS_CLUB = 'diners_club';
    public const TYPE_JCB = 'jcb';
    public const TYPE_UNKNOWN = 'unknown';

    /**
     * Validates a credit card number using the Luhn algorithm.
     *
     * @param string $cardNumber The credit card number to validate
     * @return bool True if the card number is valid, false otherwise
     */
    public function isValid(string $cardNumber): bool
    {
        $normalized = $this->normalize($cardNumber);

        // Card number must contain only digits
        if (!ctype_digit($normalized)) {
            return false;
        }

        // Card number must be between 13 and 19 digits
        $length = strlen($normalized);
        if ($length < 13 || $length > 19) {
            return false;
        }

        // Validate using Luhn algorithm
        return $this->validateLuhn($normalized);
    }

    /**
     * Validates a credit card number using the Luhn algorithm.
     *
     * @param string $cardNumber The normalized card number (digits only)
     * @return bool True if the card number passes Luhn validation
     */
    private function validateLuhn(string $cardNumber): bool
    {
        $sum = 0;
        $numDigits = strlen($cardNumber);
        $parity = $numDigits % 2;

        for ($i = 0; $i < $numDigits; ++$i) {
            $digit = (int) $cardNumber[$i];

            if ($i % 2 === $parity) {
                $digit *= 2;
            }

            if ($digit > 9) {
                $digit -= 9;
            }

            $sum += $digit;
        }

        return 0 === ($sum % 10);
    }

    /**
     * Normalizes a credit card number by removing spaces, dashes, and other non-digit characters.
     *
     * @param string $cardNumber The credit card number to normalize
     * @return string The normalized card number (digits only)
     */
    public function normalize(string $cardNumber): string
    {
        return preg_replace('/[^0-9]/', '', trim($cardNumber));
    }

    /**
     * Formats a credit card number with spaces every 4 digits for readability.
     *
     * @param string $cardNumber The credit card number to format
     * @return string The formatted card number
     */
    public function format(string $cardNumber): string
    {
        $normalized = $this->normalize($cardNumber);
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
     * Detects the credit card type based on the card number.
     *
     * @param string $cardNumber The credit card number
     * @return string The card type (visa, mastercard, amex, discover, diners_club, jcb, or unknown)
     */
    public function getCardType(string $cardNumber): string
    {
        $normalized = $this->normalize($cardNumber);

        if (empty($normalized)) {
            return self::TYPE_UNKNOWN;
        }

        // Visa: starts with 4, 13 or 16 digits
        if (preg_match('/^4\d{12}(\d{3})?$/', $normalized)) {
            return self::TYPE_VISA;
        }

        // Mastercard: starts with 51-55 or 2221-2720, 16 digits
        if (preg_match('/^5[1-5]\d{14}$/', $normalized) || preg_match('/^2[2-7]\d{14}$/', $normalized)) {
            return self::TYPE_MASTERCARD;
        }

        // American Express: starts with 34 or 37, 15 digits
        if (preg_match('/^3[47]\d{13}$/', $normalized)) {
            return self::TYPE_AMEX;
        }

        // Discover: starts with 6011, 622126-622925, 644-649, or 65, 16 digits
        if (preg_match('/^6(?:011|5\d{2}|4[4-9]\d|22[1-9]\d{2})\d{10}$/', $normalized)) {
            return self::TYPE_DISCOVER;
        }

        // Diners Club: starts with 300-305, 309, 36, or 38-39, 14 digits
        if (preg_match('/^3[0689]\d{12}$/', $normalized) || preg_match('/^30[0-5]\d{11}$/', $normalized)) {
            return self::TYPE_DINERS_CLUB;
        }

        // JCB: starts with 35, 16 digits
        if (preg_match('/^35\d{14}$/', $normalized)) {
            return self::TYPE_JCB;
        }

        return self::TYPE_UNKNOWN;
    }

    /**
     * Gets the BIN (Bank Identification Number) from a credit card number.
     * The BIN is typically the first 6 digits of the card number.
     *
     * @param string $cardNumber The credit card number
     * @return string The BIN (first 6 digits)
     */
    public function getBin(string $cardNumber): string
    {
        $normalized = $this->normalize($cardNumber);

        return substr($normalized, 0, 6);
    }

    /**
     * Gets the last 4 digits of a credit card number.
     *
     * @param string $cardNumber The credit card number
     * @return string The last 4 digits
     */
    public function getLastFour(string $cardNumber): string
    {
        $normalized = $this->normalize($cardNumber);

        return substr($normalized, -4);
    }

    /**
     * Masks a credit card number, showing only the last 4 digits.
     *
     * @param string $cardNumber The credit card number
     * @param string $maskChar   The character to use for masking (default: *)
     * @return string The masked card number
     */
    public function mask(string $cardNumber, string $maskChar = '*'): string
    {
        $normalized = $this->normalize($cardNumber);
        $length = strlen($normalized);

        if ($length < 4) {
            return str_repeat($maskChar, $length);
        }

        $lastFour = $this->getLastFour($normalized);
        $masked = str_repeat($maskChar, $length - 4);

        return $masked . $lastFour;
    }

    /**
     * Validates if a card number matches a specific card type.
     *
     * @param string $cardNumber The credit card number
     * @param string $cardType   The expected card type
     * @return bool True if the card number matches the type, false otherwise
     */
    public function isValidForType(string $cardNumber, string $cardType): bool
    {
        if (!$this->isValid($cardNumber)) {
            return false;
        }

        return $this->getCardType($cardNumber) === $cardType;
    }
}

