<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\Mandate;

use function in_array;

/**
 * Mandate status enumeration.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
enum MandateStatus: string
{
    case ACTIVE    = 'active';
    case EXPIRED   = 'expired';
    case REVOKED   = 'revoked';
    case SUSPENDED = 'suspended';

    /**
     * Gets all valid status values.
     *
     * @return array<string> Array of status values
     */
    public static function getValues(): array
    {
        return array_map(static fn (self $status) => $status->value, self::cases());
    }

    /**
     * Checks if a status string is valid.
     *
     * @param string $status The status to check
     *
     * @return bool True if valid, false otherwise
     */
    public static function isValid(string $status): bool
    {
        return in_array($status, self::getValues(), true);
    }
}
