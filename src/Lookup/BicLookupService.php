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
     * Cache interface (optional).
     *
     * @var object|null
     */
    private $cache;

    /**
     * Cache TTL in seconds (default: 86400 = 24 hours).
     */
    private int $cacheTtl = 86400;

    /**
     * IBAN validator instance.
     */
    private IbanValidator $ibanValidator;

    /**
     * Local database of IBAN to BIC mappings.
     * Key: Bank code pattern (first 4 digits of BBAN for Spanish banks, or country-specific pattern)
     * Value: BIC code.
     *
     * @var array<string, string>
     */
    private array $bicDatabase = [
        // Spanish banks (by bank code - first 4 digits of BBAN)
        'ES' => [
            '0030' => 'ESPCESMM', // Banco Santander
            '0049' => 'BSCHESMM', // Banco Santander (alternative)
            '0081' => 'BSABESBB', // Banco Sabadell
            '0128' => 'BKBKESMM', // Banco Bilbao Vizcaya Argentaria
            '0182' => 'BBVAESMM', // Banco Bilbao Vizcaya Argentaria (alternative)
            '2038' => 'CAHMESMM', // Banco Alcalá
            '2100' => 'CAIXESBB', // CaixaBank
            '0049' => 'BSCHESMM', // Banco Santander
            '2080' => 'CAGLESMM', // Banco Caja España de Inversiones
            '2095' => 'CAZRES2Z', // Caja Rural de Zamora
            '3058' => 'CCRIES2A', // Caja Rural de Navarra
        ],
        // German banks (by bank code - first 8 digits of BBAN)
        'DE' => [
            '10000000' => 'MARKDEFF', // Bundesbank
            '10010010' => 'PBNKDEFF', // Postbank
            '10011001' => 'PBNKDEFF', // Postbank (alternative)
            '20050550' => 'HASPDEHH', // Hamburger Sparkasse
            '50010517' => 'INGDDEFF', // ING-DiBa
            '70020270' => 'HYVEDEMM', // UniCredit Bank
            '70070010' => 'DEUTDEFF', // Deutsche Bank
            '70080000' => 'DRESDEFF', // Commerzbank
        ],
        // French banks (by bank code - first 5 digits of BBAN)
        'FR' => [
            '20041' => 'BNPAFRPP', // BNP Paribas
            '30002' => 'CRLYFRPP', // Crédit Lyonnais
            '30003' => 'SOGEFRPP', // Société Générale
            '30004' => 'CMCIFRPP', // Crédit Mutuel
            '30006' => 'AGRIFRPP', // Crédit Agricole
        ],
        // Italian banks (by bank code - first 5 digits of BBAN)
        'IT' => [
            '03002' => 'BCITITMM', // Intesa Sanpaolo
            '03069' => 'BCITITMM', // Intesa Sanpaolo (alternative)
            '06175' => 'CRLYITMM', // Crédit Agricole Italia
        ],
        // UK banks (by bank code - first 4 digits of BBAN)
        'GB' => [
            '1600' => 'NWBKGB2L', // NatWest
            '2000' => 'HBUKGB4B', // HSBC
            '4000' => 'LOYDGB2L', // Lloyds Bank
            '5000' => 'BARCGB22', // Barclays
        ],
        // Dutch banks (by bank code - first 4 digits of BBAN)
        'NL' => [
            'ABNA' => 'ABNANL2A', // ABN AMRO
            'INGB' => 'INGBNL2A', // ING Bank
            'RABO' => 'RABONL2U', // Rabobank
            'SNSB' => 'SNSBNL2A', // SNS Bank
        ],
        // Belgian banks (by bank code - first 3 digits of BBAN)
        'BE' => [
            '001' => 'GEBABEBB', // BNP Paribas Fortis
            '068' => 'GKCCBEBB', // Belfius
            '310' => 'BBRUBEBB', // ING Belgium
        ],
        // Portuguese banks (by bank code - first 4 digits of BBAN)
        'PT' => [
            '0007' => 'BCPTPTPL', // Banco Comercial Português
            '0010' => 'BBPIPTPL', // Banco Português de Investimento
            '0033' => 'MILNPT1L', // Millennium BCP
        ],
    ];

    /**
     * Constructor.
     *
     * @param IbanValidator $ibanValidator IBAN validator instance
     * @param object|null $cache Optional cache interface for caching lookups (must implement get/set methods)
     * @param int $cacheTtl Cache TTL in seconds (default: 86400)
     */
    public function __construct(
        IbanValidator $ibanValidator,
        $cache = null,
        int $cacheTtl = 86400
    ) {
        $this->ibanValidator = $ibanValidator;
        $this->cache         = $cache;
        $this->cacheTtl      = $cacheTtl;
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
            case 'ES': // Spain: first 4 digits (bank code)
                $bankCode = substr($bban, 0, 4);

                return $countryDatabase[$bankCode] ?? null;

            case 'DE': // Germany: first 8 digits (bank code)
                $bankCode = substr($bban, 0, 8);

                return $countryDatabase[$bankCode] ?? null;

            case 'FR': // France: first 5 digits (bank code)
                $bankCode = substr($bban, 0, 5);

                return $countryDatabase[$bankCode] ?? null;

            case 'IT': // Italy: first 5 digits (bank code)
                $bankCode = substr($bban, 0, 5);

                return $countryDatabase[$bankCode] ?? null;

            case 'GB': // UK: first 4 digits (sort code)
                $bankCode = substr($bban, 0, 4);

                return $countryDatabase[$bankCode] ?? null;

            case 'NL': // Netherlands: first 4 characters (bank code)
                $bankCode = substr($bban, 0, 4);

                return $countryDatabase[$bankCode] ?? null;

            case 'BE': // Belgium: first 3 digits (bank code)
                $bankCode = substr($bban, 0, 3);

                return $countryDatabase[$bankCode] ?? null;

            case 'PT': // Portugal: first 4 digits (bank code)
                $bankCode = substr($bban, 0, 4);

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
    public function addMapping(string $countryCode, string $bankCode, string $bic): void
    {
        if (!isset($this->bicDatabase[$countryCode])) {
            $this->bicDatabase[$countryCode] = [];
        }

        $this->bicDatabase[$countryCode][$bankCode] = $bic;
    }
}
