<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\DependencyInjection;

use Nowo\SepaPaymentBundle\DependencyInjection\Configuration;
use Nowo\SepaPaymentBundle\DependencyInjection\NowoSepaPaymentExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests for NowoSepaPaymentExtension.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class NowoSepaPaymentExtensionTest extends TestCase
{
    public function testGetAlias(): void
    {
        $extension = new NowoSepaPaymentExtension();
        $this->assertEquals(Configuration::ALIAS, $extension->getAlias());
        $this->assertEquals('nowo_sepa_payment', $extension->getAlias());
    }

    public function testLoadSetsDefaultCurrencyParameter(): void
    {
        $container = new ContainerBuilder();
        $extension = new NowoSepaPaymentExtension();

        $extension->load([], $container);

        $this->assertTrue($container->hasParameter('nowo_sepa_payment.default_currency'));
        $this->assertEquals('EUR', $container->getParameter('nowo_sepa_payment.default_currency'));
    }

    public function testLoadWithCustomConfig(): void
    {
        $container = new ContainerBuilder();
        $extension = new NowoSepaPaymentExtension();

        $extension->load([['default_currency' => 'USD']], $container);

        $this->assertEquals('USD', $container->getParameter('nowo_sepa_payment.default_currency'));
    }
}
