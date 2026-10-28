<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator;

use InvalidArgumentException;

/**
 * Iban validation contract. {@see IbanValidator} is the default implementation (aliased to this interface) and
 * {@see CachedIbanValidator} its caching decorator; constraint validators depend on this interface.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
interface IbanValidatorInterface
{
    /**
     * Validates an IBAN according to ISO 13616 standard.
     * Rejects unknown country codes and lengths that do not match the country's registered IBAN length.
     *
     * @param string $iban The IBAN to validate
     *
     * @return bool True if the IBAN is valid, false otherwise
     */
    public function isValid(string $iban): bool;

    /**
     * Normalizes an IBAN by removing spaces and converting to uppercase.
     *
     * @param string $iban The IBAN to normalize
     *
     * @return string The normalized IBAN
     */
    public function normalize(string $iban): string;

    /**
     * Formats an IBAN with spaces every 4 characters for readability.
     *
     * @param string $iban The IBAN to format
     *
     * @return string The formatted IBAN
     */
    public function format(string $iban): string;

    /**
     * Extracts the country code from an IBAN.
     *
     * @param string $iban The IBAN
     *
     * @return string The country code (2 letters)
     */
    public function getCountryCode(string $iban): string;

    /**
     * Extracts the check digits from an IBAN.
     *
     * @param string $iban The IBAN
     *
     * @return string The check digits (2 digits)
     */
    public function getCheckDigits(string $iban): string;

    /**
     * Extracts the BBAN (Basic Bank Account Number) from an IBAN.
     *
     * @param string $iban The IBAN
     *
     * @return string The BBAN
     */
    public function getBban(string $iban): string;

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
    public function calculateCheckDigits(string $iban): string;
}
