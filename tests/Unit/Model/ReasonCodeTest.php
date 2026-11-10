<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Model;

use Nowo\SepaPaymentBundle\Model\ReasonCode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

use function basename;
use function count;
use function dirname;
use function glob;

/**
 * P7: ISO 20022 reason codes and their translations.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class ReasonCodeTest extends TestCase
{
    public function testFromCodeNormalisesAndRejectsUnknownCodes(): void
    {
        self::assertSame(ReasonCode::AM04, ReasonCode::fromCode(' am04 '));
        self::assertSame(ReasonCode::MD06, ReasonCode::fromCode('MD06'));
        self::assertNull(ReasonCode::fromCode('ZZ99'));
    }

    public function testEveryCodeHasANameADescriptionAndAKey(): void
    {
        foreach (ReasonCode::cases() as $code) {
            self::assertMatchesRegularExpression('/^[A-Z]{2}[A-Z0-9]{2}$/', $code->value);
            self::assertNotSame('', $code->getIsoName());
            self::assertNotSame('', $code->getDescription());
            self::assertSame('reason_code.' . $code->value, $code->getTranslationKey());
        }
        self::assertSame('InsufficientFunds', ReasonCode::AM04->getIsoName());
        self::assertSame('Insufficient funds', ReasonCode::AM04->getDescription());
    }

    /**
     * Every locale of the bundle translates every reason code (en, es, de, fr, it, nl, pt and the others shipped).
     */
    public function testEveryLocaleTranslatesEveryCode(): void
    {
        $files = glob(dirname(__DIR__, 3) . '/src/Resources/translations/' . ReasonCode::TRANSLATION_DOMAIN . '.*.yaml') ?: [];
        self::assertGreaterThanOrEqual(7, count($files));
        foreach ($files as $file) {
            $catalogue = Yaml::parseFile($file);
            foreach (ReasonCode::cases() as $code) {
                self::assertNotEmpty($catalogue['reason_code'][$code->value] ?? null, basename($file) . ' ' . $code->value);
            }
            self::assertNotEmpty($catalogue['validation.transaction_amount_out_of_range'] ?? null, basename($file));
        }
    }
}
