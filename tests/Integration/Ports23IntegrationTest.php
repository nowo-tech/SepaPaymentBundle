<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Integration;

use Nowo\SepaPaymentBundle\DependencyInjection\Configuration;
use Nowo\SepaPaymentBundle\DependencyInjection\NowoSepaPaymentExtension;
use Nowo\SepaPaymentBundle\Formatter\IbanMasker;
use Nowo\SepaPaymentBundle\Formatter\IbanMaskerInterface;
use Nowo\SepaPaymentBundle\Parser\DebitCreditNotificationParser;
use Nowo\SepaPaymentBundle\Parser\DebitCreditNotificationParserInterface;
use Nowo\SepaPaymentBundle\Parser\PaymentStatusReportParser;
use Nowo\SepaPaymentBundle\Parser\PaymentStatusReportParserInterface;
use Nowo\SepaPaymentBundle\Tests\Kernel\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function dirname;
use function file_get_contents;

/**
 * 2.3 services in the container (standard Symfony host) and the reports.max_size option.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class Ports23IntegrationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testServicesAndPorts(): void
    {
        self::bootKernel(['debug' => false]);
        $container = self::getContainer();

        foreach ([
            PaymentStatusReportParserInterface::class     => PaymentStatusReportParser::class,
            DebitCreditNotificationParserInterface::class => DebitCreditNotificationParser::class,
            IbanMaskerInterface::class                    => IbanMasker::class,
        ] as $port => $implementation) {
            self::assertInstanceOf($implementation, $container->get($port));
        }

        $parser = $container->get(DebitCreditNotificationParser::SERVICE_NAME);
        self::assertInstanceOf(DebitCreditNotificationParser::class, $parser);
        self::assertSame('camt.054.001.08', $parser->parse((string) file_get_contents(dirname(__DIR__) . '/Fixtures/Reports/camt.054.001.08-batch.xml'))->getVersion());
        self::assertSame('ES91 **** **** **** **** 1332', $container->get(IbanMasker::SERVICE_NAME)->maskFormatted('ES9121000418450200051332'));
    }

    public function testMaxSizeOption(): void
    {
        self::assertSame(20_971_520, (new Processor())->processConfiguration(new Configuration(), [])['reports']['max_size']);

        $container = new ContainerBuilder();
        (new NowoSepaPaymentExtension())->load([['reports' => ['max_size' => 1024]]], $container);
        self::assertSame(1024, $container->getParameter('nowo_sepa_payment.reports.max_size'));
        self::assertSame('%nowo_sepa_payment.reports.max_size%', $container->getDefinition(PaymentStatusReportParser::class)->getArgument('$maxSize'));
        self::assertSame('%nowo_sepa_payment.reports.max_size%', $container->getDefinition(DebitCreditNotificationParser::class)->getArgument('$maxSize'));

        $this->expectException(InvalidConfigurationException::class);
        (new Processor())->processConfiguration(new Configuration(), [['reports' => ['max_size' => 0]]]);
    }
}
