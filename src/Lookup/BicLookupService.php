<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Lookup;

use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Service for looking up BIC codes from IBANs.
 * Uses a local database of common IBAN to BIC mappings.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class BicLookupService implements BicLookupServiceInterface
{
    public const SERVICE_NAME = 'nowo_sepa_payment.lookup.bic_lookup_service';

    /**
     * Local database of IBAN to BIC mappings.
     * PHPStan: property.defaultValue — literal with numeric-looking keys is inferred as array<int|string, string>;
     * fix: initialize in constructor from a method with explicit return type.
     *
     * @var array<string, array<string, string>>
     */
    private array $bicDatabase;

    /**
     * Constructor.
     *
     * @param IbanValidator $ibanValidator IBAN validator instance
     * @param object|null $cache Optional cache interface for caching lookups (must implement get/set methods)
     * @param int $cacheTtl Cache TTL in seconds (default: 86400)
     */
    public function __construct(
        /**
         * IBAN validator instance.
         */
        private readonly IbanValidator $ibanValidator,
        /**
         * Cache interface (optional).
         */
        private $cache = null,
        /**
         * Cache TTL in seconds (default: 86400 = 24 hours).
         */
        private readonly int $cacheTtl = 86400
    ) {
        $this->bicDatabase = $this->getDefaultBicDatabase();
    }

    /**
     * Default BIC database (countryCode => [ bankCode => bic ]).
     * Separate method so PHPStan accepts the array<string, array<string, string>> type for the literal.
     *
     * @return array<string, array<string, string>>
     */
    private function getDefaultBicDatabase(): array
    {
        /** @var array<string, array<string, string>> $db PHPStan: literal with numeric keys inferred as int|string; force type. */
        $db = [
            'ES' => [
                '0030' => 'ESPCESMM',
                '0081' => 'BSABESBB',
                '0128' => 'BKBKESMM',
                '0182' => 'BBVAESMM',
                '2038' => 'CAHMESMM',
                '2100' => 'CAIXESBB',
                '0049' => 'BSCHESMM',
                '2080' => 'CAGLESMM',
                '2095' => 'CAZRES2Z',
                '3058' => 'CCRIES2A',
            ],
            'DE' => [
                '10000000' => 'MARKDEFF',
                '10010010' => 'PBNKDEFF',
                '10011001' => 'PBNKDEFF',
                '20050550' => 'HASPDEHH',
                '50010517' => 'INGDDEFF',
                '70020270' => 'HYVEDEMM',
                '70070010' => 'DEUTDEFF',
                '70080000' => 'DRESDEFF',
            ],
            'FR' => [
                '20041' => 'BNPAFRPP',
                '30002' => 'CRLYFRPP',
                '30003' => 'SOGEFRPP',
                '30004' => 'CMCIFRPP',
                '30006' => 'AGRIFRPP',
            ],
            'IT' => [
                '03002' => 'BCITITMM',
                '03069' => 'BCITITMM',
                '06175' => 'CRLYITMM',
            ],
            'GB' => [
                '1600' => 'NWBKGB2L',
                '2000' => 'HBUKGB4B',
                '4000' => 'LOYDGB2L',
                '5000' => 'BARCGB22',
            ],
            'NL' => [
                'ABNA' => 'ABNANL2A',
                'INGB' => 'INGBNL2A',
                'RABO' => 'RABONL2U',
                'SNSB' => 'SNSBNL2A',
            ],
            'BE' => [
                '001' => 'GEBABEBB',
                '068' => 'GKCCBEBB',
                '310' => 'BBRUBEBB',
            ],
            'PT' => [
                '0007' => 'BCPTPTPL',
                '0010' => 'BBPIPTPL',
                '0033' => 'MILNPT1L',
            ],
        ];

        return $db;
    }

    /**
     * Looks up a BIC code for a given IBAN.
     *
     * @param string $iban The IBAN to look up
     *
     * @return string|null The BIC code if found, null otherwise
     */
    public function lookupBic(string $iban): ?string
    {
        // Normalize IBAN
        $normalizedIban = $this->ibanValidator->normalize($iban);

        // Validate IBAN format
        if (!$this->ibanValidator->isValid($normalizedIban)) {
            return null;
        }

        // Check cache first
        if ($this->cache !== null && method_exists($this->cache, 'get')) {
            $cacheKey  = 'bic_lookup_' . md5($normalizedIban);
            $cachedBic = $this->cache->get($cacheKey);
            if ($cachedBic !== null) {
                return $cachedBic;
            }
        }

        // Extract country code
        $countryCode = $this->ibanValidator->getCountryCode($normalizedIban);
        if (!isset($this->bicDatabase[$countryCode])) {
            return null;
        }

        // Extract BBAN (Basic Bank Account Number) - everything after country code and check digits
        $bban = $this->ibanValidator->getBban($normalizedIban);

        // Look up BIC based on country-specific patterns
        $bic = $this->lookupBicByCountry($countryCode, $bban);

        // Cache the result
        if ($this->cache !== null && $bic !== null && method_exists($this->cache, 'set')) {
            $cacheKey = 'bic_lookup_' . md5($normalizedIban);
            $this->cache->set($cacheKey, $bic, $this->cacheTtl);
        }

        return $bic;
    }

    /**
     * Checks if a BIC lookup is available for the given IBAN.
     *
     * @param string $iban The IBAN to check
     *
     * @return bool True if lookup is available, false otherwise
     */
    public function isAvailable(string $iban): bool
    {
        $normalizedIban = $this->ibanValidator->normalize($iban);

        if (!$this->ibanValidator->isValid($normalizedIban)) {
            return false;
        }

        $countryCode = $this->ibanValidator->getCountryCode($normalizedIban);

        return isset($this->bicDatabase[$countryCode]);
    }

    /**
     * Looks up BIC by country code and BBAN.
     *
     * @param string $countryCode Country code (2 letters)
     * @param string $bban BBAN (Basic Bank Account Number)
     *
     * @return string|null The BIC code if found, null otherwise
     */
    private function lookupBicByCountry(string $countryCode, string $bban): ?string
    {
        $countryDatabase = $this->bicDatabase[$countryCode] ?? [];

        // Country-specific lookup patterns
        switch ($countryCode) {
            case 'ES':
            case 'GB':
            case 'NL':
            case 'PT': // Spain: first 4 digits (bank code)
                $bankCode = substr($bban, 0, 4);

                return $countryDatabase[$bankCode] ?? null;

            case 'DE': // Germany: first 8 digits (bank code)
                $bankCode = substr($bban, 0, 8);

                return $countryDatabase[$bankCode] ?? null;

            case 'FR':

            case 'IT': // France: first 5 digits (bank code)
                $bankCode = substr($bban, 0, 5);

                return $countryDatabase[$bankCode] ?? null;

            case 'BE': // Belgium: first 3 digits (bank code)
                $bankCode = substr($bban, 0, 3);

                return $countryDatabase[$bankCode] ?? null;

            default:
                return null;
        }
    }

    /**
     * Adds a custom IBAN to BIC mapping.
     * Useful for adding bank-specific mappings not in the default database.
     *
     * @param string $countryCode Country code (2 letters)
     * @param string $bankCode Bank code (country-specific format)
     * @param string $bic BIC code
     */
    /**
     * PHPStan: addMapping assigns to $this->bicDatabase[$countryCode][$bankCode]; nested type is already declared on $bicDatabase.
     */
    public function addMapping(string $countryCode, string $bankCode, string $bic): void
    {
        if (!isset($this->bicDatabase[$countryCode])) {
            $this->bicDatabase[$countryCode] = [];
        }

        $this->bicDatabase[$countryCode][$bankCode] = $bic;
    }
}
