<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Service;

use DateTimeImmutable;
use DateTimeInterface;
use Nowo\SepaPaymentBundle\Event\Mandate\MandateCreatedEvent;
use Nowo\SepaPaymentBundle\Event\Mandate\MandateEvent;
use Nowo\SepaPaymentBundle\Event\Mandate\MandateReactivatedEvent;
use Nowo\SepaPaymentBundle\Event\Mandate\MandateRevokedEvent;
use Nowo\SepaPaymentBundle\Event\Mandate\MandateSequenceTypeChangedEvent;
use Nowo\SepaPaymentBundle\Event\Mandate\MandateSuspendedEvent;
use Nowo\SepaPaymentBundle\Model\Mandate\Mandate;
use Nowo\SepaPaymentBundle\Model\Mandate\MandateHistory;
use Nowo\SepaPaymentBundle\Repository\MandateRepository;
use Nowo\SepaPaymentBundle\Repository\MandateRepositoryInterface;
use Nowo\SepaPaymentBundle\Service\MandateService;
use Nowo\SepaPaymentBundle\Service\MandateServiceInterface;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Clock\MockClock;

use function assert;

/**
 * Bar gap 4 (events carry ids) on the DDD path: a host-owned repository adapter (here an array standing for the host's
 * table) and a PSR-14 dispatcher; events are dispatched after the save, with ids and plain values only.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class MandateServiceEventsTest extends TestCase
{
    public function testEventsCarryIdsAndAreDispatchedAfterTheSave(): void
    {
        $repository = new class implements MandateRepositoryInterface {
            /** @var array<string, array{status: string, sequence: string}> rows of the host's own table */
            public array $rows = [];

            /** @var array<string, Mandate> */
            private array $identityMap = [];

            public function save(Mandate $mandate): void
            {
                $this->rows[$mandate->getMandateId()]        = ['status' => $mandate->getStatus()->value, 'sequence' => $mandate->getSequenceType()];
                $this->identityMap[$mandate->getMandateId()] = $mandate;
            }

            public function findById(string $mandateId): ?Mandate
            {
                return $this->identityMap[$mandateId] ?? null;
            }

            public function findByDebtorIban(string $debtorIban): array
            {
                return [];
            }

            public function findActive(): array
            {
                return [];
            }

            public function findExpired(?DateTimeInterface $beforeDate = null): array
            {
                return [];
            }

            public function delete(string $mandateId): bool
            {
                return false;
            }

            public function addHistory(MandateHistory $history): void
            {
            }

            public function getHistory(string $mandateId): array
            {
                return [];
            }
        };
        $dispatcher = new class($repository) implements EventDispatcherInterface {
            /** @var list<array{MandateEvent, array{status: string, sequence: string}|null}> */
            public array $events = [];

            public function __construct(private readonly object $repository)
            {
            }

            public function dispatch(object $event): object
            {
                assert($event instanceof MandateEvent);
                // what the host table holds when the listener runs
                $this->events[] = [$event, $this->repository->rows[$event->getMandateId()] ?? null];

                return $event;
            }
        };
        $clock   = new MockClock('2026-11-10 12:00:00');
        $service = new MandateService($repository, $clock, $dispatcher);
        self::assertInstanceOf(MandateServiceInterface::class, $service);

        $service->createMandate('UMR-1', new DateTimeImmutable('2026-11-01'), 'ES9121000418450200051332', 'Debtor');
        $service->updateSequenceType('UMR-1', 'RCUR');
        $service->suspendMandate('UMR-1');
        $service->reactivateMandate('UMR-1');
        $service->revokeMandate('UMR-1', 'customer request');

        $classes = array_map(static fn (array $entry): string => $entry[0]::class, $dispatcher->events);
        self::assertSame([MandateCreatedEvent::class, MandateSequenceTypeChangedEvent::class, MandateSuspendedEvent::class, MandateReactivatedEvent::class, MandateRevokedEvent::class], $classes);

        foreach ($dispatcher->events as [$event, $row]) {
            self::assertSame('UMR-1', $event->getMandateId());
            self::assertSame('2026-11-10 12:00:00', $event->getOccurredAt()->format('Y-m-d H:i:s'));
            self::assertSame($event->getStatus(), $row['status'] ?? null, 'dispatched after the save');
        }

        [$created]  = $dispatcher->events[0];
        [$sequence] = $dispatcher->events[1];
        [$suspend]  = $dispatcher->events[2];
        [$reactive] = $dispatcher->events[3];
        [$revoked]  = $dispatcher->events[4];
        self::assertInstanceOf(MandateCreatedEvent::class, $created);
        self::assertSame(['active', 'FRST', 'CORE'], [$created->getStatus(), $created->getSequenceType(), $created->getType()]);
        self::assertInstanceOf(MandateSequenceTypeChangedEvent::class, $sequence);
        self::assertSame(['FRST', 'RCUR'], [$sequence->getPreviousSequenceType(), $sequence->getSequenceType()]);
        self::assertInstanceOf(MandateSuspendedEvent::class, $suspend);
        self::assertSame(['active', 'suspended'], [$suspend->getPreviousStatus(), $suspend->getStatus()]);
        self::assertInstanceOf(MandateReactivatedEvent::class, $reactive);
        self::assertSame(['suspended', 'active'], [$reactive->getPreviousStatus(), $reactive->getStatus()]);
        self::assertInstanceOf(MandateRevokedEvent::class, $revoked);
        self::assertSame(['active', 'revoked', 'customer request'], [$revoked->getPreviousStatus(), $revoked->getStatus(), $revoked->getReason()]);
    }

    public function testNoDispatcherMeansNoEvents(): void
    {
        $service = new MandateService(new MandateRepository());
        $mandate = $service->createMandate('UMR-2', new DateTimeImmutable('2026-11-01'), 'ES9121000418450200051332', 'Debtor');
        self::assertSame('UMR-2', $mandate->getMandateId());
    }
}
