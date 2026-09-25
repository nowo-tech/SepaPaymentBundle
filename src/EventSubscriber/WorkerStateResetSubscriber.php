<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Resets the bundle's request-scoped services at the start of every main request.
 *
 * Long-running workers (FrankenPHP worker mode, RoadRunner, Swoole) keep the container
 * between requests and may not run the services_resetter; this subscriber makes the
 * in-memory state of the bundle (mandate store, runtime BIC mappings) per request
 * regardless of that. Sub-requests (fragments, ESI) are ignored.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class WorkerStateResetSubscriber implements EventSubscriberInterface
{
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
            KernelEvents::REQUEST => ['onKernelRequest', 4096],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        foreach ($this->resettables as $service) {
            $service->reset();
        }
    }
}
