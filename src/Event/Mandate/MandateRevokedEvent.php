<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Event\Mandate;

use DateTimeImmutable;

/**
 * Dispatched after a mandate is revoked and saved.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class MandateRevokedEvent extends MandateEvent
{
    /**
     * Constructor.
     *
     * @param string $mandateId The mandate id (UMR)
     * @param string $status The mandate status after the change
     * @param DateTimeImmutable $occurredAt When the change happened
     * @param string $previousStatus The mandate status before the change
     * @param string|null $reason The revocation reason
     */
    public function __construct(
        string $mandateId,
        string $status,
        DateTimeImmutable $occurredAt,
        private readonly string $previousStatus,
        private readonly ?string $reason = null,
    ) {
        parent::__construct($mandateId, $status, $occurredAt);
    }

    /**
     * Gets the mandate status before the change.
     */
    public function getPreviousStatus(): string
    {
        return $this->previousStatus;
    }

    /**
     * Gets the revocation reason.
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }
}
