<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\DependencyInjection;

use Nowo\SepaPaymentBundle\DependencyInjection\Configuration;
use Nowo\SepaPaymentBundle\DependencyInjection\NowoSepaPaymentExtension;
use Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * `direct_debit.*` options (2.2): defaults keep the 2.1 output, values are validated and wired into the generator.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class DirectDebitConfigurationTest extends TestCase
{
    public function testDefaults(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), []);

        self::assertSame(['pain_version' => 'pain.008.001.09', 'sequence_type_mode' => 'per_transaction', 'validate_xsd' => false], $config['direct_debit']);
    }

    public function testCustomValuesAreWired(): void
    {
        $container = new ContainerBuilder();
        (new NowoSepaPaymentExtension())->load([['direct_debit' => ['pain_version' => 'pain.008.001.08', 'sequence_type_mode' => 'omit', 'validate_xsd' => true]]], $container);

        self::assertSame('pain.008.001.08', $container->getParameter('nowo_sepa_payment.direct_debit.pain_version'));
        self::assertSame('omit', $container->getParameter('nowo_sepa_payment.direct_debit.sequence_type_mode'));
        self::assertTrue($container->getParameter('nowo_sepa_payment.direct_debit.validate_xsd'));
        $definition = $container->getDefinition(DirectDebitGenerator::class);
        self::assertSame('%nowo_sepa_payment.direct_debit.pain_version%', $definition->getArgument('$defaultPainVersion'));
        self::assertSame('%nowo_sepa_payment.direct_debit.sequence_type_mode%', $definition->getArgument('$defaultSequenceTypeMode'));
        self::assertSame('%nowo_sepa_payment.direct_debit.validate_xsd%', $definition->getArgument('$validateXsd'));
    }

    public function testUnknownVersionIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        (new Processor())->processConfiguration(new Configuration(), [['direct_debit' => ['pain_version' => 'pain.008.001.05']]]);
    }

    public function testUnknownModeIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        (new Processor())->processConfiguration(new Configuration(), [['direct_debit' => ['sequence_type_mode' => 'sometimes']]]);
    }
}
