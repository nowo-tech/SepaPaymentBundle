<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Model\Mandate;

use Nowo\SepaPaymentBundle\Model\Mandate\MandateStatus;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for MandateStatus enum.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class MandateStatusTest extends TestCase
{
    public function testGetValues(): void
    {
        $values = MandateStatus::getValues();

        $this->assertIsArray($values);
        $this->assertContains('active', $values);
        $this->assertContains('expired', $values);
        $this->assertContains('revoked', $values);
        $this->assertContains('suspended', $values);
        $this->assertCount(4, $values);
    }

    public function testIsValidWithValidStatus(): void
    {
        $this->assertTrue(MandateStatus::isValid('active'));
        $this->assertTrue(MandateStatus::isValid('expired'));
        $this->assertTrue(MandateStatus::isValid('revoked'));
        $this->assertTrue(MandateStatus::isValid('suspended'));
    }

    public function testIsValidWithInvalidStatus(): void
    {
        $this->assertFalse(MandateStatus::isValid('invalid'));
        $this->assertFalse(MandateStatus::isValid(''));
        $this->assertFalse(MandateStatus::isValid('ACTIVE'));
    }
}
