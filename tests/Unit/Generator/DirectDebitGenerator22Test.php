<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Generator;

use DateTimeImmutable;
use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Event\AfterDirectDebitGenerationEvent;
use Nowo\SepaPaymentBundle\Event\BeforeDirectDebitGenerationEvent;
use Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitData;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitGenerationOptions;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitTransaction;
use Nowo\SepaPaymentBundle\Model\DirectDebit\Pain008Version;
use Nowo\SepaPaymentBundle\Model\DirectDebit\PaymentInformationData;
use Nowo\SepaPaymentBundle\Model\DirectDebit\SequenceTypeMode;
use Nowo\SepaPaymentBundle\Model\Mandate\MandateAmendment;
use Nowo\SepaPaymentBundle\Model\PartyIdentification;
use Nowo\SepaPaymentBundle\Tests\Unit\Helper\TranslationHelper;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Nowo\SepaPaymentBundle\Validator\IbanValidatorInterface;
use Nowo\SepaPaymentBundle\Validator\XsdValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Translation\TranslatorInterface;

use function count;
use function memory_get_usage;

use const E_USER_DEPRECATED;

/**
 * 2.2.0 features of the direct debit generator: pain.008 versions (P1), several PmtInf blocks (P2), sequence type
 * modes (P3), InitgPty/Id and the NOTPROVIDED agent (P4), the EPC amount range (P5), the IbanValidatorInterface port,
 * events with ids and worker-mode safety (no per-call option sticks to the service).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class DirectDebitGenerator22Test extends TestCase
{
    private XsdValidator $xsdValidator;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(TranslationHelper::createTranslatorCallback());
        $this->xsdValidator = new XsdValidator($translator);
    }

    /**
     * @return iterable<string, array{Pain008Version}>
     */
    public static function versionProvider(): iterable
    {
        foreach (Pain008Version::cases() as $version) {
            yield $version->value => [$version];
        }
    }

    /**
     * Every version validates against its own shipped XSD, chosen per call; the generator validates it too.
     */
    #[DataProvider('versionProvider')]
    public function testEveryVersionValidatesAgainstItsOfficialXsd(Pain008Version $version): void
    {
        $generator = new DirectDebitGenerator(new IbanValidator(), $this->xsdValidator);
        $data      = $this->multiBlockData();
        $data->setInitiatingPartyIdentification(PartyIdentification::organisation('ES59000B12345678', 'SEPA'));
        $data->setCreditorAddress('Calle Mayor 1', 'Madrid', '28001', 'ES');

        $xml = $generator->generate($data, new DirectDebitGenerationOptions(painVersion: $version, validateXsd: true));

        $this->assertStringContainsString('xmlns="' . $version->getNamespace() . '"', $xml);
        $this->assertTrue($this->xsdValidator->validateDirectDebit($xml, __DIR__ . '/../../../src/Resources/schemas/' . $version->value . '.xsd'));
        // 3 creditor agents (one per block) + 1 debtor BIC: BIC in .02, BICFI in .08 and .09
        $this->assertSame($version === Pain008Version::V02 ? 4 : 0, self::countNodes($xml, '//ns:CdtrAgt/ns:FinInstnId/ns:BIC | //ns:DbtrAgt/ns:FinInstnId/ns:BIC'));
    }

    /**
     * Each omit / RCUR / per-transaction file of each version validates too.
     */
    #[DataProvider('versionProvider')]
    public function testSequenceTypeModesValidateInEveryVersion(Pain008Version $version): void
    {
        $generator = new DirectDebitGenerator(new IbanValidator(), $this->xsdValidator, true);
        foreach (SequenceTypeMode::cases() as $mode) {
            $xml = $generator->generate($this->multiBlockData(), new DirectDebitGenerationOptions($version, $mode));
            $this->assertTrue($this->xsdValidator->validateDirectDebit($xml), $mode->value);
        }
    }

    /**
     * The config default version (constructor) is used when the call gives none, and a call overrides it.
     */
    public function testConfiguredDefaultVersionAndPerCallOverride(): void
    {
        $generator = new DirectDebitGenerator(new IbanValidator(), null, false, null, null, null, 'EUR', null, 'pain.008.001.08');

        $this->assertStringContainsString(Pain008Version::V08->getNamespace(), $generator->generate($this->singleBlockData()));
        $this->assertStringContainsString(Pain008Version::V02->getNamespace(), $generator->generate($this->singleBlockData(), new DirectDebitGenerationOptions('pain.008.001.02')));
        $this->assertStringContainsString(Pain008Version::V08->getNamespace(), $generator->generate($this->singleBlockData()));
    }

    /**
     * Worker mode (kernel kept alive, no reset): the same instance serves .09, then .02, then the default again, and
     * modes do not stick either.
     */
    public function testPerCallOptionsNeverStickToTheService(): void
    {
        $generator = new DirectDebitGenerator(new IbanValidator(), $this->xsdValidator, true);

        $v09 = $generator->generate($this->singleBlockData(), new DirectDebitGenerationOptions(Pain008Version::V09, SequenceTypeMode::OMIT));
        $v02 = $generator->generate($this->singleBlockData(), new DirectDebitGenerationOptions(Pain008Version::V02));
        $def = $generator->generate($this->singleBlockData());

        $this->assertStringContainsString(Pain008Version::V09->getNamespace(), $v09);
        $this->assertSame(0, self::countNodes($v09, '//ns:SeqTp'));
        $this->assertStringContainsString(Pain008Version::V02->getNamespace(), $v02);
        $this->assertSame(1, self::countNodes($v02, '//ns:SeqTp'));
        $this->assertStringContainsString(Pain008Version::V09->getNamespace(), $def);
        $this->assertSame(1, self::countNodes($def, '//ns:SeqTp'));
        $this->assertSame(0, self::countNodes($def, '//ns:InitgPty/ns:Id'));
    }

    /**
     * Worker mode: 2,000 calls alternating versions, modes and creditors on one instance keep memory flat and never
     * mix data of two "tenants".
     */
    public function testRepeatedCallsKeepMemoryFlatAndDoNotLeakData(): void
    {
        $generator = new DirectDebitGenerator(new IbanValidator());
        $versions  = Pain008Version::cases();
        $modes     = SequenceTypeMode::cases();
        $baseline  = 0;
        for ($i = 0; $i < 2000; ++$i) {
            $tenant = $i % 2 === 0 ? 'Tenant A' : 'Tenant B';
            $data   = $this->singleBlockData($tenant);
            if ($i % 2 === 0) {
                $data->setInitiatingPartyIdentification(PartyIdentification::organisation('ID-A'));
            }
            $xml = $generator->generate($data, new DirectDebitGenerationOptions($versions[$i % 3], $modes[$i % 3]));
            $this->assertStringNotContainsString($i % 2 === 0 ? 'Tenant B' : 'Tenant A', $xml);
            $this->assertSame($i % 2 === 0 ? 1 : 0, self::countNodes($xml, '//ns:InitgPty/ns:Id'));
            if ($i === 100) {
                $baseline = memory_get_usage();
            }
        }
        $this->assertLessThan(512 * 1024, memory_get_usage() - $baseline);
    }

    /**
     * P2: several blocks, in order, with their own due dates, instruments and control sums.
     */
    public function testSeveralPaymentInformationBlocks(): void
    {
        $xml = (new DirectDebitGenerator(new IbanValidator()))->generate($this->multiBlockData());

        $this->assertSame(['PMT-1', 'PMT-2', 'PMT-3'], self::values($xml, '//ns:PmtInf/ns:PmtInfId'));
        $this->assertSame(['FRST', 'RCUR', 'RCUR'], self::values($xml, '//ns:PmtInf/ns:PmtTpInf/ns:SeqTp'));
        $this->assertSame(['CORE', 'CORE', 'CORE'], self::values($xml, '//ns:PmtInf/ns:PmtTpInf/ns:LclInstrm/ns:Cd'));
        $this->assertSame(['2026-12-01', '2026-12-01', '2026-12-05'], self::values($xml, '//ns:PmtInf/ns:ReqdColltnDt'));
        $this->assertSame(['10.00', '30.50', '1.00'], self::values($xml, '//ns:PmtInf/ns:CtrlSum'));
        $this->assertSame(['4'], self::values($xml, '//ns:GrpHdr/ns:NbOfTxs'));
        $this->assertSame(['41.50'], self::values($xml, '//ns:GrpHdr/ns:CtrlSum'));
    }

    /**
     * P2: the constructor block comes first when it has transactions; getTransactions() lists every block.
     */
    public function testConstructorBlockIsFirstWhenItHasTransactions(): void
    {
        $data = $this->singleBlockData();
        $data->addPaymentInformation(new PaymentInformationData('PMT-EXTRA', new DateTimeImmutable('2026-12-02'), 'RCUR', 'CORE', [self::tx('E2E-X', 500)]));

        $xml = (new DirectDebitGenerator(new IbanValidator()))->generate($data);

        $this->assertSame(['PMT-SINGLE', 'PMT-EXTRA'], self::values($xml, '//ns:PmtInf/ns:PmtInfId'));
        $this->assertCount(2, $data->getTransactions());
        $this->assertSame(1500, $data->getTotalAmountCents());
    }

    /**
     * P3 per transaction: a block whose transactions carry FRST and RCUR is split, ids suffixed.
     */
    public function testPerTransactionModeSplitsMixedBlocks(): void
    {
        $block = new PaymentInformationData('PMT-MIX', new DateTimeImmutable('2026-12-01'), null, 'CORE', [
            self::tx('E2E-1', 100)->setSequenceType('FRST'),
            self::tx('E2E-2', 200)->setSequenceType('RCUR'),
            self::tx('E2E-3', 300)->setSequenceType('FRST'),
        ]);
        $data = DirectDebitData::fromPaymentInformation('MSG-MIX', 'Creditor', 'Creditor', 'ES9121000418450200051332', 'ES59ZZZX1234567', $block);
        $data->setCreditorBic('CAIXESBBXXX');
        $generator = new DirectDebitGenerator(new IbanValidator());

        $xml = $generator->generate($data);
        $this->assertSame(['PMT-MIX-FRST', 'PMT-MIX-RCUR'], self::values($xml, '//ns:PmtInf/ns:PmtInfId'));
        $this->assertSame(['E2E-1', 'E2E-3', 'E2E-2'], self::values($xml, '//ns:EndToEndId'));

        $rcur = $generator->generate($data, new DirectDebitGenerationOptions(sequenceTypeMode: SequenceTypeMode::ALWAYS_RCUR));
        $this->assertSame(['PMT-MIX'], self::values($rcur, '//ns:PmtInf/ns:PmtInfId'));
        $this->assertSame(['RCUR'], self::values($rcur, '//ns:SeqTp'));

        $omit = $generator->generate($data, new DirectDebitGenerationOptions(sequenceTypeMode: 'omit'));
        $this->assertSame(['PMT-MIX'], self::values($omit, '//ns:PmtInf/ns:PmtInfId'));
        $this->assertSame([], self::values($omit, '//ns:SeqTp'));
    }

    /**
     * P3: a transaction's own sequence type overrides the block's; a single resulting type keeps the block id.
     */
    public function testTransactionSequenceTypeOverridesBlock(): void
    {
        $data = $this->singleBlockData();
        $data->getTransactions()[0]->setSequenceType('FNAL');

        $xml = (new DirectDebitGenerator(new IbanValidator()))->generate($data);

        $this->assertSame(['PMT-SINGLE'], self::values($xml, '//ns:PmtInfId'));
        $this->assertSame(['FNAL'], self::values($xml, '//ns:SeqTp'));
    }

    public function testMissingSequenceTypeIsRejectedInPerTransactionMode(): void
    {
        $block = new PaymentInformationData('PMT-N', new DateTimeImmutable('2026-12-01'), null, 'CORE', [self::tx('E2E-N', 100)]);
        $data  = DirectDebitData::fromPaymentInformation('MSG-N', 'Creditor', 'Creditor', 'ES9121000418450200051332', 'ES59ZZZX1234567', $block);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing sequence type for transaction "E2E-N"');
        (new DirectDebitGenerator(new IbanValidator()))->generate($data);
    }

    public function testEmptyBlockIsRejected(): void
    {
        $block = new PaymentInformationData('PMT-E', new DateTimeImmutable('2026-12-01'));
        $data  = DirectDebitData::fromPaymentInformation('MSG-E', 'Creditor', 'Creditor', 'ES9121000418450200051332', 'ES59ZZZX1234567', $block);

        $this->expectExceptionMessage('Payment information "PMT-E" has no transactions');
        (new DirectDebitGenerator(new IbanValidator()))->generate($data);
    }

    public function testDuplicatePaymentInformationIdIsRejected(): void
    {
        $data = $this->singleBlockData();
        $data->addPaymentInformation(new PaymentInformationData('PMT-SINGLE', new DateTimeImmutable('2026-12-02'), 'RCUR', 'CORE', [self::tx('E2E-D', 100)]));

        $this->expectExceptionMessage('Duplicate payment information id (PmtInfId) "PMT-SINGLE"');
        (new DirectDebitGenerator(new IbanValidator()))->generate($data);
    }

    public function testSplitIdLongerThan35CharactersIsRejected(): void
    {
        $block = new PaymentInformationData('PMT-' . str_repeat('X', 29), new DateTimeImmutable('2026-12-01'), null, 'CORE', [
            self::tx('E2E-1', 100)->setSequenceType('FRST'),
            self::tx('E2E-2', 100)->setSequenceType('RCUR'),
        ]);
        $data = DirectDebitData::fromPaymentInformation('MSG-L', 'Creditor', 'Creditor', 'ES9121000418450200051332', 'ES59ZZZX1234567', $block);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('PmtInfId');
        (new DirectDebitGenerator(new IbanValidator()))->generate($data);
    }

    /**
     * P4: InitgPty/Id as OrgId or PrvtId, with a code, a proprietary scheme or an issuer.
     */
    public function testInitiatingPartyIdentification(): void
    {
        $generator = new DirectDebitGenerator(new IbanValidator(), $this->xsdValidator, true);

        $data = $this->singleBlockData();
        $data->setInitiatingPartyIdentification(PartyIdentification::organisation('ES59000B12345678'));
        $xml = $generator->generate($data);
        $this->assertSame(['ES59000B12345678'], self::values($xml, '//ns:InitgPty/ns:Id/ns:OrgId/ns:Othr/ns:Id'));
        $this->assertSame(0, self::countNodes($xml, '//ns:InitgPty//ns:SchmeNm'));

        $data->setInitiatingPartyIdentification(PartyIdentification::privatePerson('12345678Z', null, 'NIDN', 'ES & co'));
        $xml = $generator->generate($data, new DirectDebitGenerationOptions(Pain008Version::V02));
        $this->assertSame(['NIDN'], self::values($xml, '//ns:InitgPty/ns:Id/ns:PrvtId/ns:Othr/ns:SchmeNm/ns:Cd'));
        $this->assertSame(['ES co'], self::values($xml, '//ns:InitgPty/ns:Id/ns:PrvtId/ns:Othr/ns:Issr'));

        // Creditor scheme id keeps SchmeNm/Prtry SEPA
        $this->assertSame(['SEPA'], self::values($xml, '//ns:CdtrSchmeId/ns:Id/ns:PrvtId/ns:Othr/ns:SchmeNm/ns:Prtry'));
    }

    /**
     * P4: without a creditor or debtor BIC (null or "") the agent is Othr/Id NOTPROVIDED, not an empty BIC.
     */
    #[DataProvider('versionProvider')]
    public function testMissingCreditorBicIsWrittenAsNotProvided(Pain008Version $version): void
    {
        $data = $this->singleBlockData();
        $data->setCreditorBic('');
        $data->getTransactions()[0]->setDebtorBic('');

        $xml = (new DirectDebitGenerator(new IbanValidator(), $this->xsdValidator, true))->generate($data, new DirectDebitGenerationOptions($version));

        $this->assertSame(['NOTPROVIDED'], self::values($xml, '//ns:CdtrAgt/ns:FinInstnId/ns:Othr/ns:Id'));
        $this->assertSame(['NOTPROVIDED'], self::values($xml, '//ns:DbtrAgt/ns:FinInstnId/ns:Othr/ns:Id'));
    }

    /**
     * P5: the EPC range 1 to 99 999 999 999 cents.
     *
     * @return iterable<string, array{int, bool}>
     */
    public static function amountProvider(): iterable
    {
        yield 'zero' => [0, false];
        yield 'negative' => [-100, false];
        yield 'minimum' => [1, true];
        yield 'maximum' => [99_999_999_999, true];
        yield 'above maximum' => [100_000_000_000, false];
    }

    #[DataProvider('amountProvider')]
    public function testEpcAmountRange(int $cents, bool $valid): void
    {
        $data = new DirectDebitData('MSG-R', 'Creditor', 'PMT-R', new DateTimeImmutable('2026-12-01'), 'Creditor', 'ES9121000418450200051332', 'RCUR', 'ES59ZZZX1234567', 'CORE');
        $data->setCreditorBic('CAIXESBBXXX');
        $data->addTransaction(self::tx('E2E-R', $cents));
        $generator = new DirectDebitGenerator(new IbanValidator());

        if (!$valid) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('outside the SEPA range');
        }
        $xml = $generator->generate($data);
        $this->assertStringContainsString('<NbOfTxs>1</NbOfTxs>', $xml);
    }

    /**
     * Bar gap 5: a host decorator of IbanValidatorInterface is used by the generator.
     */
    public function testHostIbanValidatorDecoratorIsUsed(): void
    {
        $decorator = new class(new IbanValidator()) implements IbanValidatorInterface {
            /** @var list<string> */
            public array $checked = [];

            public function __construct(private readonly IbanValidator $inner)
            {
            }

            public function isValid(string $iban): bool
            {
                $this->checked[] = $iban;

                return $iban !== 'DE89370400440532013000' && $this->inner->isValid($iban);
            }

            public function normalize(string $iban): string
            {
                return $this->inner->normalize($iban);
            }

            public function format(string $iban): string
            {
                return $this->inner->format($iban);
            }

            public function getCountryCode(string $iban): string
            {
                return $this->inner->getCountryCode($iban);
            }

            public function getCheckDigits(string $iban): string
            {
                return $this->inner->getCheckDigits($iban);
            }

            public function getBban(string $iban): string
            {
                return $this->inner->getBban($iban);
            }

            public function calculateCheckDigits(string $iban): string
            {
                return $this->inner->calculateCheckDigits($iban);
            }
        };
        $generator = new DirectDebitGenerator($decorator);
        $generator->generate($this->singleBlockData());
        $this->assertContains('GB82WEST12345698765432', $decorator->checked);

        $data = $this->singleBlockData();
        $data->addTransaction(new DirectDebitTransaction('1.00', 'DE89370400440532013000', 'Blocked', 'M-B', new DateTimeImmutable('2025-01-01'), 'E2E-B'));
        $this->expectExceptionMessage('Invalid debtor IBAN: DE89370400440532013000');
        $generator->generate($data);
    }

    /**
     * Events carry ids: the before event exposes the message id, the after event the block ids, version, count and sum.
     */
    public function testEventsCarryIds(): void
    {
        $dispatcher = new EventDispatcher();
        $seen       = [];
        $dispatcher->addListener(BeforeDirectDebitGenerationEvent::class, static function (BeforeDirectDebitGenerationEvent $event) use (&$seen): void {
            $seen['before'] = $event->getMessageId();
        });
        $dispatcher->addListener(AfterDirectDebitGenerationEvent::class, static function (AfterDirectDebitGenerationEvent $event) use (&$seen): void {
            $seen['after'] = [$event->getMessageId(), $event->getPaymentInformationIds(), $event->getPainVersion(), $event->getNumberOfTransactions(), $event->getControlSumCents()];
        });

        (new DirectDebitGenerator(new IbanValidator(), null, false, $dispatcher))->generate($this->multiBlockData(), new DirectDebitGenerationOptions(Pain008Version::V08));

        $this->assertSame('MSG-MULTI', $seen['before']);
        $this->assertSame(['MSG-MULTI', ['PMT-1', 'PMT-2', 'PMT-3'], 'pain.008.001.08', 4, 4150], $seen['after']);
    }

    /**
     * The creation date can be fixed per call (reproducible files).
     */
    public function testCreationDateTimeOption(): void
    {
        $xml = (new DirectDebitGenerator(new IbanValidator()))->generate($this->singleBlockData(), new DirectDebitGenerationOptions(creationDateTime: new DateTimeImmutable('2026-11-10T10:00:00+01:00')));

        $this->assertSame(['2026-11-10T10:00:00+01:00'], self::values($xml, '//ns:CreDtTm'));
    }

    public function testCreateResponseIsDeprecatedButStillWorks(): void
    {
        $deprecations = [];
        set_error_handler(static function (int $errno, string $message) use (&$deprecations): bool {
            $deprecations[] = $message;

            return true;
        }, E_USER_DEPRECATED);
        try {
            $response = (new DirectDebitGenerator(new IbanValidator()))->createResponse('<x/>', 'dd.xml');
        } finally {
            restore_error_handler();
        }

        $this->assertSame('<x/>', $response->getContent());
        $this->assertStringContainsString('SepaXmlResponseFactory', $deprecations[0] ?? '');
    }

    public function testInvalidConfiguredDefaultsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported pain.008 version "pain.008.001.05"');
        new DirectDebitGenerator(new IbanValidator(), null, false, null, null, null, 'EUR', null, 'pain.008.001.05');
    }

    /**
     * LB-L4: an empty block added next to a full one is rejected too.
     */
    public function testEmptyAddedBlockIsRejected(): void
    {
        $data = $this->singleBlockData();
        $data->addPaymentInformation(new PaymentInformationData('PMT-EMPTY', new DateTimeImmutable('2026-12-02'), 'RCUR'));

        $this->expectExceptionMessage('Payment information "PMT-EMPTY" has no transactions');
        (new DirectDebitGenerator(new IbanValidator()))->generate($data);
    }

    /**
     * LB-L1: always_rcur keeps one-off (OOFF) collections; FRST and FNAL become RCUR. A block with both is split.
     */
    public function testAlwaysRcurKeepsOneOff(): void
    {
        $generator = new DirectDebitGenerator(new IbanValidator(), $this->xsdValidator, true);
        $options   = new DirectDebitGenerationOptions(sequenceTypeMode: SequenceTypeMode::ALWAYS_RCUR);

        $data = $this->singleBlockData();
        $data->getTransactions()[0]->setSequenceType('OOFF');
        $this->assertSame(['OOFF'], self::values($generator->generate($data, $options), '//ns:SeqTp'));

        $block = new PaymentInformationData('PMT-AR', new DateTimeImmutable('2026-12-01'), 'FRST', 'CORE', [
            self::tx('E2E-1', 100),
            self::tx('E2E-2', 100)->setSequenceType('OOFF'),
            self::tx('E2E-3', 100)->setSequenceType('FNAL'),
        ]);
        $data = DirectDebitData::fromPaymentInformation('MSG-AR', 'Creditor', 'Creditor', 'ES9121000418450200051332', 'ES59ZZZX1234567', $block);
        $xml  = $generator->generate($data, $options);
        $this->assertSame(['PMT-AR-RCUR', 'PMT-AR-OOFF'], self::values($xml, '//ns:PmtInfId'));
        $this->assertSame(['RCUR', 'OOFF'], self::values($xml, '//ns:SeqTp'));
        $this->assertSame(['E2E-1', 'E2E-3', 'E2E-2'], self::values($xml, '//ns:EndToEndId'));
    }

    /**
     * LB-L5: CORE and B2B in one file are rejected.
     */
    public function testMixedLocalInstrumentsAreRejected(): void
    {
        $data = $this->singleBlockData();
        $data->addPaymentInformation(new PaymentInformationData('PMT-B2B', new DateTimeImmutable('2026-12-02'), 'RCUR', 'B2B', [self::tx('E2E-B2B', 100)]));

        $this->expectExceptionMessage('cannot mix local instruments (CORE, B2B)');
        (new DirectDebitGenerator(new IbanValidator()))->generate($data);
    }

    /**
     * LB-L5: validateXsd requested per call without an XSD validator throws instead of being ignored.
     */
    public function testValidateXsdWithoutValidatorThrows(): void
    {
        $this->expectExceptionMessage('the generator has no XSD validator');
        (new DirectDebitGenerator(new IbanValidator()))->generate($this->singleBlockData(), new DirectDebitGenerationOptions(validateXsd: true));
    }

    /**
     * LB-I6: an EndToEndId used twice in a file (across blocks) is rejected.
     */
    public function testDuplicateEndToEndIdIsRejected(): void
    {
        $data = $this->singleBlockData();
        $data->addPaymentInformation(new PaymentInformationData('PMT-DUP', new DateTimeImmutable('2026-12-02'), 'RCUR', 'CORE', [self::tx('E2E-1', 100)]));

        $this->expectExceptionMessage('Duplicate EndToEndId "E2E-1"');
        (new DirectDebitGenerator(new IbanValidator()))->generate($data);
    }

    /**
     * LB-L3: invalid UTF-8 in an address is rejected with a clear error (it used to give a raw, unprocessed file).
     */
    public function testInvalidUtf8AddressIsRejected(): void
    {
        $data = $this->singleBlockData();
        $data->getTransactions()[0]->setDebtorAddress("Stra\xDFe 1", 'Berlin', '10115', 'DE');

        $this->expectExceptionMessage('Invalid debtor address of transaction "E2E-1": the text is not valid UTF-8.');
        (new DirectDebitGenerator(new IbanValidator()))->generate($data, new DirectDebitGenerationOptions(sequenceTypeMode: SequenceTypeMode::OMIT));
    }

    /**
     * LB-M1: computed float amounts give the same cents on every PHP version (CI runs this on 8.2 to 8.5).
     */
    public function testComputedFloatAmountsInTheXml(): void
    {
        $data = new DirectDebitData('MSG-F', 'Creditor', 'PMT-F', new DateTimeImmutable('2026-12-01'), 'Creditor', 'ES9121000418450200051332', 'RCUR', 'ES59ZZZX1234567', 'CORE');
        $data->setCreditorBic('CAIXESBBXXX');
        foreach ([3.5 * 1.21, 18.5 * 1.21, 1073 + 0.535] as $i => $amount) {
            $data->addTransaction(@new DirectDebitTransaction($amount, 'GB82WEST12345698765432', 'John Doe', 'M-' . $i, new DateTimeImmutable('2025-01-01'), 'E2E-F' . $i));
        }

        $this->assertSame(['4.24', '22.39', '1073.54'], self::values((new DirectDebitGenerator(new IbanValidator()))->generate($data), '//ns:InstdAmt'));
    }

    /**
     * P8: mandate amendments are written in MndtRltdInf and validate in every version.
     */
    #[DataProvider('versionProvider')]
    public function testMandateAmendmentsValidateInEveryVersion(Pain008Version $version): void
    {
        $generator = new DirectDebitGenerator(new IbanValidator(), $this->xsdValidator, true);
        $data      = $this->singleBlockData();
        $data->getTransactions()[0]->setAmendment(new MandateAmendment('UMR-OLD', 'ES59ZZZX7654321', 'Old Creditor & Co', null, true));
        $second = self::tx('E2E-2', 200)->setAmendment(new MandateAmendment(null, null, null, 'DE89370400440532013000'));
        $data->addTransaction($second);

        $xml = $generator->generate($data, new DirectDebitGenerationOptions($version));

        self::assertSame(['true', 'true'], self::values($xml, '//ns:MndtRltdInf/ns:AmdmntInd'));
        self::assertSame(['UMR-OLD'], self::values($xml, '//ns:AmdmntInfDtls/ns:OrgnlMndtId'));
        self::assertSame(['Old Creditor Co'], self::values($xml, '//ns:OrgnlCdtrSchmeId/ns:Nm'));
        self::assertSame(['ES59ZZZX7654321'], self::values($xml, '//ns:OrgnlCdtrSchmeId/ns:Id/ns:PrvtId/ns:Othr/ns:Id'));
        self::assertSame(['SMNDA'], self::values($xml, '//ns:OrgnlDbtrAgt/ns:FinInstnId/ns:Othr/ns:Id'));
        self::assertSame(['DE89370400440532013000'], self::values($xml, '//ns:OrgnlDbtrAcct/ns:Id/ns:IBAN'));
    }

    public function testNewDebtorAgentRequiresFirst(): void
    {
        $data = $this->singleBlockData();
        $data->getTransactions()[0]->setSequenceType('RCUR')->setAmendment(new MandateAmendment(null, null, null, null, true));

        $this->expectExceptionMessage('must be collected with sequence type FRST, not RCUR');
        (new DirectDebitGenerator(new IbanValidator()))->generate($data);
    }

    /**
     * LB-I2: control sums come from integer cents; 20 000 maximum-amount transactions (about 2·10^15 cents) stay exact.
     */
    public function testControlSumsAreExactIntegerCents(): void
    {
        $block = new PaymentInformationData('PMT-BIG', new DateTimeImmutable('2026-12-01'), 'RCUR', 'CORE');
        for ($i = 0; $i < 20000; ++$i) {
            $block->addTransaction(self::tx('E2E-' . $i, 99_999_999_999));
        }
        $data = DirectDebitData::fromPaymentInformation('MSG-BIG', 'Creditor', 'Creditor', 'ES9121000418450200051332', 'ES59ZZZX1234567', $block);

        $xml = (new DirectDebitGenerator(new IbanValidator()))->generate($data);

        self::assertSame(['19999999999800.00'], self::values($xml, '//ns:GrpHdr/ns:CtrlSum'));
        self::assertSame(['19999999999800.00'], self::values($xml, '//ns:PmtInf/ns:CtrlSum'));
    }

    /**
     * LB-I4: DOM-added address text is reduced to the EPC character set, and its EPC lengths are enforced.
     */
    public function testAddressesUseTheEpcCharacterSet(): void
    {
        $data = $this->singleBlockData();
        $data->setCreditorAddress('Straße & Söhne <1>', 'Málaga', '29001', 'ES');

        $xml = (new DirectDebitGenerator(new IbanValidator(), $this->xsdValidator, true))->generate($data);

        self::assertSame(['Strasse Sohne 1'], self::values($xml, '//ns:Cdtr/ns:PstlAdr/ns:StrtNm'));
        self::assertSame(['Malaga'], self::values($xml, '//ns:Cdtr/ns:PstlAdr/ns:TwnNm'));
    }

    /**
     * @return iterable<string, array{array{0: ?string, 1: ?string, 2: ?string, 3: ?string}, string}>
     */
    public static function badAddressProvider(): iterable
    {
        yield 'street' => [[str_repeat('S', 71), null, null, null], 'street'];
        yield 'city' => [[null, str_repeat('C', 36), null, null], 'city'];
        yield 'postal code' => [[null, null, str_repeat('1', 17), null], 'postalCode'];
        yield 'country' => [[null, null, null, 'es'], 'country "es"'];
    }

    /**
     * @param array{0: ?string, 1: ?string, 2: ?string, 3: ?string} $address
     */
    #[DataProvider('badAddressProvider')]
    public function testEpcAddressLimits(array $address, string $message): void
    {
        $data = $this->singleBlockData();
        $data->getTransactions()[0]->setDebtorAddress(...$address);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        (new DirectDebitGenerator(new IbanValidator()))->generate($data);
    }

    /**
     * LF-L3: text that the EPC character set reduces to nothing is refused, instead of an empty element or the
     * library's unsanitised AdrLine.
     */
    public function testTextEmptyAfterSanitizingIsRefused(): void
    {
        $data = $this->singleBlockData();
        $data->getTransactions()[0]->setAmendment(new MandateAmendment(null, 'ES59ZZZX7654321', '東京商事'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('original creditor name of the amendment of transaction "E2E-1"');
        (new DirectDebitGenerator(new IbanValidator()))->generate($data);
    }

    public function testDebtorStreetEmptyAfterSanitizingIsRefused(): void
    {
        $data = $this->singleBlockData();
        $data->getTransactions()[0]->setDebtorAddress('東京', null, null, null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('debtor address of transaction "E2E-1" street "東京": nothing is left after reducing it to the EPC character set');
        (new DirectDebitGenerator(new IbanValidator()))->generate($data);
    }

    /**
     * LF-L3: the address passed to the library is sanitised too, so no raw AdrLine is written.
     */
    public function testDebtorStreetIsSanitisedEverywhere(): void
    {
        $data = $this->singleBlockData();
        $data->getTransactions()[0]->setDebtorAddress('Straße 1 <b>', null, null, null);

        $xml = (new DirectDebitGenerator(new IbanValidator(), $this->xsdValidator, true))->generate($data);

        self::assertStringNotContainsString('Straße', $xml);
        self::assertSame(['Strasse 1 b'], self::values($xml, '//ns:Dbtr/ns:PstlAdr/ns:StrtNm'));
    }

    /**
     * LB-D-I1: validateXsd in the constructor without a validator is refused instead of silently ignored.
     */
    public function testConstructorValidateXsdWithoutValidatorIsRefused(): void
    {
        $this->expectExceptionMessage('no XSD validator was given');
        new DirectDebitGenerator(new IbanValidator(), null, true);
    }

    private function singleBlockData(string $creditor = 'My Company'): DirectDebitData
    {
        $data = new DirectDebitData('MSG-SINGLE', $creditor, 'PMT-SINGLE', new DateTimeImmutable('2026-12-01'), $creditor, 'ES9121000418450200051332', 'FRST', 'ES59ZZZX1234567', 'CORE');
        $data->setCreditorBic('CAIXESBBXXX');
        $data->addTransaction(self::tx('E2E-1', 1000));

        return $data;
    }

    private function multiBlockData(): DirectDebitData
    {
        $data = DirectDebitData::fromPaymentInformation(
            'MSG-MULTI',
            'My Company',
            'My Company',
            'ES9121000418450200051332',
            'ES59ZZZX1234567',
            new PaymentInformationData('PMT-1', new DateTimeImmutable('2026-12-01'), 'FRST', 'CORE', [self::tx('E2E-1', 1000)]),
            new PaymentInformationData('PMT-2', new DateTimeImmutable('2026-12-01'), 'RCUR', 'CORE', [self::tx('E2E-2', 2000, 'COBADEFFXXX'), self::tx('E2E-3', 1050)]),
            new PaymentInformationData('PMT-3', new DateTimeImmutable('2026-12-05'), 'RCUR', 'CORE', [self::tx('E2E-4', 100)]),
        );
        $data->setCreditorBic('CAIXESBBXXX');

        return $data;
    }

    private static function tx(string $endToEndId, int $cents, ?string $bic = null): DirectDebitTransaction
    {
        $transaction = DirectDebitTransaction::fromCents($cents, 'GB82WEST12345698765432', 'John Doe', 'M-' . $endToEndId, new DateTimeImmutable('2025-01-01'), $endToEndId);
        if ($bic !== null) {
            $transaction->setDebtorBic($bic);
        }

        return $transaction;
    }

    /**
     * @return list<string>
     */
    private static function values(string $xml, string $query): array
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('ns', (string) $dom->documentElement?->namespaceURI);
        $values = [];
        foreach ($xpath->query($query) ?: [] as $node) {
            $values[] = $node->textContent;
        }

        return $values;
    }

    private static function countNodes(string $xml, string $query): int
    {
        return count(self::values($xml, $query));
    }
}
