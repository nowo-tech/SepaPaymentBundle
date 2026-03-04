<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Model\Mandate;

use DateTime;
use Nowo\SepaPaymentBundle\Model\Mandate\MandateHistory;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for MandateHistory.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class MandateHistoryTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $timestamp = new DateTime('2024-01-15 10:30:00');
        $history   = new MandateHistory(
            'MANDATE-001',
            $timestamp,
            'status_change',
            'active',
            'revoked',
            'Mandate revoked by customer',
        );

        $this->assertEquals('MANDATE-001', $history->getMandateId());
        $this->assertSame($timestamp, $history->getTimestamp());
        $this->assertEquals('status_change', $history->getEventType());
        $this->assertEquals('active', $history->getOldValue());
        $this->assertEquals('revoked', $history->getNewValue());
        $this->assertEquals('Mandate revoked by customer', $history->getDescription());
    }

    public function testGetDescriptionWhenNull(): void
    {
        $history = new MandateHistory(
            'MANDATE-002',
            new DateTime(),
            'sequence_change',
            'FRST',
            'RCUR',
        );

        $this->assertNull($history->getDescription());
    }

    public function testToArray(): void
    {
        $timestamp = new DateTime('2024-01-15 10:30:00');
        $history   = new MandateHistory(
            'MANDATE-001',
            $timestamp,
            'status_change',
            'pending',
            'active',
            'Created',
        );

        $array = $history->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('MANDATE-001', $array['mandateId']);
        $this->assertEquals('2024-01-15 10:30:00', $array['timestamp']);
        $this->assertEquals('status_change', $array['eventType']);
        $this->assertEquals('pending', $array['oldValue']);
        $this->assertEquals('active', $array['newValue']);
        $this->assertEquals('Created', $array['description']);
    }

    public function testToArrayWithNullDescription(): void
    {
        $history = new MandateHistory('M-1', new DateTime(), 'created', '', 'active');
        $array   = $history->toArray();

        $this->assertArrayHasKey('description', $array);
        $this->assertNull($array['description']);
    }
}
