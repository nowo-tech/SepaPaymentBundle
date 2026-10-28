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
#[AsAlias(id: SepaCountryValidatorInterface::class)]
final class SepaCountryValidator implements SepaCountryValidatorInterface
{
    public const SERVICE_NAME = 'nowo_sepa_payment.validator.sepa_country_validator';

    /**
     * Countries and territories in the SEPA schemes' geographical scope (ISO 3166-1 alpha-2 codes).
     * Source: EPC409-09 "EPC list of countries in the SEPA schemes' geographical scope", v8.0 (24 December 2025).
     * Territories whose IBANs use another country's prefix (e.g. GP → FR, JE → GB) are listed for address/BIC use.
     */
    private const SEPA_COUNTRIES = [
        'AD', // Andorra
        'AL', // Albania
        'AT', // Austria
        'AX', // Åland Islands (BIC/IBAN use FI)
        'BE', // Belgium
        'BG', // Bulgaria
        'BL', // Saint Barthélemy (IBAN uses FR)
        'CH', // Switzerland
        'CY', // Cyprus
        'CZ', // Czech Republic
        'DE', // Germany
        'DK', // Denmark
        'EE', // Estonia
        'ES', // Spain (incl. Canary Islands)
        'FI', // Finland
        'FR', // France
        'GB', // United Kingdom
        'GF', // French Guiana (IBAN uses FR)
        'GG', // Guernsey (IBAN uses GB)
        'GI', // Gibraltar
        'GP', // Guadeloupe (IBAN uses FR)
        'GR', // Greece
        'HR', // Croatia
        'HU', // Hungary
        'IE', // Ireland
        'IM', // Isle of Man (IBAN uses GB)
        'IS', // Iceland
        'IT', // Italy
        'JE', // Jersey (IBAN uses GB)
        'LI', // Liechtenstein
        'LT', // Lithuania
        'LU', // Luxembourg
        'LV', // Latvia
        'MC', // Monaco
        'MD', // Moldova
        'ME', // Montenegro
        'MF', // Saint Martin, French part (IBAN uses FR)
        'MK', // North Macedonia
        'MQ', // Martinique (IBAN uses FR)
        'MT', // Malta
        'NL', // Netherlands
        'NO', // Norway
        'PL', // Poland
        'PM', // Saint Pierre and Miquelon (IBAN uses FR)
        'PT', // Portugal (incl. Azores and Madeira)
        'RE', // Réunion (IBAN uses FR)
        'RO', // Romania
        'RS', // Serbia
        'SE', // Sweden
        'SI', // Slovenia
        'SK', // Slovakia
        'SM', // San Marino
        'VA', // Vatican City State
        'YT', // Mayotte (IBAN uses FR)
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
            'AD' => 'Andorra',
            'AL' => 'Albania',
            'AT' => 'Austria',
            'AX' => 'Åland Islands',
            'BE' => 'Belgium',
            'BG' => 'Bulgaria',
            'BL' => 'Saint Barthélemy',
            'CH' => 'Switzerland',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'DE' => 'Germany',
            'DK' => 'Denmark',
            'EE' => 'Estonia',
            'ES' => 'Spain',
            'FI' => 'Finland',
            'FR' => 'France',
            'GB' => 'United Kingdom',
            'GF' => 'French Guiana',
            'GG' => 'Guernsey',
            'GI' => 'Gibraltar',
            'GP' => 'Guadeloupe',
            'GR' => 'Greece',
            'HR' => 'Croatia',
            'HU' => 'Hungary',
            'IE' => 'Ireland',
            'IM' => 'Isle of Man',
            'IS' => 'Iceland',
            'IT' => 'Italy',
            'JE' => 'Jersey',
            'LI' => 'Liechtenstein',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'LV' => 'Latvia',
            'MC' => 'Monaco',
            'MD' => 'Moldova',
            'ME' => 'Montenegro',
            'MF' => 'Saint Martin, French part',
            'MK' => 'North Macedonia',
            'MQ' => 'Martinique',
            'MT' => 'Malta',
            'NL' => 'Netherlands',
            'NO' => 'Norway',
            'PL' => 'Poland',
            'PM' => 'Saint Pierre and Miquelon',
            'PT' => 'Portugal',
            'RE' => 'Réunion',
            'RO' => 'Romania',
            'RS' => 'Serbia',
            'SE' => 'Sweden',
            'SI' => 'Slovenia',
            'SK' => 'Slovakia',
            'SM' => 'San Marino',
            'VA' => 'Vatican City State',
            'YT' => 'Mayotte',
        ];

        return $countryNames[$countryCode] ?? $countryCode;
    }
}
