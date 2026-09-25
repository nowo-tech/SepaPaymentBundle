<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\EventSubscriber;

use DateTime;
use Nowo\SepaPaymentBundle\EventSubscriber\WorkerStateResetSubscriber;
use Nowo\SepaPaymentBundle\Lookup\BicLookupService;
use Nowo\SepaPaymentBundle\Model\Mandate\Mandate;
use Nowo\SepaPaymentBundle\Repository\MandateRepository;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Tests for WorkerStateResetSubscriber (worker mode without services_resetter).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class WorkerStateResetSubscriberTest extends TestCase
{
    public function testSubscribesToKernelRequestWithHighPriority(): void
    {
        $this->assertSame(
            [KernelEvents::REQUEST => ['onKernelRequest', 4096]],
            WorkerStateResetSubscriber::getSubscribedEvents(),
        );
    }

    public function testTwoConsecutiveMainRequestsDoNotShareState(): void
    {
        $repository = new MandateRepository();
        $bicLookup  = new BicLookupService(new IbanValidator());
        $subscriber = new WorkerStateResetSubscriber([$repository, $bicLookup]);

        // Request 1 (tenant A)
        $subscriber->onKernelRequest($this->createEvent(HttpKernelInterface::MAIN_REQUEST));
        $repository->save(new Mandate('M-TENANT-A', new DateTime('2024-01-01'), 'ES9121000418450200051332', 'Alice', 'CORE', 'FRST'));
        $bicLookup->addMapping('ES', '2100', 'TENANTAXX');
        $this->assertSame('TENANTAXX', $bicLookup->lookupBic('ES9121000418450200051332'));

        // Request 2 (tenant B) on the same instances, no reset() by the framework
        $subscriber->onKernelRequest($this->createEvent(HttpKernelInterface::MAIN_REQUEST));
        $this->assertNull($repository->findById('M-TENANT-A'));
        $this->assertSame([], $repository->findByDebtorIban('ES9121000418450200051332'));
        $this->assertSame('CAIXESBB', $bicLookup->lookupBic('ES9121000418450200051332'));
    }

    public function testSubRequestDoesNotResetState(): void
    {
        $repository = new MandateRepository();
        $subscriber = new WorkerStateResetSubscriber([$repository]);

        $repository->save(new Mandate('M-1', new DateTime('2024-01-01'), 'ES9121000418450200051332', 'Alice', 'CORE', 'FRST'));
        $subscriber->onKernelRequest($this->createEvent(HttpKernelInterface::SUB_REQUEST));

        $this->assertNotNull($repository->findById('M-1'));
    }

    public function testWorksWithoutResettables(): void
    {
        $subscriber = new WorkerStateResetSubscriber();
        $subscriber->onKernelRequest($this->createEvent(HttpKernelInterface::MAIN_REQUEST));

        $this->addToAssertionCount(1);
    }

    private function createEvent(int $requestType): RequestEvent
    {
        return new RequestEvent($this->createMock(HttpKernelInterface::class), new Request(), $requestType);
    }
}
