<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Event\Mandate;

use DateTimeImmutable;

/**
 * Dispatched after a mandate is suspended and saved.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class MandateSuspendedEvent extends MandateEvent
{
    /**
     * Constructor.
     *
     * @param string $mandateId The mandate id (UMR)
     * @param string $status The mandate status after the change
     * @param DateTimeImmutable $occurredAt When the change happened
     * @param string $previousStatus The mandate status before the change
     */
    public function __construct(
        string $mandateId,
        string $status,
        DateTimeImmutable $occurredAt,
        private readonly string $previousStatus,
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
}
