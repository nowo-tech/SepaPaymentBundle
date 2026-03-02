<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator;

use Nowo\SepaPaymentBundle\Cache\ValidationCacheInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Cached IBAN validator wrapper.
 * Caches validation results to improve performance for repeated validations.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class CachedIbanValidator
{
    public const SERVICE_NAME = 'nowo_sepa_payment.validator.cached_iban_validator';

    /**
     * Constructor.
     *
     * @param IbanValidator $ibanValidator IBAN validator instance
     * @param ValidationCacheInterface $cache Optional cache instance
     */
    public function __construct(
        private IbanValidator $ibanValidator,
        private ?ValidationCacheInterface $cache = null
    ) {
    }

    /**
     * Validates an IBAN (with caching).
     *
     * @param string $iban The IBAN to validate
     *
     * @return bool True if the IBAN is valid, false otherwise
     */
    public function isValid(string $iban): bool
    {
        $normalized = $this->ibanValidator->normalize($iban);
        $cacheKey   = 'iban_' . $normalized;

        // Check cache first
        if ($this->cache !== null) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        // Validate
        $result = $this->ibanValidator->isValid($iban);

        // Cache result
        if ($this->cache !== null) {
            $this->cache->set($cacheKey, $result);
        }

        return $result;
    }

    /**
     * Normalizes an IBAN by removing spaces and converting to uppercase.
     *
     * @param string $iban The IBAN to normalize
     *
     * @return string The normalized IBAN
     */
    public function normalize(string $iban): string
    {
        return $this->ibanValidator->normalize($iban);
    }

    /**
     * Formats an IBAN with spaces every 4 characters for readability.
     *
     * @param string $iban The IBAN to format
     *
     * @return string The formatted IBAN
     */
    public function format(string $iban): string
    {
        return $this->ibanValidator->format($iban);
    }

    /**
     * Extracts the country code from an IBAN.
     *
     * @param string $iban The IBAN
     *
     * @return string The country code (2 letters)
     */
    public function getCountryCode(string $iban): string
    {
        return $this->ibanValidator->getCountryCode($iban);
    }

    /**
     * Extracts the check digits from an IBAN.
     *
     * @param string $iban The IBAN
     *
     * @return string The check digits (2 digits)
     */
    public function getCheckDigits(string $iban): string
    {
        return $this->ibanValidator->getCheckDigits($iban);
    }

    /**
     * Extracts the BBAN (Basic Bank Account Number) from an IBAN.
     *
     * @param string $iban The IBAN
     *
     * @return string The BBAN
     */
    public function getBban(string $iban): string
    {
        return $this->ibanValidator->getBban($iban);
    }

    /**
     * Calculates check digits for an IBAN.
     *
     * @param string $iban The IBAN with placeholder check digits (e.g., "ES00...")
     *
     * @return string The calculated check digits (2 digits)
     */
    public function calculateCheckDigits(string $iban): string
    {
        return $this->ibanValidator->calculateCheckDigits($iban);
    }
}
