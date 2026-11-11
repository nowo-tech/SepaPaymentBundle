<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Integration;

use DateTime;
use Nowo\SepaPaymentBundle\Lookup\BicLookupService;
use Nowo\SepaPaymentBundle\Service\MandateService;
use Nowo\SepaPaymentBundle\Tests\Kernel\TestKernel;
use RuntimeException;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;

use function sprintf;

/**
 * Simulates FrankenPHP worker mode with the kernel kept alive and no services_resetter
 * (FRANKENPHP_RESET_KERNEL=false), plus a long-running Messenger consumer and a console command:
 * one booted kernel, the same service instances, many units of work for two tenants in alternation,
 * never a call to services_resetter. Each unit must only see its own tenant's mandates and BIC mappings,
 * and memory must stay flat.
 *
 * N = 500 per scenario (HTTP and Messenger): the mandate service is a hot path but each unit creates a
 * mandate with history, so 500 keeps the suite fast while showing any per-unit growth. Memory margin: 1 MiB.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class WorkerModeIntegrationTest extends KernelTestCase
{
    private const ITERATIONS    = 500;
    private const WARMUP        = 50;
    private const MEMORY_MARGIN = 1024 * 1024;

    private const TENANTS = [
        'A' => ['iban' => 'ES9121000418450200051332', 'name' => 'Alice', 'bic' => 'TENANTAXX'],
        'B' => ['iban' => 'ES7921000813610123456789', 'name' => 'Bob', 'bic' => 'TENANTBXX'],
    ];

    private const DEFAULT_BIC = 'CAIXESBB';

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testMandatesAndRuntimeBicMappingsDoNotLeakToTheNextRequest(): void
    {
        self::bootKernel(['debug' => false]);
        $container = self::getContainer();

        $mandateService = $container->get(MandateService::class);
        $this->assertInstanceOf(MandateService::class, $mandateService);
        $bicLookup = $container->get(BicLookupService::SERVICE_NAME);
        $this->assertInstanceOf(BicLookupService::class, $bicLookup);

        // Request 1: user A creates a mandate and adds a tenant-specific mapping
        $this->dispatchRequest(HttpKernelInterface::MAIN_REQUEST);
        $mandateService->createMandate('MANDATE-USER-A', new DateTime('2024-01-01'), 'ES9121000418450200051332', 'Alice');
        $bicLookup->addMapping('ES', '2100', 'TENANTAXX');

        // A fragment sub-request within request 1 keeps the request state
        $this->dispatchRequest(HttpKernelInterface::SUB_REQUEST);
        $this->assertNotNull($mandateService->findMandate('MANDATE-USER-A'));
        $this->assertSame('TENANTAXX', $bicLookup->lookupBic('ES9121000418450200051332'));

        // Request 2: user B on the same worker, same service instances
        $this->dispatchRequest(HttpKernelInterface::MAIN_REQUEST);
        $this->assertSame($mandateService, $container->get(MandateService::class));
        $this->assertNull($mandateService->findMandate('MANDATE-USER-A'));
        $this->assertSame([], $mandateService->findActiveMandates());
        $this->assertSame([], $mandateService->findMandatesByDebtorIban('ES9121000418450200051332'));
        $this->assertSame([], $mandateService->getMandateHistory('MANDATE-USER-A'));
        $this->assertSame('CAIXESBB', $bicLookup->lookupBic('ES9121000418450200051332'));

        // The same mandate ID can be created again (no "already exists" from a previous request)
        $mandate = $mandateService->createMandate('MANDATE-USER-A', new DateTime('2024-02-01'), 'GB82WEST12345698765432', 'Bob');
        $this->assertSame('Bob', $mandate->getDebtorName());
    }

    /**
     * Many HTTP requests, two tenants in alternation: state is gone at kernel.terminate already
     * (checked before the next kernel.request), and memory stays flat.
     */
    public function testManyHttpRequestsForTwoTenantsKeepStateIsolatedAndMemoryFlat(): void
    {
        self::bootKernel(['debug' => false]);
        [$mandateService, $bicLookup] = $this->services();
        $kernel                       = self::$kernel;
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);

        $baseline = 0;
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            if ($i === self::WARMUP) {
                gc_collect_cycles();
                $baseline = memory_get_usage();
            }

            $tenant  = $i % 2 === 0 ? 'A' : 'B';
            $request = Request::create('/mandates/' . $tenant);
            $request->attributes->set('_controller', 'already_routed');

            $this->dispatch(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST), KernelEvents::REQUEST);
            $this->runUnitOfWork($mandateService, $bicLookup, $tenant, $i);
            $this->dispatch(new TerminateEvent($kernel, $request, new Response()), KernelEvents::TERMINATE);

            $this->assertStoreEmpty($mandateService, $bicLookup, sprintf('after kernel.terminate of request %d', $i));
        }

        gc_collect_cycles();
        $this->assertLessThan(self::MEMORY_MARGIN, memory_get_usage() - $baseline, 'Memory grew across HTTP requests');
    }

    /**
     * A Messenger consumer: one kernel, many messages for two tenants, some failing. Each message must
     * not see the previous message's mandates, and the store is empty right after WorkerMessageHandled /
     * WorkerMessageFailed (before the next message is received).
     */
    public function testManyMessengerMessagesForTwoTenantsKeepStateIsolatedAndMemoryFlat(): void
    {
        self::bootKernel(['debug' => false]);
        [$mandateService, $bicLookup] = $this->services();

        $baseline = 0;
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            if ($i === self::WARMUP) {
                gc_collect_cycles();
                $baseline = memory_get_usage();
            }

            $tenant   = $i % 2 === 0 ? 'A' : 'B';
            $envelope = new Envelope(new stdClass());

            $this->dispatch(new WorkerMessageReceivedEvent($envelope, 'async'), WorkerMessageReceivedEvent::class);
            $this->runUnitOfWork($mandateService, $bicLookup, $tenant, $i);

            if ($i % 5 === 0) {
                $this->dispatch(new WorkerMessageFailedEvent($envelope, 'async', new RuntimeException('handler failed')), WorkerMessageFailedEvent::class);
                $this->assertStoreEmpty($mandateService, $bicLookup, sprintf('after WorkerMessageFailedEvent of message %d', $i));
            } else {
                $this->dispatch(new WorkerMessageHandledEvent($envelope, 'async'), WorkerMessageHandledEvent::class);
                $this->assertStoreEmpty($mandateService, $bicLookup, sprintf('after WorkerMessageHandledEvent of message %d', $i));
            }
        }

        gc_collect_cycles();
        $this->assertLessThan(self::MEMORY_MARGIN, memory_get_usage() - $baseline, 'Memory grew across Messenger messages');
    }

    /**
     * Messenger state is reset at the start of a message too, so a message whose previous unit of work
     * ended without a handled/failed event (e.g. code run outside the worker loop) still starts empty.
     */
    public function testMessengerMessageStartsEmptyEvenWithoutAPreviousHandledEvent(): void
    {
        self::bootKernel(['debug' => false]);
        [$mandateService, $bicLookup] = $this->services();

        $mandateService->createMandate('MANDATE-OUTSIDE', new DateTime('2024-01-01'), self::TENANTS['A']['iban'], 'Alice');
        $bicLookup->addMapping('ES', '2100', 'TENANTAXX');

        $this->dispatch(new WorkerMessageReceivedEvent(new Envelope(new stdClass()), 'async'), WorkerMessageReceivedEvent::class);

        $this->assertStoreEmpty($mandateService, $bicLookup, 'at WorkerMessageReceivedEvent');
    }

    public function testConsoleTerminateResetsState(): void
    {
        self::bootKernel(['debug' => false]);
        [$mandateService, $bicLookup] = $this->services();

        foreach (['A', 'B', 'A'] as $i => $tenant) {
            $this->runUnitOfWork($mandateService, $bicLookup, $tenant, $i);
            $this->dispatch(
                new ConsoleTerminateEvent(new Command('app:sepa-import'), new ArrayInput([]), new NullOutput(), Command::SUCCESS),
                ConsoleEvents::TERMINATE,
            );
            $this->assertStoreEmpty($mandateService, $bicLookup, sprintf('after console.terminate %d', $i));
        }
    }

    /**
     * One unit of work for $tenant: it must start with no other tenant's data, then writes its own.
     */
    private function runUnitOfWork(MandateService $mandateService, BicLookupService $bicLookup, string $tenant, int $i): void
    {
        $other = $tenant === 'A' ? 'B' : 'A';
        $data  = self::TENANTS[$tenant];

        $this->assertSame([], $mandateService->findActiveMandates(), sprintf('Unit %d (%s) sees active mandates of a previous unit', $i, $tenant));
        $this->assertSame([], $mandateService->findMandatesByDebtorIban(self::TENANTS[$other]['iban']), sprintf('Unit %d (%s) sees tenant %s debtor IBAN', $i, $tenant, $other));
        $this->assertNull($mandateService->findMandate('MANDATE-' . $other), sprintf('Unit %d (%s) sees tenant %s mandate', $i, $tenant, $other));
        $this->assertSame([], $mandateService->getMandateHistory('MANDATE-' . $other));
        $this->assertSame(self::DEFAULT_BIC, $bicLookup->lookupBic($data['iban']), sprintf('Unit %d (%s) sees a runtime BIC mapping of a previous unit', $i, $tenant));

        // Same mandate ID per tenant every time: "already exists" would mean the previous unit leaked.
        $mandate = $mandateService->createMandate('MANDATE-' . $tenant, new DateTime('2024-01-01'), $data['iban'], $data['name']);
        $bicLookup->addMapping('ES', '2100', $data['bic']);

        $this->assertSame($data['name'], $mandate->getDebtorName());
        $this->assertCount(1, $mandateService->findActiveMandates());
        $this->assertSame($data['bic'], $bicLookup->lookupBic($data['iban']));
    }

    private function assertStoreEmpty(MandateService $mandateService, BicLookupService $bicLookup, string $when): void
    {
        $this->assertSame([], $mandateService->findActiveMandates(), 'Mandates survive ' . $when);
        foreach (self::TENANTS as $tenant => $data) {
            $this->assertNull($mandateService->findMandate('MANDATE-' . $tenant), sprintf('Mandate of tenant %s survives %s', $tenant, $when));
            $this->assertSame([], $mandateService->findMandatesByDebtorIban($data['iban']));
            $this->assertSame([], $mandateService->getMandateHistory('MANDATE-' . $tenant));
        }
        $this->assertSame(self::DEFAULT_BIC, $bicLookup->lookupBic(self::TENANTS['A']['iban']), 'Runtime BIC mapping survives ' . $when);
    }

    /**
     * @return array{0: MandateService, 1: BicLookupService}
     */
    private function services(): array
    {
        $container      = self::getContainer();
        $mandateService = $container->get(MandateService::class);
        $this->assertInstanceOf(MandateService::class, $mandateService);
        $bicLookup = $container->get(BicLookupService::SERVICE_NAME);
        $this->assertInstanceOf(BicLookupService::class, $bicLookup);

        return [$mandateService, $bicLookup];
    }

    private function dispatch(object $event, string $eventName): void
    {
        $dispatcher = self::getContainer()->get('event_dispatcher');
        $this->assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
        $dispatcher->dispatch($event, $eventName);
    }

    private function dispatchRequest(int $requestType): void
    {
        $kernel = self::$kernel;
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);

        $request = Request::create('/');
        $request->attributes->set('_controller', 'already_routed');

        $this->dispatch(new RequestEvent($kernel, $request, $requestType), KernelEvents::REQUEST);
    }
}
