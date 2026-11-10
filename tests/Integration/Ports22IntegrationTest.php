<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Integration;

use DateTimeImmutable;
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Generator\CreditTransferGeneratorInterface;
use Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator;
use Nowo\SepaPaymentBundle\Generator\DirectDebitGeneratorInterface;
use Nowo\SepaPaymentBundle\Http\SepaXmlResponseFactory;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitData;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitGenerationOptions;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitTransaction;
use Nowo\SepaPaymentBundle\Model\DirectDebit\Pain008Version;
use Nowo\SepaPaymentBundle\Parser\CreditTransferParser;
use Nowo\SepaPaymentBundle\Parser\CreditTransferParserInterface;
use Nowo\SepaPaymentBundle\Parser\DirectDebitParser;
use Nowo\SepaPaymentBundle\Service\MandateService;
use Nowo\SepaPaymentBundle\Service\MandateServiceInterface;
use Nowo\SepaPaymentBundle\Tests\Kernel\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * 2.2 ports in the container (standard Symfony host): interfaces resolve to the bundle services, the response factory
 * is a service, and the kept-alive container serves .09 then .02 then the default without any option sticking.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class Ports22IntegrationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testInterfacesResolveToTheBundleServices(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        foreach ([
            DirectDebitGeneratorInterface::class    => DirectDebitGenerator::class,
            CreditTransferGeneratorInterface::class => CreditTransferGenerator::class,
            CreditTransferParserInterface::class    => CreditTransferParser::class,
            MandateServiceInterface::class          => MandateService::class,
        ] as $port => $implementation) {
            self::assertTrue($container->has($port), $port);
            self::assertInstanceOf($implementation, $container->get($port));
        }
        self::assertInstanceOf(SepaXmlResponseFactory::class, $container->get(SepaXmlResponseFactory::SERVICE_NAME));
    }

    /**
     * Worker mode without kernel reset: the same container service instance, three calls in a row.
     */
    public function testKeptAliveGeneratorDoesNotKeepPerCallOptions(): void
    {
        self::bootKernel(['debug' => false]);
        $generator = self::getContainer()->get(DirectDebitGenerator::SERVICE_NAME);
        self::assertInstanceOf(DirectDebitGenerator::class, $generator);

        $v09 = $generator->generate(self::data(), new DirectDebitGenerationOptions(Pain008Version::V09));
        $v02 = $generator->generate(self::data(), new DirectDebitGenerationOptions(Pain008Version::V02, 'omit'));
        $def = $generator->generate(self::data());

        self::assertStringContainsString(Pain008Version::V09->getNamespace(), $v09);
        self::assertStringContainsString(Pain008Version::V02->getNamespace(), $v02);
        self::assertStringNotContainsString('<SeqTp>', $v02);
        self::assertStringContainsString(Pain008Version::V09->getNamespace(), $def);
        self::assertStringContainsString('<SeqTp>RCUR</SeqTp>', $def);
        self::assertSame($generator, self::getContainer()->get(DirectDebitGenerator::SERVICE_NAME));

        // The bundle parser reads every written version back
        $parser = self::getContainer()->get(DirectDebitParser::SERVICE_NAME);
        self::assertInstanceOf(DirectDebitParser::class, $parser);
        foreach ([$v09, $v02, $generator->generate(self::data(), new DirectDebitGenerationOptions(Pain008Version::V08))] as $xml) {
            self::assertTrue($parser->isValidDirectDebit($xml));
        }
    }

    private static function data(): DirectDebitData
    {
        $data = new DirectDebitData('MSG-W', 'My Company', 'PMT-W', new DateTimeImmutable('2026-12-01'), 'My Company', 'ES9121000418450200051332', 'RCUR', 'ES59ZZZX1234567', 'CORE');
        $data->setCreditorBic('CAIXESBBXXX');
        $data->addTransaction(DirectDebitTransaction::fromCents(1000, 'GB82WEST12345698765432', 'John Doe', 'M-1', new DateTimeImmutable('2025-01-01'), 'E2E-W'));

        return $data;
    }
}
