<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\DependencyInjection;

use Nowo\SepaPaymentBundle\Model\DirectDebit\Pain008Version;
use Nowo\SepaPaymentBundle\Model\DirectDebit\SequenceTypeMode;
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
                ->arrayNode('direct_debit')
                    ->info('Defaults of the direct debit (pain.008) generator; each one can be overridden per call with DirectDebitGenerationOptions.')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode('pain_version')
                            ->values(Pain008Version::values())
                            ->defaultValue(Pain008Version::DEFAULT->value)
                            ->info('ISO 20022 message version written by DirectDebitGenerator: pain.008.001.02, pain.008.001.08 (EPC 2023) or pain.008.001.09 (default).')
                        ->end()
                        ->enumNode('sequence_type_mode')
                            ->values(SequenceTypeMode::values())
                            ->defaultValue(SequenceTypeMode::DEFAULT->value)
                            ->info('How SeqTp is written: per_transaction (from the data; default), always_rcur, or omit (no SeqTp element).')
                        ->end()
                        ->booleanNode('validate_xsd')
                            ->defaultFalse()
                            ->info('Validate every generated direct debit file against the XSD of its version (shipped with the bundle).')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('validation_cache')
                    ->info('Caches IBAN/BIC validation results (CachedIbanValidator, CachedBicValidator) in a PSR-16 cache. Disabled when no service is set.')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('service')
                            ->defaultNull()
                            ->info('Service id of a PSR-16 cache (Psr\\SimpleCache\\CacheInterface), e.g. a Symfony\\Component\\Cache\\Psr16Cache wrapping a dedicated pool. Null disables caching.')
                        ->end()
                        ->integerNode('ttl')
                            ->defaultValue(3600)
                            ->min(1)
                            ->info('Default TTL in seconds for cached validation results.')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
