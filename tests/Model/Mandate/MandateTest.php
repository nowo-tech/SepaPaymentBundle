<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Model\Mandate;

use Nowo\SepaPaymentBundle\Model\Mandate\Mandate;
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
}
