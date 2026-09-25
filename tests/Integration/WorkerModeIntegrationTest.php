<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Integration;

use DateTime;
use Nowo\SepaPaymentBundle\Lookup\BicLookupService;
use Nowo\SepaPaymentBundle\Service\MandateService;
use Nowo\SepaPaymentBundle\Tests\Kernel\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Simulates FrankenPHP worker mode without services_resetter: one booted kernel,
 * two consecutive main requests on the same service instances, no reset() in between.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class WorkerModeIntegrationTest extends KernelTestCase
{
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

    private function dispatchRequest(int $requestType): void
    {
        $dispatcher = self::getContainer()->get('event_dispatcher');
        $this->assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
        $kernel = self::$kernel;
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);

        $request = Request::create('/');
        $request->attributes->set('_controller', 'already_routed');

        $dispatcher->dispatch(new RequestEvent($kernel, $request, $requestType), KernelEvents::REQUEST);
    }
}
