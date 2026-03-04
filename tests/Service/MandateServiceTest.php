<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Service;

use DateTime;
use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Model\Mandate\Mandate;
use Nowo\SepaPaymentBundle\Model\Mandate\MandateStatus;
use Nowo\SepaPaymentBundle\Repository\MandateRepository;
use Nowo\SepaPaymentBundle\Service\MandateService;
use PHPUnit\Framework\TestCase;

/**
 * Tests for MandateService.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class MandateServiceTest extends TestCase
{
    private MandateService $service;
    private MandateRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new MandateRepository();
        $this->service    = new MandateService($this->repository);
    }

    public function testCreateMandate(): void
    {
        $mandate = $this->service->createMandate(
            'MANDATE-001',
            new DateTime('2024-01-01'),
            'ES9121000418450200051332',
            'John Doe',
            'CORE',
            'FRST',
        );

        $this->assertInstanceOf(Mandate::class, $mandate);
        $this->assertEquals('MANDATE-001', $mandate->getMandateId());
        $this->assertEquals('ES9121000418450200051332', $mandate->getDebtorIban());
        $this->assertEquals('John Doe', $mandate->getDebtorName());
        $this->assertEquals('CORE', $mandate->getType());
        $this->assertEquals('FRST', $mandate->getSequenceType());
        $this->assertTrue($mandate->isActive());
        $this->assertEquals(MandateStatus::ACTIVE, $mandate->getStatus());
    }

    public function testCreateMandateThrowsExceptionIfExists(): void
    {
        $this->service->createMandate(
            'MANDATE-001',
            new DateTime('2024-01-01'),
            'ES9121000418450200051332',
            'John Doe',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Mandate with ID 'MANDATE-001' already exists");

        $this->service->createMandate(
            'MANDATE-001',
            new DateTime('2024-01-01'),
            'ES9121000418450200051332',
            'John Doe',
        );
    }

    public function testUpdateSequenceType(): void
    {
        $this->service->createMandate(
            'MANDATE-001',
            new DateTime('2024-01-01'),
            'ES9121000418450200051332',
            'John Doe',
            'CORE',
            'FRST',
        );

        $updatedMandate = $this->service->updateSequenceType('MANDATE-001', 'RCUR');

        $this->assertEquals('RCUR', $updatedMandate->getSequenceType());
    }

    public function testUpdateSequenceTypeThrowsExceptionIfInvalidTransition(): void
    {
        $this->service->createMandate(
            'MANDATE-001',
            new DateTime('2024-01-01'),
            'ES9121000418450200051332',
            'John Doe',
            'CORE',
            'FRST',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid sequence type transition from 'FRST' to 'OOFF'");

        $this->service->updateSequenceType('MANDATE-001', 'OOFF');
    }

    public function testRevokeMandate(): void
    {
        $this->service->createMandate(
            'MANDATE-001',
            new DateTime('2024-01-01'),
            'ES9121000418450200051332',
            'John Doe',
        );

        $revokedMandate = $this->service->revokeMandate('MANDATE-001', 'Customer request');

        $this->assertEquals(MandateStatus::REVOKED, $revokedMandate->getStatus());
        $this->assertFalse($revokedMandate->isActive());
        $this->assertNotNull($revokedMandate->getRevocationDate());
        $this->assertEquals('Customer request', $revokedMandate->getRevocationReason());
    }

    public function testSuspendMandate(): void
    {
        $this->service->createMandate(
            'MANDATE-001',
            new DateTime('2024-01-01'),
            'ES9121000418450200051332',
            'John Doe',
        );

        $suspendedMandate = $this->service->suspendMandate('MANDATE-001');

        $this->assertEquals(MandateStatus::SUSPENDED, $suspendedMandate->getStatus());
        $this->assertFalse($suspendedMandate->isActive());
    }

    public function testReactivateMandate(): void
    {
        $this->service->createMandate(
            'MANDATE-001',
            new DateTime('2024-01-01'),
            'ES9121000418450200051332',
            'John Doe',
        );

        $this->service->suspendMandate('MANDATE-001');
        $reactivatedMandate = $this->service->reactivateMandate('MANDATE-001');

        $this->assertEquals(MandateStatus::ACTIVE, $reactivatedMandate->getStatus());
        $this->assertTrue($reactivatedMandate->isActive());
    }

    public function testValidateMandateForTransaction(): void
    {
        $this->service->createMandate(
            'MANDATE-001',
            new DateTime('2024-01-01'),
            'ES9121000418450200051332',
            'John Doe',
            'CORE',
            'FRST',
        );

        $this->assertTrue($this->service->validateMandateForTransaction('MANDATE-001', 'RCUR'));
        $this->assertFalse($this->service->validateMandateForTransaction('MANDATE-001', 'OOFF'));
    }

    public function testValidateMandateForTransactionReturnsFalseIfNotActive(): void
    {
        $this->service->createMandate(
            'MANDATE-001',
            new DateTime('2024-01-01'),
            'ES9121000418450200051332',
            'John Doe',
        );

        $this->service->revokeMandate('MANDATE-001');

        $this->assertFalse($this->service->validateMandateForTransaction('MANDATE-001', 'RCUR'));
    }

    public function testIsValidSequenceTransition(): void
    {
        $this->assertTrue($this->service->isValidSequenceTransition('FRST', 'RCUR'));
        $this->assertTrue($this->service->isValidSequenceTransition('FRST', 'FNAL'));
        $this->assertTrue($this->service->isValidSequenceTransition('RCUR', 'RCUR'));
        $this->assertTrue($this->service->isValidSequenceTransition('RCUR', 'FNAL'));
        $this->assertFalse($this->service->isValidSequenceTransition('FRST', 'OOFF'));
        $this->assertFalse($this->service->isValidSequenceTransition('FNAL', 'RCUR'));
    }

    public function testGetMandateHistory(): void
    {
        $this->service->createMandate(
            'MANDATE-001',
            new DateTime('2024-01-01'),
            'ES9121000418450200051332',
            'John Doe',
        );

        $this->service->updateSequenceType('MANDATE-001', 'RCUR');
        $this->service->revokeMandate('MANDATE-001', 'Test');

        $history = $this->service->getMandateHistory('MANDATE-001');

        $this->assertCount(3, $history);
        $this->assertEquals('created', $history[0]->getEventType());
        $this->assertEquals('sequence_change', $history[1]->getEventType());
        $this->assertEquals('status_change', $history[2]->getEventType());
    }

    public function testFindMandatesByDebtorIban(): void
    {
        $this->service->createMandate(
            'MANDATE-001',
            new DateTime('2024-01-01'),
            'ES9121000418450200051332',
            'John Doe',
        );

        $this->service->createMandate(
            'MANDATE-002',
            new DateTime('2024-01-02'),
            'ES9121000418450200051332',
            'John Doe',
        );

        $this->service->createMandate(
            'MANDATE-003',
            new DateTime('2024-01-03'),
            'GB82WEST12345698765432',
            'Jane Smith',
        );

        $mandates = $this->service->findMandatesByDebtorIban('ES9121000418450200051332');

        $this->assertCount(2, $mandates);
        $this->assertEquals('MANDATE-001', $mandates[0]->getMandateId());
        $this->assertEquals('MANDATE-002', $mandates[1]->getMandateId());
    }

    public function testFindExpiredMandates(): void
    {
        // Create an old mandate (expired)
        $this->service->createMandate(
            'MANDATE-OLD',
            new DateTime('2020-01-01'), // 4 years ago
            'ES9121000418450200051332',
            'John Doe',
        );

        // Create a recent mandate (not expired)
        $this->service->createMandate(
            'MANDATE-NEW',
            new DateTime('2024-01-01'),
            'ES9121000418450200051332',
            'John Doe',
        );

        $expiredMandates = $this->service->findExpiredMandates();

        $this->assertCount(1, $expiredMandates);
        $this->assertEquals('MANDATE-OLD', $expiredMandates[0]->getMandateId());
    }

    public function testFindExpiredMandatesWithBeforeDate(): void
    {
        $this->service->createMandate(
            'MANDATE-OLD',
            new DateTime('2020-01-01'),
            'ES9121000418450200051332',
            'John Doe',
        );

        $expiredBefore2023 = $this->service->findExpiredMandates(new DateTime('2023-06-01'));
        $this->assertCount(1, $expiredBefore2023);

        $expiredBefore2019 = $this->service->findExpiredMandates(new DateTime('2019-12-31'));
        $this->assertCount(0, $expiredBefore2019);
    }

    public function testFindMandate(): void
    {
        $this->service->createMandate(
            'MANDATE-001',
            new DateTime('2024-01-01'),
            'ES9121000418450200051332',
            'John Doe',
        );

        $mandate = $this->service->findMandate('MANDATE-001');
        $this->assertInstanceOf(Mandate::class, $mandate);
        $this->assertEquals('MANDATE-001', $mandate->getMandateId());

        $this->assertNull($this->service->findMandate('NONEXISTENT'));
    }

    public function testFindActiveMandates(): void
    {
        $this->service->createMandate(
            'MANDATE-001',
            new DateTime('2024-01-01'),
            'ES9121000418450200051332',
            'John Doe',
        );
        $this->service->createMandate(
            'MANDATE-002',
            new DateTime('2024-01-02'),
            'ES9121000418450200051332',
            'Jane Doe',
        );
        $this->service->revokeMandate('MANDATE-002');

        $active = $this->service->findActiveMandates();
        $this->assertCount(1, $active);
        $this->assertEquals('MANDATE-001', $active[0]->getMandateId());
    }

    public function testValidateMandateForTransactionReturnsFalseWhenMandateNotFound(): void
    {
        $this->assertFalse($this->service->validateMandateForTransaction('NONEXISTENT', 'FRST'));
    }

    public function testValidateMandateForTransactionReturnsFalseWhenExpired(): void
    {
        $this->service->createMandate(
            'MANDATE-OLD',
            new DateTime('2020-01-01'),
            'ES9121000418450200051332',
            'John Doe',
        );

        $this->assertFalse($this->service->validateMandateForTransaction('MANDATE-OLD', 'FRST'));
    }

    public function testValidateMandateForTransactionReturnsFalseWhenInvalidSequenceTransition(): void
    {
        $this->service->createMandate(
            'MANDATE-001',
            new DateTime('2024-01-01'),
            'ES9121000418450200051332',
            'John Doe',
            'CORE',
            'FNAL',
        );

        $this->assertFalse($this->service->validateMandateForTransaction('MANDATE-001', 'RCUR'));
    }

    public function testRevokeMandateWithoutReason(): void
    {
        $this->service->createMandate(
            'MANDATE-001',
            new DateTime('2024-01-01'),
            'ES9121000418450200051332',
            'John Doe',
        );

        $revoked = $this->service->revokeMandate('MANDATE-001');
        $this->assertEquals(MandateStatus::REVOKED, $revoked->getStatus());
        $this->assertNull($revoked->getRevocationReason());
    }

    public function testUpdateSequenceTypeThrowsWhenMandateNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Mandate with ID 'MISSING' not found");

        $this->service->updateSequenceType('MISSING', 'RCUR');
    }

    public function testRevokeMandateThrowsWhenMandateNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Mandate with ID 'MISSING' not found");

        $this->service->revokeMandate('MISSING');
    }

    public function testSuspendMandateThrowsWhenMandateNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Mandate with ID 'MISSING' not found");

        $this->service->suspendMandate('MISSING');
    }

    public function testReactivateMandateThrowsWhenMandateNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Mandate with ID 'MISSING' not found");

        $this->service->reactivateMandate('MISSING');
    }
}
