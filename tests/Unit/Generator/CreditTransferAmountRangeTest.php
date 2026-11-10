<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Generator;

use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Generator\CreditTransferGeneratorInterface;
use Nowo\SepaPaymentBundle\Tests\Unit\Helper\TranslationHelper;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

use function assert;

use const E_USER_DEPRECATED;

/**
 * P5 / KQ-P-L3 for credit transfers, the string port and the deprecated createResponse().
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class CreditTransferAmountRangeTest extends TestCase
{
    private CreditTransferGeneratorInterface $generator;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(TranslationHelper::createTranslatorCallback());
        $this->generator = new CreditTransferGenerator(new IbanValidator(), $translator);
    }

    /**
     * @return iterable<string, array{int, bool}>
     */
    public static function amountProvider(): iterable
    {
        yield 'zero' => [0, false];
        yield 'negative' => [-1, false];
        yield 'minimum' => [1, true];
        yield 'maximum' => [99_999_999_999, true];
        yield 'above maximum' => [100_000_000_000, false];
    }

    #[DataProvider('amountProvider')]
    public function testEpcRange(int $cents, bool $valid): void
    {
        if (!$valid) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('outside the SEPA range');
        }

        $xml = $this->generator->generateFromArray([
            'reference'              => 'MSG-R',
            'initiatingPartyName'    => 'My Company',
            'paymentInfoId'          => 'PMT-R',
            'requestedExecutionDate' => '2026-12-01',
            'debtorName'             => 'My Company',
            'debtorIban'             => 'ES9121000418450200051332',
            'transactions'           => [['amountCents' => $cents, 'creditorIban' => 'GB82WEST12345698765432', 'creditorName' => 'John Doe', 'endToEndId' => 'E2E-R']],
        ]);
        self::assertStringContainsString('<NbOfTxs>1</NbOfTxs>', $xml);
    }

    public function testCreateResponseIsDeprecatedButStillWorks(): void
    {
        $deprecations = [];
        set_error_handler(static function (int $errno, string $message) use (&$deprecations): bool {
            $deprecations[] = $message;

            return true;
        }, E_USER_DEPRECATED);
        try {
            assert($this->generator instanceof CreditTransferGenerator);
            $response = $this->generator->createResponse('<x/>', 'ct.xml');
        } finally {
            restore_error_handler();
        }

        self::assertSame('<x/>', $response->getContent());
        self::assertStringContainsString('SepaXmlResponseFactory', $deprecations[0] ?? '');
    }
}
