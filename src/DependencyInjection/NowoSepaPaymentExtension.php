<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Extension class that loads and manages the SepaPayment bundle configuration.
 * Handles service definitions and configuration processing.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class NowoSepaPaymentExtension extends Extension
{
    /**
     * Loads the services configuration and processes the bundle configuration.
     * Loads the services.yaml file from the bundle's Resources/config directory.
     *
     * @param array<string, mixed> $configs   Array of configuration values
     * @param ContainerBuilder     $container The container builder object
     *
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        // Set parameters from configuration
        $container->setParameter('nowo_sepa_payment.default_currency', $config['default_currency']);
    }

    /**
     * Returns the alias for this extension.
     *
     * @return string The alias
     */
    public function getAlias(): string
    {
        return 'nowo_sepa_payment';
    }
}
