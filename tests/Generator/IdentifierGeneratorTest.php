<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Generator;

use Nowo\SepaPaymentBundle\Generator\IdentifierGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for IdentifierGenerator.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class IdentifierGeneratorTest extends TestCase
{
    /**
     * Identifier generator instance.
     *
     * @var IdentifierGenerator
     */
    private IdentifierGenerator $generator;

    /**
     * Sets up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->generator = new IdentifierGenerator();
    }

    /**
     * Tests message ID generation.
     *
     * @return void
     */
    public function testGenerateMessageId(): void
    {
        $id1 = $this->generator->generateMessageId();
        $id2 = $this->generator->generateMessageId();

        $this->assertStringStartsWith('MSG-', $id1);
        $this->assertNotEquals($id1, $id2);
        $this->assertMatchesRegularExpression('/^MSG-\d{14}-[a-f0-9]{8}$/', $id1);
    }

    /**
     * Tests message ID generation with custom prefix.
     *
     * @return void
     */
    public function testGenerateMessageIdWithPrefix(): void
    {
        $id = $this->generator->generateMessageId('CUSTOM');
        $this->assertStringStartsWith('CUSTOM-', $id);
    }

    /**
     * Tests payment info ID generation.
     *
     * @return void
     */
    public function testGeneratePaymentInfoId(): void
    {
        $id1 = $this->generator->generatePaymentInfoId();
        $id2 = $this->generator->generatePaymentInfoId();

        $this->assertStringStartsWith('PMT-', $id1);
        $this->assertNotEquals($id1, $id2);
        $this->assertMatchesRegularExpression('/^PMT-\d{14}-[a-f0-9]{8}$/', $id1);
    }

    /**
     * Tests payment info ID generation with custom prefix.
     *
     * @return void
     */
    public function testGeneratePaymentInfoIdWithPrefix(): void
    {
        $id = $this->generator->generatePaymentInfoId('PAY');
        $this->assertStringStartsWith('PAY-', $id);
    }

    /**
     * Tests end-to-end ID generation.
     *
     * @return void
     */
    public function testGenerateEndToEndId(): void
    {
        $id1 = $this->generator->generateEndToEndId();
        $id2 = $this->generator->generateEndToEndId();

        $this->assertStringStartsWith('E2E-', $id1);
        $this->assertNotEquals($id1, $id2);
        $this->assertMatchesRegularExpression('/^E2E-\d{14}-[a-f0-9]{8}$/', $id1);
    }

    /**
     * Tests end-to-end ID generation with custom prefix.
     *
     * @return void
     */
    public function testGenerateEndToEndIdWithPrefix(): void
    {
        $id = $this->generator->generateEndToEndId('TRANS');
        $this->assertStringStartsWith('TRANS-', $id);
    }

    /**
     * Tests mandate ID generation.
     *
     * @return void
     */
    public function testGenerateMandateId(): void
    {
        $id1 = $this->generator->generateMandateId();
        $id2 = $this->generator->generateMandateId();

        $this->assertStringStartsWith('MANDATE-', $id1);
        $this->assertNotEquals($id1, $id2);
        $this->assertMatchesRegularExpression('/^MANDATE-\d{14}-[a-f0-9]{8}$/', $id1);
    }

    /**
     * Tests mandate ID generation with custom prefix.
     *
     * @return void
     */
    public function testGenerateMandateIdWithPrefix(): void
    {
        $id = $this->generator->generateMandateId('MAND');
        $this->assertStringStartsWith('MAND-', $id);
    }

    /**
     * Tests custom ID generation.
     *
     * @return void
     */
    public function testGenerateCustomId(): void
    {
        $id1 = $this->generator->generateCustomId('TEST');
        $id2 = $this->generator->generateCustomId('TEST');

        $this->assertStringStartsWith('TEST-', $id1);
        $this->assertNotEquals($id1, $id2);
        $this->assertMatchesRegularExpression('/^TEST-\d{14}-[a-f0-9]{8}$/', $id1);
    }
}
