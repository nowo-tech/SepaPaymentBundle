<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\DependencyInjection;

use Nowo\SepaPaymentBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

/**
 * Tests for Configuration.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class ConfigurationTest extends TestCase
{
    public function testGetConfigTreeBuilder(): void
    {
        $configuration = new Configuration();
        $treeBuilder = $configuration->getConfigTreeBuilder();

        $this->assertNotNull($treeBuilder);
        $this->assertEquals(Configuration::ALIAS, $treeBuilder->buildTree()->getName());
    }

    public function testDefaultConfiguration(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, []);

        $this->assertArrayHasKey('default_currency', $config);
        $this->assertEquals('EUR', $config['default_currency']);
    }

    public function testCustomConfiguration(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [
            ['default_currency' => 'GBP'],
        ]);

        $this->assertEquals('GBP', $config['default_currency']);
    }
}
