<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Event;

use Nowo\SepaPaymentBundle\Event\AfterValidationEvent;
use Nowo\SepaPaymentBundle\Event\BeforeValidationEvent;
use PHPUnit\Framework\TestCase;

/**
 * Tests for validation events.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class ValidationEventTest extends TestCase
{
    /**
     * Test BeforeValidationEvent.
     */
    public function testBeforeValidationEvent(): void
    {
        $event = new BeforeValidationEvent('iban', 'ES9121000418450200051332');

        $this->assertEquals('iban', $event->getValidationType());
        $this->assertEquals('ES9121000418450200051332', $event->getValue());
        $this->assertNull($event->getResult());
        $this->assertFalse($event->hasResult());

        $event->setResult(true);
        $this->assertTrue($event->getResult());
        $this->assertTrue($event->hasResult());

        $event->setResult(false);
        $this->assertFalse($event->getResult());
    }

    /**
     * Test AfterValidationEvent.
     */
    public function testAfterValidationEvent(): void
    {
        $event = new AfterValidationEvent('bic', 'CAIXESBBXXX', true);

        $this->assertEquals('bic', $event->getValidationType());
        $this->assertEquals('CAIXESBBXXX', $event->getValue());
        $this->assertTrue($event->getResult());

        $event2 = new AfterValidationEvent('iban', 'INVALID', false);
        $this->assertFalse($event2->getResult());
    }
}
