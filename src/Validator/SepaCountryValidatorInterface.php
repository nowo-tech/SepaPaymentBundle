<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator;

/**
 * Sepa Country validation contract. {@see SepaCountryValidator} is the default implementation (aliased to this interface); constraint
 * validators depend on this interface.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
interface SepaCountryValidatorInterface
{
    /**
     * Validates if a country code is a SEPA member country.
     *
     * @param string $countryCode ISO 3166-1 alpha-2 country code (e.g., 'ES', 'FR', 'DE')
     *
     * @return bool True if the country is a SEPA member, false otherwise
     */
    public function isSepaCountry(string $countryCode): bool;

    /**
     * Validates if a country code from an IBAN is a SEPA member country.
     *
     * @param string $iban The IBAN to check
     *
     * @return bool True if the IBAN's country is a SEPA member, false otherwise
     */
    public function isSepaCountryFromIban(string $iban): bool;

    /**
     * Gets all SEPA member countries.
     *
     * @return array<int, string> Array of ISO 3166-1 alpha-2 country codes
     */
    public function getSepaCountries(): array;

    /**
     * Gets the country name for a given country code.
     * Returns the country code if name is not available.
     *
     * @param string $countryCode ISO 3166-1 alpha-2 country code
     *
     * @return string Country name or code
     */
    public function getCountryName(string $countryCode): string;
}
