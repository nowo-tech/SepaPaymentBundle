<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration class for the SepaPayment bundle.
 * Defines the configuration structure and default values.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class Configuration implements ConfigurationInterface
{

    public const string ALIAS = 'nowo_sepa_payment';

    /**
     * Builds the configuration tree.
     *
     * @return TreeBuilder The configuration tree builder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ALIAS);

        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('default_currency')
                    ->defaultValue('EUR')
                    ->info('Default currency code for payment (ISO 4217)')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
