<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Event;

use DateTime;
use Nowo\SepaPaymentBundle\Event\AfterCreditTransferGenerationEvent;
use Nowo\SepaPaymentBundle\Event\AfterDirectDebitGenerationEvent;
use Nowo\SepaPaymentBundle\Event\BeforeCreditTransferGenerationEvent;
use Nowo\SepaPaymentBundle\Event\BeforeDirectDebitGenerationEvent;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\CreditTransferData;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitData;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for SEPA Payment Bundle events.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class EventTest extends TestCase
{
    public function testBeforeCreditTransferGenerationEvent(): void
    {
        $data = new CreditTransferData(
            'MSG-001',
            new DateTime(),
            'Test Company',
            'PMT-001',
            'ES9121000418450200051332',
            'Test Company Name',
            new DateTime('tomorrow'),
        );

        $event = new BeforeCreditTransferGenerationEvent($data);

        $this->assertSame($data, $event->getCreditTransferData());

        $newData = new CreditTransferData(
            'MSG-002',
            new DateTime(),
            'New Company',
            'PMT-002',
            'ES9121000418450200051332',
            'New Company Name',
            new DateTime('tomorrow'),
        );

        $event->setCreditTransferData($newData);
        $this->assertSame($newData, $event->getCreditTransferData());
    }

    public function testAfterCreditTransferGenerationEvent(): void
    {
        $xml       = '<?xml version="1.0" encoding="UTF-8"?><Document></Document>';
        $messageId = 'MSG-001';

        $event = new AfterCreditTransferGenerationEvent($xml, $messageId);

        $this->assertSame($xml, $event->getXml());
        $this->assertSame($messageId, $event->getMessageId());

        $newXml = '<?xml version="1.0" encoding="UTF-8"?><Document><Modified></Modified></Document>';
        $event->setXml($newXml);
        $this->assertSame($newXml, $event->getXml());
    }

    public function testBeforeDirectDebitGenerationEvent(): void
    {
        $data = new DirectDebitData(
            'MSG-001',
            'Test Company',
            'PMT-001',
            new DateTime('tomorrow'),
            'Test Company Name',
            'ES9121000418450200051332',
            'FRST',
            'ES98ZZZ09999999999',
            'CORE',
        );

        $event = new BeforeDirectDebitGenerationEvent($data);

        $this->assertSame($data, $event->getDirectDebitData());

        $newData = new DirectDebitData(
            'MSG-002',
            'New Company',
            'PMT-002',
            new DateTime('tomorrow'),
            'New Company Name',
            'ES9121000418450200051332',
            'FRST',
            'ES98ZZZ09999999999',
            'CORE',
        );

        $event->setDirectDebitData($newData);
        $this->assertSame($newData, $event->getDirectDebitData());
    }

    public function testAfterDirectDebitGenerationEvent(): void
    {
        $xml       = '<?xml version="1.0" encoding="UTF-8"?><Document></Document>';
        $messageId = 'MSG-001';

        $event = new AfterDirectDebitGenerationEvent($xml, $messageId);

        $this->assertSame($xml, $event->getXml());
        $this->assertSame($messageId, $event->getMessageId());

        $newXml = '<?xml version="1.0" encoding="UTF-8"?><Document><Modified></Modified></Document>';
        $event->setXml($newXml);
        $this->assertSame($newXml, $event->getXml());
    }
}
