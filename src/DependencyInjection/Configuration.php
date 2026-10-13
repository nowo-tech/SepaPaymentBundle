<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use function is_string;

/**
 * Configuration class for the SepaPayment bundle.
 * Defines the configuration structure and default values.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class Configuration implements ConfigurationInterface
{
    public const ALIAS = 'nowo_sepa_payment';

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
                    ->info('Default currency code (ISO 4217, 3 uppercase letters) for generated transactions without an explicit currency. SEPA schemes only support EUR; generators reject any other currency.')
                    ->validate()
                        ->ifTrue(static fn (mixed $value): bool => !is_string($value) || preg_match('/^[A-Z]{3}$/', $value) !== 1)
                        ->thenInvalid('Invalid default_currency %s: expected a 3-letter uppercase ISO 4217 code (SEPA: "EUR").')
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
