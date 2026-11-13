<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Event;

use DateTime;
use DateTimeImmutable;
use Nowo\SepaPaymentBundle\Event\AfterCreditTransferGenerationEvent;
use Nowo\SepaPaymentBundle\Event\AfterDirectDebitGenerationEvent;
use Nowo\SepaPaymentBundle\Event\BeforeCreditTransferGenerationEvent;
use Nowo\SepaPaymentBundle\Event\BeforeDirectDebitGenerationEvent;
use Nowo\SepaPaymentBundle\Event\Generation\CreditTransferGeneratedEvent;
use Nowo\SepaPaymentBundle\Event\Generation\CreditTransferGeneratingEvent;
use Nowo\SepaPaymentBundle\Event\Generation\DirectDebitGeneratedEvent;
use Nowo\SepaPaymentBundle\Event\Generation\DirectDebitGeneratingEvent;
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\CreditTransferData;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\Transaction;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitData;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitGenerationOptions;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitTransaction;
use Nowo\SepaPaymentBundle\Model\DirectDebit\Pain008Version;
use Nowo\SepaPaymentBundle\Model\DirectDebit\PaymentInformationData;
use Nowo\SepaPaymentBundle\Tests\Unit\Helper\TranslationHelper;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\Translation\TranslatorInterface;

use function preg_replace;

/**
 * 2.4.0 (REQ-ARCH-001 rule 3): framework-free twins of the generation events, dispatched right after the legacy ones.
 * The legacy events and their effect are unchanged (equivalence with the 2.3.x behaviour).
 */
final class GenerationEventTwinsTest extends TestCase
{
    public function testTwinsHaveNoFrameworkBaseClass(): void
    {
        foreach ([DirectDebitGeneratingEvent::class, DirectDebitGeneratedEvent::class, CreditTransferGeneratingEvent::class, CreditTransferGeneratedEvent::class] as $class) {
            $reflection = new ReflectionClass($class);
            self::assertFalse($reflection->getParentClass(), $class);
            self::assertFalse($reflection->isSubclassOf(Event::class), $class);
            self::assertSame([], $reflection->getInterfaceNames(), $class);
        }
    }

    public function testDirectDebitOutputIsUnchangedWithoutTwinListeners(): void
    {
        $options = new DirectDebitGenerationOptions(Pain008Version::V08, creationDateTime: new DateTimeImmutable('2026-11-13T10:00:00+01:00'));

        $plain      = (new DirectDebitGenerator(new IbanValidator()))->generate($this->directDebitData(), $options);
        $dispatched = (new DirectDebitGenerator(new IbanValidator(), null, false, new EventDispatcher()))->generate($this->directDebitData(), $options);

        self::assertSame($plain, $dispatched);
    }

    public function testDirectDebitLegacyThenTwinOrderAndValues(): void
    {
        $dispatcher = new EventDispatcher();
        $seen       = [];
        $dispatcher->addListener(BeforeDirectDebitGenerationEvent::class, static function (BeforeDirectDebitGenerationEvent $event) use (&$seen): void {
            $seen[] = ['legacy-before', $event->getMessageId()];
        });
        $dispatcher->addListener(DirectDebitGeneratingEvent::class, static function (DirectDebitGeneratingEvent $event) use (&$seen): void {
            $seen[] = ['twin-before', $event->getMessageId()];
        });
        $dispatcher->addListener(AfterDirectDebitGenerationEvent::class, static function (AfterDirectDebitGenerationEvent $event) use (&$seen): void {
            $seen[] = ['legacy-after', $event->getMessageId()];
            $event->setXml($event->getXml() . '<!--legacy-->');
        });
        $dispatcher->addListener(DirectDebitGeneratedEvent::class, static function (DirectDebitGeneratedEvent $event) use (&$seen): void {
            $seen[] = ['twin-after', $event->getMessageId(), $event->getPaymentInformationIds(), $event->getPainVersion(), $event->getNumberOfTransactions(), $event->getControlSumCents()];
            self::assertStringEndsWith('<!--legacy-->', $event->getXml(), 'the twin sees what the legacy listeners left');
            $event->setXml($event->getXml() . '<!--twin-->');
        });

        $xml = (new DirectDebitGenerator(new IbanValidator(), null, false, $dispatcher))->generate($this->directDebitData(), new DirectDebitGenerationOptions(Pain008Version::V08));

        self::assertSame([
            ['legacy-before', 'MSG-TWIN'],
            ['twin-before', 'MSG-TWIN'],
            ['legacy-after', 'MSG-TWIN'],
            ['twin-after', 'MSG-TWIN', ['PMT-1'], 'pain.008.001.08', 2, 3000],
        ], $seen);
        self::assertStringEndsWith('<!--legacy--><!--twin-->', $xml);
    }

