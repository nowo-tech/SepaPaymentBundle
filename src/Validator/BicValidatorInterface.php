<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator;

/**
 * Bic validation contract. {@see BicValidator} is the default implementation (aliased to this interface) and
 * {@see CachedBicValidator} its caching decorator; constraint validators depend on this interface.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
interface BicValidatorInterface
{
    /**
     * Validates a BIC according to ISO 13616 standard.
     * BIC format: 4 letters (bank code) + 2 letters (country code) + 2 alphanumeric (location) + 3 alphanumeric (branch, optional).
     *
     * @param string $bic The BIC to validate
     *
     * @return bool True if the BIC is valid, false otherwise
     */
    public function isValid(string $bic): bool;

    /**
     * Normalizes a BIC by removing spaces and converting to uppercase.
     *
     * @param string $bic The BIC to normalize
     *
     * @return string The normalized BIC
     */
    public function normalize(string $bic): string;

    /**
     * Extracts the bank code from a BIC.
     *
     * @param string $bic The BIC
     *
     * @return string The bank code (4 letters)
     */
    public function getBankCode(string $bic): string;

    /**
     * Extracts the country code from a BIC.
     *
     * @param string $bic The BIC
     *
     * @return string The country code (2 letters)
     */
    public function getCountryCode(string $bic): string;

    /**
     * Extracts the location code from a BIC.
     *
     * @param string $bic The BIC
     *
     * @return string The location code (2 alphanumeric)
     */
    public function getLocationCode(string $bic): string;

    /**
     * Extracts the branch code from a BIC (if present).
     *
     * @param string $bic The BIC
     *
     * @return string|null The branch code (3 alphanumeric) or null if not present
     */
    public function getBranchCode(string $bic): ?string;
}
