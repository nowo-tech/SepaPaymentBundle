<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Repository;

use Nowo\SepaPaymentBundle\Model\Mandate\Mandate;
use Nowo\SepaPaymentBundle\Model\Mandate\MandateHistory;
use Nowo\SepaPaymentBundle\Model\Mandate\MandateStatus;
use Nowo\SepaPaymentBundle\Repository\MandateRepository;
use PHPUnit\Framework\TestCase;

/**
 * Tests for MandateRepository.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class MandateRepositoryTest extends TestCase
{
    private MandateRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new MandateRepository();
    }

    public function testSaveAndFindById(): void
    {
        $mandate = new Mandate(
            'MANDATE-001',
            new \DateTime('2024-01-01'),
            'ES9121000418450200051332',
            'John Doe',
            'CORE',
            'FRST'
        );
        $this->repository->save($mandate);

        $found = $this->repository->findById('MANDATE-001');
        $this->assertNotNull($found);
        $this->assertEquals('MANDATE-001', $found->getMandateId());
        $this->assertNull($this->repository->findById('NONEXISTENT'));
    }

    public function testFindByDebtorIban(): void
    {
        $mandate1 = new Mandate('M1', new \DateTime('2024-01-01'), 'ES9121000418450200051332', 'John', 'CORE', 'FRST');
        $mandate2 = new Mandate('M2', new \DateTime('2024-01-02'), 'ES9121000418450200051332', 'Jane', 'CORE', 'RCUR');
        $mandate3 = new Mandate('M3', new \DateTime('2024-01-03'), 'GB82WEST12345698765432', 'Bob', 'CORE', 'FRST');

        $this->repository->save($mandate1);
        $this->repository->save($mandate2);
        $this->repository->save($mandate3);

        $result = $this->repository->findByDebtorIban('ES9121000418450200051332');
        $this->assertCount(2, $result);
        $this->assertEmpty($this->repository->findByDebtorIban('DE89370400440532013000'));
    }

    public function testFindActive(): void
    {
        $mandate = new Mandate('M1', new \DateTime('2024-01-01'), 'ES9121000418450200051332', 'John', 'CORE', 'FRST');
        $mandate->setStatus(MandateStatus::ACTIVE);
        $this->repository->save($mandate);

        $active = $this->repository->findActive();
        $this->assertCount(1, $active);

        $mandate->setStatus(MandateStatus::REVOKED);
        $this->repository->save($mandate);
        $this->assertCount(0, $this->repository->findActive());
    }

    public function testFindExpired(): void
    {
        $oldDate = new \DateTime('2020-01-01');
        $mandate = new Mandate('M-EXP', $oldDate, 'ES9121000418450200051332', 'John', 'CORE', 'FRST');
        $this->repository->save($mandate);

        $expired = $this->repository->findExpired(new \DateTime('2024-06-01'));
        $this->assertCount(1, $expired);

        $expiredBefore = $this->repository->findExpired(new \DateTime('2022-01-01'));
        $this->assertCount(0, $expiredBefore);
    }

    public function testDelete(): void
    {
        $mandate = new Mandate('M-DEL', new \DateTime('2024-01-01'), 'ES9121000418450200051332', 'John', 'CORE', 'FRST');
        $this->repository->save($mandate);
        $this->assertTrue($this->repository->delete('M-DEL'));
        $this->assertNull($this->repository->findById('M-DEL'));
        $this->assertFalse($this->repository->delete('M-DEL'));
    }

    public function testAddHistoryAndGetHistory(): void
    {
        $mandate = new Mandate('M-HIST', new \DateTime('2024-01-01'), 'ES9121000418450200051332', 'John', 'CORE', 'FRST');
        $this->repository->save($mandate);

        $history1 = new MandateHistory('M-HIST', new \DateTime('2024-01-01 10:00:00'), 'status_change', 'PENDING', MandateStatus::ACTIVE->value);
        $history2 = new MandateHistory('M-HIST', new \DateTime('2024-01-02 10:00:00'), 'status_change', MandateStatus::ACTIVE->value, MandateStatus::REVOKED->value);
        $this->repository->addHistory($history2);
        $this->repository->addHistory($history1);

        $history = $this->repository->getHistory('M-HIST');
        $this->assertCount(2, $history);
        // Sorted oldest first: history[0] = history1 (PENDING->ACTIVE), history[1] = history2 (ACTIVE->REVOKED)
        $this->assertEquals('status_change', $history[0]->getEventType());
        $this->assertEquals(MandateStatus::ACTIVE->value, $history[0]->getNewValue());
        $this->assertEquals(MandateStatus::REVOKED->value, $history[1]->getNewValue());

        $this->assertEmpty($this->repository->getHistory('UNKNOWN'));
    }

    public function testClear(): void
    {
        $mandate = new Mandate('M-CLR', new \DateTime('2024-01-01'), 'ES9121000418450200051332', 'John', 'CORE', 'FRST');
        $this->repository->save($mandate);
        $this->repository->addHistory(new MandateHistory('M-CLR', new \DateTime(), 'status_change', 'PENDING', MandateStatus::ACTIVE->value));

        $this->repository->clear();
        $this->assertNull($this->repository->findById('M-CLR'));
        $this->assertEmpty($this->repository->getHistory('M-CLR'));
    }
}