    public function testDirectDebitTwinCanReplaceTheData(): void
    {
        $dispatcher = new EventDispatcher();
        $other      = $this->directDebitData('MSG-REPLACED');
        $dispatcher->addListener(DirectDebitGeneratingEvent::class, static function (DirectDebitGeneratingEvent $event) use ($other): void {
            $event->setDirectDebitData($other);
        });

        $xml = (new DirectDebitGenerator(new IbanValidator(), null, false, $dispatcher))->generate($this->directDebitData());

        self::assertStringContainsString('MSG-REPLACED', $xml);
        self::assertStringNotContainsString('MSG-TWIN', $xml);
    }

    public function testCreditTransferOutputIsUnchangedWithoutTwinListeners(): void
    {
        $plain      = (new CreditTransferGenerator(new IbanValidator(), $this->translator()))->generate($this->creditTransferData());
        $dispatched = (new CreditTransferGenerator(new IbanValidator(), $this->translator(), null, false, new EventDispatcher()))->generate($this->creditTransferData());

        self::assertSame(self::withoutCreationTime($plain), self::withoutCreationTime($dispatched));
    }

    public function testCreditTransferLegacyThenTwinOrderAndReplacement(): void
    {
        $dispatcher = new EventDispatcher();
        $seen       = [];
        $other      = $this->creditTransferData('MSG-CT-REPLACED');
        $dispatcher->addListener(BeforeCreditTransferGenerationEvent::class, static function (BeforeCreditTransferGenerationEvent $event) use (&$seen): void {
            $seen[] = 'legacy-before:' . $event->getMessageId();
        });
        $dispatcher->addListener(CreditTransferGeneratingEvent::class, static function (CreditTransferGeneratingEvent $event) use (&$seen, $other): void {
            $seen[] = 'twin-before:' . $event->getMessageId();
            $event->setCreditTransferData($other);
        });
        $dispatcher->addListener(AfterCreditTransferGenerationEvent::class, static function (AfterCreditTransferGenerationEvent $event) use (&$seen): void {
            $seen[] = 'legacy-after:' . $event->getMessageId();
            $event->setXml($event->getXml() . '<!--legacy-->');
        });
        $dispatcher->addListener(CreditTransferGeneratedEvent::class, static function (CreditTransferGeneratedEvent $event) use (&$seen): void {
            $seen[] = 'twin-after:' . $event->getMessageId();
            $event->setXml($event->getXml() . '<!--twin-->');
        });

        $xml = (new CreditTransferGenerator(new IbanValidator(), $this->translator(), null, false, $dispatcher))->generate($this->creditTransferData());

        self::assertSame(['legacy-before:MSG-CT', 'twin-before:MSG-CT', 'legacy-after:MSG-CT-REPLACED', 'twin-after:MSG-CT-REPLACED'], $seen);
        self::assertStringContainsString('MSG-CT-REPLACED', $xml);
        self::assertStringEndsWith('<!--legacy--><!--twin-->', $xml);
    }

    private function directDebitData(string $messageId = 'MSG-TWIN'): DirectDebitData
    {
        $data = DirectDebitData::fromPaymentInformation(
            $messageId,
            'My Company',
            'My Company',
            'ES9121000418450200051332',
            'ES59ZZZX1234567',
            new PaymentInformationData('PMT-1', new DateTimeImmutable('2026-12-01'), 'RCUR', 'CORE', [
                DirectDebitTransaction::fromCents(1000, 'GB82WEST12345698765432', 'John Doe', 'M-1', new DateTimeImmutable('2025-01-01'), 'E2E-1'),
                DirectDebitTransaction::fromCents(2000, 'GB82WEST12345698765432', 'Jane Doe', 'M-2', new DateTimeImmutable('2025-01-01'), 'E2E-2'),
            ]),
        );
        $data->setCreditorBic('CAIXESBBXXX');

        return $data;
    }

    private function creditTransferData(string $messageId = 'MSG-CT'): CreditTransferData
    {
        $data = new CreditTransferData(
            $messageId,
            new DateTime('2026-11-13 10:00:00'),
            'Test Company',
            'PMT-CT',
            'ES9121000418450200051332',
            'Test Company Name',
            new DateTime('tomorrow'),
        );
        $data->addTransaction(Transaction::fromCents('E2E-CT', 2500, 'EUR', 'GB82WEST12345698765432', 'Jane Doe'));

        return $data;
    }

    private function translator(): TranslatorInterface
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(TranslationHelper::createTranslatorCallback());

        return $translator;
    }

    private static function withoutCreationTime(string $xml): string
    {
        return (string) preg_replace('#<CreDtTm>[^<]*</CreDtTm>#', '<CreDtTm/>', $xml);
    }
}
