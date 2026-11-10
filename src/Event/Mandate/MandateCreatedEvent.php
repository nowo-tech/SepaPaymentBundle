<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Event\Mandate;

use DateTimeImmutable;

/**
 * Dispatched after a mandate is created and saved.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class MandateCreatedEvent extends MandateEvent
{
    /**
     * Constructor.
     *
     * @param string $mandateId The mandate id (UMR)
     * @param string $status The mandate status ("active")
     * @param DateTimeImmutable $occurredAt When the mandate was created
     * @param string $sequenceType The mandate sequence type (FRST, RCUR, OOFF, FNAL)
     * @param string $type The mandate type (CORE, B2B)
     */
    public function __construct(
        string $mandateId,
        string $status,
        DateTimeImmutable $occurredAt,
        private readonly string $sequenceType,
        private readonly string $type,
    ) {
        parent::__construct($mandateId, $status, $occurredAt);
    }

    /**
     * Gets the mandate sequence type.
     */
    public function getSequenceType(): string
    {
        return $this->sequenceType;
    }

    /**
     * Gets the mandate type (CORE, B2B).
     */
    public function getType(): string
    {
        return $this->type;
    }
}
