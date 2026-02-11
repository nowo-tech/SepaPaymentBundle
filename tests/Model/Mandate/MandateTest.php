<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Model\Mandate;

use Nowo\SepaPaymentBundle\Model\Mandate\Mandate;
use Nowo\SepaPaymentBundle\Model\Mandate\MandateStatus;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for Mandate.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class MandateTest extends TestCase
{
    /**
     * Tests mandate creation.
     *
     * @return void
     */
    public function testMandateCreation(): void
    {
        $signatureDate = new \DateTime('2024-01-15');
        $mandate = new Mandate(
            'MANDATE-001',
            $signatureDate,
            'ES9121000418450200051332',
            'John Doe',
            'CORE',
            'FRST'
        );

        $this->assertEquals('MANDATE-001', $mandate->getMandateId());
        $this->assertEquals($signatureDate, $mandate->getSignatureDate());
        $this->assertEquals('ES9121000418450200051332', $mandate->getDebtorIban());
        $this->assertEquals('John Doe', $mandate->getDebtorName());
        $this->assertEquals('CORE', $mandate->getType());
        $this->assertEquals('FRST', $mandate->getSequenceType());
        $this->assertTrue($mandate->isActive());
        $this->assertEquals(MandateStatus::ACTIVE, $mandate->getStatus());
    }

    /**
     * Tests setting debtor BIC.
     *
     * @return void
     */
    public function testSetDebtorBic(): void
    {
        $mandate = new Mandate(
            'MANDATE-001',
            new \DateTime(),
            'ES9121000418450200051332',
            'John Doe'
        );

        $mandate->setDebtorBic('CAIXESBBXXX');
        $this->assertEquals('CAIXESBBXXX', $mandate->getDebtorBic());

        $mandate->setDebtorBic(null);
        $this->assertNull($mandate->getDebtorBic());
    }

    /**
     * Tests setting sequence type.
     *
     * @return void
     */
    public function testSetSequenceType(): void
    {
        $mandate = new Mandate(
            'MANDATE-001',
            new \DateTime(),
            'ES9121000418450200051332',
            'John Doe',
            'CORE',
            'FRST'
        );

        $mandate->setSequenceType('RCUR');
        $this->assertEquals('RCUR', $mandate->getSequenceType());
    }

    /**
     * Tests setting active status.
     *
     * @return void
     */
    public function testSetActive(): void
    {
        $mandate = new Mandate(
            'MANDATE-001',
            new \DateTime(),
            'ES9121000418450200051332',
            'John Doe'
        );

        $mandate->setActive(false);
        $this->assertFalse($mandate->isActive());

        $mandate->setActive(true);
        $this->assertTrue($mandate->isActive());
    }

    public function testSetStatus(): void
    {
        $mandate = new Mandate(
            'MANDATE-001',
            new \DateTime(),
            'ES9121000418450200051332',
            'John Doe'
        );

        $mandate->setStatus(MandateStatus::SUSPENDED);
        $this->assertEquals(MandateStatus::SUSPENDED, $mandate->getStatus());
        $this->assertFalse($mandate->isActive());

        $mandate->setStatus(MandateStatus::ACTIVE);
        $this->assertEquals(MandateStatus::ACTIVE, $mandate->getStatus());
        $this->assertTrue($mandate->isActive());
    }

    public function testGetExpirationDateDefaultsTo36MonthsAfterSignature(): void
    {
        $signatureDate = new \DateTime('2024-01-15');
        $mandate = new Mandate(
            'MANDATE-001',
            $signatureDate,
            'ES9121000418450200051332',
            'John Doe'
        );

        $expiration = $mandate->getExpirationDate();
        $this->assertNotNull($expiration);
        $expected = (clone $signatureDate)->modify('+36 months');
        $this->assertEquals($expected->format('Y-m-d'), $expiration->format('Y-m-d'));
    }

    public function testSetExpirationDate(): void
    {
        $mandate = new Mandate(
            'MANDATE-001',
            new \DateTime('2024-01-15'),
            'ES9121000418450200051332',
            'John Doe'
        );

        $customDate = new \DateTime('2025-06-30');
        $mandate->setExpirationDate($customDate);
        $this->assertSame($customDate, $mandate->getExpirationDate());

        $mandate->setExpirationDate(null);
        $this->assertNotNull($mandate->getExpirationDate()); // falls back to default 36 months
    }

    public function testIsExpired(): void
    {
        $mandate = new Mandate(
            'MANDATE-001',
            new \DateTime('2020-01-01'),
            'ES9121000418450200051332',
            'John Doe'
        );

        $this->assertTrue($mandate->isExpired(new \DateTime('2024-01-01')));
        $this->assertTrue($mandate->isExpired());

        $recentMandate = new Mandate(
            'MANDATE-002',
            new \DateTime('2024-01-01'),
            'ES9121000418450200051332',
            'John Doe'
        );
        $this->assertFalse($recentMandate->isExpired(new \DateTime('2024-06-01')));
    }

    public function testRevoke(): void
    {
        $mandate = new Mandate(
            'MANDATE-001',
            new \DateTime(),
            'ES9121000418450200051332',
            'John Doe'
        );

        $mandate->revoke('Customer request');
        $this->assertEquals(MandateStatus::REVOKED, $mandate->getStatus());
        $this->assertFalse($mandate->isActive());
        $this->assertNotNull($mandate->getRevocationDate());
        $this->assertEquals('Customer request', $mandate->getRevocationReason());

        $mandate2 = new Mandate('M-2', new \DateTime(), 'ES9121000418450200051332', 'Jane');
        $mandate2->revoke(null);
        $this->assertNull($mandate2->getRevocationReason());
    }

    public function testSuspend(): void
    {
        $mandate = new Mandate(
            'MANDATE-001',
            new \DateTime(),
            'ES9121000418450200051332',
            'John Doe'
        );

        $mandate->suspend();
        $this->assertEquals(MandateStatus::SUSPENDED, $mandate->getStatus());
        $this->assertFalse($mandate->isActive());
    }

    public function testReactivate(): void
    {
        $mandate = new Mandate(
            'MANDATE-001',
            new \DateTime(),
            'ES9121000418450200051332',
            'John Doe'
        );
        $mandate->suspend();

        $mandate->reactivate();
        $this->assertEquals(MandateStatus::ACTIVE, $mandate->getStatus());
        $this->assertTrue($mandate->isActive());
    }

    public function testReactivateThrowsWhenExpired(): void
    {
        $mandate = new Mandate(
            'MANDATE-001',
            new \DateTime('2020-01-01'),
            'ES9121000418450200051332',
            'John Doe'
        );
        $mandate->suspend();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot reactivate an expired mandate');

        $mandate->reactivate();
    }
}
