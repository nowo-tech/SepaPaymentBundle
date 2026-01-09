<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Lookup;

/**
 * Interface for BIC lookup services.
 * Provides methods to look up BIC codes from IBANs.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
interface BicLookupServiceInterface
{
    /**
     * Looks up a BIC code for a given IBAN.
     *
     * @param string $iban The IBAN to look up
     *
     * @return string|null The BIC code if found, null otherwise
     */
    public function lookupBic(string $iban): ?string;

    /**
     * Checks if a BIC lookup is available for the given IBAN.
     *
     * @param string $iban The IBAN to check
     *
     * @return bool True if lookup is available, false otherwise
     */
    public function isAvailable(string $iban): bool;
}
