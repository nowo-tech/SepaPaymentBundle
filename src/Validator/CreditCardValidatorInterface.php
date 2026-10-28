<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator;

/**
 * Credit Card validation contract. {@see CreditCardValidator} is the default implementation (aliased to this interface); constraint
 * validators depend on this interface.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
interface CreditCardValidatorInterface
{
    /**
     * Validates a credit card number using the Luhn algorithm.
     *
     * @param string $cardNumber The credit card number to validate
     *
     * @return bool True if the card number is valid, false otherwise
     */
    public function isValid(string $cardNumber): bool;

    /**
     * Normalizes a credit card number by removing spaces, dashes, and other non-digit characters.
     *
     * @param string $cardNumber The credit card number to normalize
     *
     * @return string The normalized card number (digits only)
     */
    public function normalize(string $cardNumber): string;

    /**
     * Formats a credit card number with spaces every 4 digits for readability.
     *
     * @param string $cardNumber The credit card number to format
     *
     * @return string The formatted card number
     */
    public function format(string $cardNumber): string;

    /**
     * Detects the credit card type based on the card number.
     *
     * @param string $cardNumber The credit card number
     *
     * @return string The card type (visa, mastercard, amex, discover, diners_club, jcb, or unknown)
     */
    public function getCardType(string $cardNumber): string;

    /**
     * Gets the BIN (Bank Identification Number) from a credit card number.
     * The BIN is typically the first 6 digits of the card number.
     *
     * @param string $cardNumber The credit card number
     *
     * @return string The BIN (first 6 digits)
     */
    public function getBin(string $cardNumber): string;

    /**
     * Gets the last 4 digits of a credit card number.
     *
     * @param string $cardNumber The credit card number
     *
     * @return string The last 4 digits
     */
    public function getLastFour(string $cardNumber): string;

    /**
     * Masks a credit card number, showing only the last 4 digits.
     *
     * @param string $cardNumber The credit card number
     * @param string $maskChar The character to use for masking (default: *)
     *
     * @return string The masked card number
     */
    public function mask(string $cardNumber, string $maskChar = '*'): string;

    /**
     * Validates if a card number matches a specific card type.
     *
     * @param string $cardNumber The credit card number
     * @param string $cardType The expected card type
     *
     * @return bool True if the card number matches the type, false otherwise
     */
    public function isValidForType(string $cardNumber, string $cardType): bool;
}
