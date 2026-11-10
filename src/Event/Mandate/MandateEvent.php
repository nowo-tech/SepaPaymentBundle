<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Event\Mandate;

use DateTimeImmutable;
use Nowo\SepaPaymentBundle\Model\Mandate\MandateStatus;
use Nowo\SepaPaymentBundle\Service\MandateService;

/**
 * Base of the mandate events dispatched by {@see MandateService}. Events carry IDs
 * and plain values only (no mandate object), so DDD hosts can react without the bundle model.
 *
 * They are dispatched after `MandateRepositoryInterface::save()` and the history entry. The bundle never opens or
 * commits a transaction: when the host's repository writes inside the host's own transaction, a listener runs before
 * that transaction commits (react after your commit, e.g. through Messenger, if you need the row to exist).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
abstract class MandateEvent
{
    /**
     * Constructor.
     *
     * @param string $mandateId The mandate id (UMR)
     * @param string $status The mandate status after the change ({@see MandateStatus} value)
     * @param DateTimeImmutable $occurredAt When the change happened (the service clock)
     */
    public function __construct(
        private readonly string $mandateId,
        private readonly string $status,
        private readonly DateTimeImmutable $occurredAt,
    ) {
    }

    /**
     * Gets the mandate id (UMR).
     */
    public function getMandateId(): string
    {
        return $this->mandateId;
    }

    /**
     * Gets the mandate status after the change (MandateStatus value, e.g. "active").
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Gets when the change happened.
     */
    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
