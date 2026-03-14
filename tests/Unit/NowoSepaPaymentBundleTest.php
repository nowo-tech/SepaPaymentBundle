<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests;

use Nowo\SepaPaymentBundle\NowoSepaPaymentBundle;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for NowoSepaPaymentBundle.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class NowoSepaPaymentBundleTest extends TestCase
{
    /**
     * Tests bundle instantiation.
     */
    public function testBundleInstantiation(): void
    {
        $bundle = new NowoSepaPaymentBundle();
        $this->assertInstanceOf(NowoSepaPaymentBundle::class, $bundle);
    }

    /**
     * Tests container extension.
     */
    public function testGetContainerExtension(): void
    {
        $bundle    = new NowoSepaPaymentBundle();
        $extension = $bundle->getContainerExtension();

        $this->assertNotNull($extension);
        $this->assertEquals('nowo_sepa_payment', $extension->getAlias());
    }
}
