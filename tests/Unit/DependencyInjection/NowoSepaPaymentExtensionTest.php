<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\DependencyInjection;

use Nowo\SepaPaymentBundle\DependencyInjection\Configuration;
use Nowo\SepaPaymentBundle\DependencyInjection\NowoSepaPaymentExtension;
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests for NowoSepaPaymentExtension.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
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

    /**
     * Regression: default_currency used to be a dead parameter; it is now injected into both generators.
     */
    public function testDefaultCurrencyIsWiredIntoGenerators(): void
    {
        $container = new ContainerBuilder();
        (new NowoSepaPaymentExtension())->load([], $container);

        foreach ([CreditTransferGenerator::class, DirectDebitGenerator::class] as $generator) {
            $this->assertSame(
                '%nowo_sepa_payment.default_currency%',
                $container->getDefinition($generator)->getArgument('$defaultCurrency'),
            );
        }
    }
}
