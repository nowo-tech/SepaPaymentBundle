<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Event\Mandate;

use DateTimeImmutable;

/**
 * Dispatched after the sequence type of a mandate changes and the mandate is saved.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class MandateSequenceTypeChangedEvent extends MandateEvent
{
    /**
     * Constructor.
     *
     * @param string $mandateId The mandate id (UMR)
     * @param string $status The mandate status
     * @param DateTimeImmutable $occurredAt When the change happened
     * @param string $previousSequenceType The sequence type before the change
     * @param string $sequenceType The sequence type after the change
     */
    public function __construct(
        string $mandateId,
        string $status,
        DateTimeImmutable $occurredAt,
        private readonly string $previousSequenceType,
        private readonly string $sequenceType,
    ) {
        parent::__construct($mandateId, $status, $occurredAt);
    }

    /**
     * Gets the sequence type before the change.
     */
    public function getPreviousSequenceType(): string
    {
        return $this->previousSequenceType;
    }

    /**
     * Gets the sequence type after the change.
     */
    public function getSequenceType(): string
    {
        return $this->sequenceType;
    }
}
