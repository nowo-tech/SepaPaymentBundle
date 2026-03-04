<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

use function in_array;
use function strlen;

/**
 * SEPA country validator.
 * Validates if a country is a SEPA member country.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class SepaCountryValidator
{
    public const SERVICE_NAME = 'nowo_sepa_payment.validator.sepa_country_validator';

    /**
     * List of SEPA member countries (ISO 3166-1 alpha-2 codes).
     * Updated as of 2025.
     */
    private const SEPA_COUNTRIES = [
        'AT', // Austria
        'BE', // Belgium
        'BG', // Bulgaria
        'HR', // Croatia
        'CY', // Cyprus
        'CZ', // Czech Republic
        'DK', // Denmark
        'EE', // Estonia
        'FI', // Finland
        'FR', // France
        'DE', // Germany
        'GR', // Greece
        'HU', // Hungary
        'IS', // Iceland
        'IE', // Ireland
        'IT', // Italy
        'LV', // Latvia
        'LI', // Liechtenstein
        'LT', // Lithuania
        'LU', // Luxembourg
        'MT', // Malta
        'MC', // Monaco
        'NL', // Netherlands
        'NO', // Norway
        'PL', // Poland
        'PT', // Portugal
        'RO', // Romania
        'SM', // San Marino
        'SK', // Slovakia
        'SI', // Slovenia
        'ES', // Spain
        'SE', // Sweden
        'CH', // Switzerland
        'GB', // United Kingdom (still SEPA member despite Brexit)
    ];

    /**
     * Validates if a country code is a SEPA member country.
     *
     * @param string $countryCode ISO 3166-1 alpha-2 country code (e.g., 'ES', 'FR', 'DE')
     *
     * @return bool True if the country is a SEPA member, false otherwise
     */
    public function isSepaCountry(string $countryCode): bool
    {
        $countryCode = strtoupper(trim($countryCode));

        return in_array($countryCode, self::SEPA_COUNTRIES, true);
    }

    /**
     * Validates if a country code from an IBAN is a SEPA member country.
     *
     * @param string $iban The IBAN to check
     *
     * @return bool True if the IBAN's country is a SEPA member, false otherwise
     */
    public function isSepaCountryFromIban(string $iban): bool
    {
        // Extract country code from IBAN (first 2 characters)
        if (strlen($iban) < 2) {
            return false;
        }

        $countryCode = strtoupper(substr($iban, 0, 2));

        return $this->isSepaCountry($countryCode);
    }

    /**
     * Gets all SEPA member countries.
     *
     * @return array<int, string> Array of ISO 3166-1 alpha-2 country codes
     */
    public function getSepaCountries(): array
    {
        return self::SEPA_COUNTRIES;
    }

    /**
     * Gets the country name for a given country code.
     * Returns the country code if name is not available.
     *
     * @param string $countryCode ISO 3166-1 alpha-2 country code
     *
     * @return string Country name or code
     */
    public function getCountryName(string $countryCode): string
    {
        $countryCode = strtoupper(trim($countryCode));

        $countryNames = [
            'AT' => 'Austria',
            'BE' => 'Belgium',
            'BG' => 'Bulgaria',
            'HR' => 'Croatia',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'DK' => 'Denmark',
            'EE' => 'Estonia',
            'FI' => 'Finland',
            'FR' => 'France',
            'DE' => 'Germany',
            'GR' => 'Greece',
            'HU' => 'Hungary',
            'IS' => 'Iceland',
            'IE' => 'Ireland',
            'IT' => 'Italy',
            'LV' => 'Latvia',
            'LI' => 'Liechtenstein',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'MT' => 'Malta',
            'MC' => 'Monaco',
            'NL' => 'Netherlands',
            'NO' => 'Norway',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'RO' => 'Romania',
            'SM' => 'San Marino',
            'SK' => 'Slovakia',
            'SI' => 'Slovenia',
            'ES' => 'Spain',
            'SE' => 'Sweden',
            'CH' => 'Switzerland',
            'GB' => 'United Kingdom',
        ];

        return $countryNames[$countryCode] ?? $countryCode;
    }
}
