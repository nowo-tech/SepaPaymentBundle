<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator;

/**
 * BIC (Business Identifier Code) validator.
 * Validates BIC format according to ISO 13616 standard.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class BicValidator
{
    /**
     * Validates a BIC according to ISO 13616 standard.
     * BIC format: 4 letters (bank code) + 2 letters (country code) + 2 alphanumeric (location) + 3 alphanumeric (branch, optional)
     *
     * @param string $bic The BIC to validate
     *
     * @return bool True if the BIC is valid, false otherwise
     */
    public function isValid(string $bic): bool
    {
        // Remove spaces and convert to uppercase
        $bic = $this->normalize($bic);

        // BIC must be 8 or 11 characters
        if (strlen($bic) !== 8 && strlen($bic) !== 11) {
            return false;
        }

        // Format: 4 letters (bank) + 2 letters (country) + 2 alphanumeric (location) + 3 alphanumeric (branch, optional)
        return (bool) preg_match('/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/', $bic);
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
        return strtoupper(str_replace(' ', '', trim($bic)));
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
        $normalized = $this->normalize($bic);

        return substr($normalized, 0, 4);
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
        $normalized = $this->normalize($bic);

        return substr($normalized, 4, 2);
    }

    /**
     * Extracts the location code from a BIC.
     *
     * @param string $bic The BIC
     *
     * @return string The location code (2 alphanumeric)
     */
    public function getLocationCode(string $bic): string
    {
        $normalized = $this->normalize($bic);

        return substr($normalized, 6, 2);
    }

    /**
     * Extracts the branch code from a BIC (if present).
     *
     * @param string $bic The BIC
     *
     * @return string|null The branch code (3 alphanumeric) or null if not present
     */
    public function getBranchCode(string $bic): ?string
    {
        $normalized = $this->normalize($bic);

        if (strlen($normalized) === 11) {
            return substr($normalized, 8, 3);
        }

        return null;
    }
}
