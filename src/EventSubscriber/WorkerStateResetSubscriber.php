<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Resets the bundle's request-scoped services at every unit-of-work boundary.
 *
 * Long-running workers (FrankenPHP worker mode, RoadRunner, Swoole, Messenger consumers) keep the
 * container between units of work and may not run the services_resetter; this subscriber makes the
 * in-memory state of the bundle (mandate store, runtime BIC mappings) per unit of work regardless of that:
 *
 * - HTTP: at the start of every main request and at `kernel.terminate` (sub-requests such as fragments
 *   and ESI keep the state of their main request);
 * - Messenger consumers: when a message is received and after it is handled or failed;
 * - console: at `console.terminate` (long-running commands that call the mandate service).
 *
 * The Messenger events are subscribed by name, so symfony/messenger stays optional.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class WorkerStateResetSubscriber implements EventSubscriberInterface
{
    /** Messenger event names (class names), subscribed without requiring symfony/messenger. */
    public const MESSENGER_WORKER_MESSAGE_RECEIVED = 'Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent';
    public const MESSENGER_WORKER_MESSAGE_HANDLED  = 'Symfony\Component\Messenger\Event\WorkerMessageHandledEvent';
    public const MESSENGER_WORKER_MESSAGE_FAILED   = 'Symfony\Component\Messenger\Event\WorkerMessageFailedEvent';

    /**
     * @param iterable<ResetInterface> $resettables Services tagged nowo_sepa_payment.request_scoped
     */
    public function __construct(
        private readonly iterable $resettables = [],
    ) {
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST                   => ['onKernelRequest', 4096],
            KernelEvents::TERMINATE                 => ['resetState', -4096],
            self::MESSENGER_WORKER_MESSAGE_RECEIVED => ['resetState', 4096],
            self::MESSENGER_WORKER_MESSAGE_HANDLED  => ['resetState', -4096],
            self::MESSENGER_WORKER_MESSAGE_FAILED   => ['resetState', -4096],
            ConsoleEvents::TERMINATE                => ['resetState', -4096],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->resetState();
    }

    /**
     * Empties every request-scoped service. Called at the end of a unit of work (HTTP terminate,
     * Messenger handled/failed, console terminate) and at the start of a Messenger message.
     */
    public function resetState(): void
    {
        foreach ($this->resettables as $service) {
            $service->reset();
        }
    }
}
