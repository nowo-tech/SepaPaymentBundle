<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\Mandate;

use DateTimeInterface;

/**
 * Mandate history entry.
 * Represents a change in mandate status or sequence type.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class MandateHistory
{
    /**
     * Constructor.
     *
     * @param string $mandateId Mandate identifier
     * @param DateTimeInterface $timestamp Timestamp of the change
     * @param string $eventType Type of event (status_change, sequence_change, etc.)
     * @param string $oldValue Old value
     * @param string $newValue New value
     * @param string|null $description Optional description
     */
    public function __construct(
        private readonly string $mandateId,
        private readonly DateTimeInterface $timestamp,
        private readonly string $eventType,
        private readonly string $oldValue,
        private readonly string $newValue,
        private readonly ?string $description = null
    ) {
    }

    /**
     * Gets the mandate identifier.
     *
     * @return string The mandate identifier
     */
    public function getMandateId(): string
    {
        return $this->mandateId;
    }

    /**
     * Gets the timestamp.
     *
     * @return DateTimeInterface The timestamp
     */
    public function getTimestamp(): DateTimeInterface
    {
        return $this->timestamp;
    }

    /**
     * Gets the event type.
     *
     * @return string The event type
     */
    public function getEventType(): string
    {
        return $this->eventType;
    }

    /**
     * Gets the old value.
     *
     * @return string The old value
     */
    public function getOldValue(): string
    {
        return $this->oldValue;
    }

    /**
     * Gets the new value.
     *
     * @return string The new value
     */
    public function getNewValue(): string
    {
        return $this->newValue;
    }

    /**
     * Gets the description.
     *
     * @return string|null The description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Converts the history entry to an array.
     *
     * @return array<string, mixed> Array representation
     */
    public function toArray(): array
    {
        return [
            'mandateId'   => $this->mandateId,
            'timestamp'   => $this->timestamp->format('Y-m-d H:i:s'),
            'eventType'   => $this->eventType,
            'oldValue'    => $this->oldValue,
            'newValue'    => $this->newValue,
            'description' => $this->description,
        ];
    }
}
