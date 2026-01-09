<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator;

use Nowo\SepaPaymentBundle\Cache\ValidationCacheInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Cached BIC validator wrapper.
 * Caches validation results to improve performance for repeated validations.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class CachedBicValidator
{
    public const SERVICE_NAME = 'nowo_sepa_payment.validator.cached_bic_validator';

    /**
     * Constructor.
     *
     * @param BicValidator             $bicValidator BIC validator instance
     * @param ValidationCacheInterface $cache        Optional cache instance
     */
    public function __construct(
        private BicValidator $bicValidator,
        private ?ValidationCacheInterface $cache = null
    ) {
    }

    /**
     * Validates a BIC (with caching).
     *
     * @param string $bic The BIC to validate
     *
     * @return bool True if the BIC is valid, false otherwise
     */
    public function isValid(string $bic): bool
    {
        $normalized = $this->bicValidator->normalize($bic);
        $cacheKey = 'bic_' . $normalized;

        // Check cache first
        if (null !== $this->cache) {
            $cached = $this->cache->get($cacheKey);
            if (null !== $cached) {
                return $cached;
            }
        }

        // Validate
        $result = $this->bicValidator->isValid($bic);

        // Cache result
        if (null !== $this->cache) {
            $this->cache->set($cacheKey, $result);
        }

        return $result;
    }

    /**
     * Normalizes a BIC by removing spaces and converting to uppercase.
     *
     * @param string $bic The BIC to normalize
     *
     * @return string The normalized BIC
     */
    public function normalize(string $bic): string
    {
        return $this->bicValidator->normalize($bic);
    }

    /**
     * Extracts the bank code from a BIC.
     *
     * @param string $bic The BIC
     *
     * @return string The bank code (4 letters)
     */
    public function getBankCode(string $bic): string
    {
        return $this->bicValidator->getBankCode($bic);
    }

    /**
     * Extracts the country code from a BIC.
     *
     * @param string $bic The BIC
     *
     * @return string The country code (2 letters)
     */
    public function getCountryCode(string $bic): string
    {
        return $this->bicValidator->getCountryCode($bic);
    }

    /**
     * Extracts the location code from a BIC.
     *
     * @param string $bic The BIC
     *
     * @return string The location code (2 characters)
     */
    public function getLocationCode(string $bic): string
    {
        return $this->bicValidator->getLocationCode($bic);
    }

    /**
     * Extracts the branch code from a BIC.
     *
     * @param string $bic The BIC
     *
     * @return string|null The branch code (3 characters) or null if not present
     */
    public function getBranchCode(string $bic): ?string
    {
        return $this->bicValidator->getBranchCode($bic);
    }
}
