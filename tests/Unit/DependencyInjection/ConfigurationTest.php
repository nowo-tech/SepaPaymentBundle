<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\DependencyInjection;

use Nowo\SepaPaymentBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/**
 * Tests for Configuration.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class ConfigurationTest extends TestCase
{
    public function testGetConfigTreeBuilder(): void
    {
        $configuration = new Configuration();
        $treeBuilder   = $configuration->getConfigTreeBuilder();

        $this->assertEquals(Configuration::ALIAS, $treeBuilder->buildTree()->getName());
    }

    public function testDefaultConfiguration(): void
    {
        $configuration = new Configuration();
        $processor     = new Processor();
        $config        = $processor->processConfiguration($configuration, []);

        $this->assertArrayHasKey('default_currency', $config);
        $this->assertEquals('EUR', $config['default_currency']);
    }

    public function testCustomConfiguration(): void
    {
        $configuration = new Configuration();
        $processor     = new Processor();
        $config        = $processor->processConfiguration($configuration, [
            ['default_currency' => 'GBP'],
        ]);

        $this->assertEquals('GBP', $config['default_currency']);
    }

    /**
     * default_currency must be a 3-letter uppercase ISO 4217 code.
     */
    #[DataProvider('invalidCurrencyProvider')]
    public function testInvalidDefaultCurrencyIsRejected(mixed $currency): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('expected a 3-letter uppercase ISO 4217 code');

        (new Processor())->processConfiguration(new Configuration(), [['default_currency' => $currency]]);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidCurrencyProvider(): iterable
    {
        yield 'lowercase' => ['eur'];
        yield 'too long' => ['EURO'];
        yield 'empty' => [''];
        yield 'number' => [978];
    }
}
