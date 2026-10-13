<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\DependencyInjection;

use Nowo\SepaPaymentBundle\Cache\ValidationCache;
use Nowo\SepaPaymentBundle\DependencyInjection\Configuration;
use Nowo\SepaPaymentBundle\DependencyInjection\NowoSepaPaymentExtension;
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

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

    /**
     * Regression: ValidationCache was never wired (always a no-op). It is now wired from validation_cache.*.
     */
    public function testValidationCacheIsWiredFromConfig(): void
    {
        $container = new ContainerBuilder();
        (new NowoSepaPaymentExtension())->load([['validation_cache' => ['service' => 'app.sepa_psr16_cache', 'ttl' => 600]]], $container);

        $definition = $container->getDefinition(ValidationCache::class);
        $cache      = $definition->getArgument('$cache');
        $this->assertInstanceOf(Reference::class, $cache);
        $this->assertSame('app.sepa_psr16_cache', (string) $cache);
        $this->assertSame(600, $definition->getArgument('$defaultTtl'));
    }

    /**
     * Without a configured service the validation cache stays disabled (explicit null, no autowiring).
     */
    public function testValidationCacheIsDisabledByDefault(): void
    {
        $container = new ContainerBuilder();
        (new NowoSepaPaymentExtension())->load([], $container);

        $definition = $container->getDefinition(ValidationCache::class);
        $this->assertNull($definition->getArgument('$cache'));
        $this->assertSame(3600, $definition->getArgument('$defaultTtl'));
    }
}
