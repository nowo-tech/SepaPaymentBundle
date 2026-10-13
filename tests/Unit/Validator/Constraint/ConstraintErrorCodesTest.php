<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Validator\Constraint;

use Nowo\SepaPaymentBundle\Validator\Constraint\Bic;
use Nowo\SepaPaymentBundle\Validator\Constraint\CreditCard;
use Nowo\SepaPaymentBundle\Validator\Constraint\Iban;
use Nowo\SepaPaymentBundle\Validator\Constraint\SepaCountry;
use Nowo\SepaPaymentBundle\Validator\Constraint\SepaCreditorIdentifier;
use PHPUnit\Framework\TestCase;

use function count;

/**
 * Regression: constraint violation codes must be valid UUIDs (Bic::INVALID_BIC_ERROR used to contain non-hex characters).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class ConstraintErrorCodesTest extends TestCase
{
    public function testErrorCodesAreValidUuids(): void
    {
        $codes = [
            Bic::INVALID_BIC_ERROR,
            CreditCard::INVALID_CREDIT_CARD_ERROR,
            Iban::INVALID_IBAN_ERROR,
            SepaCountry::INVALID_SEPA_COUNTRY_ERROR,
            SepaCreditorIdentifier::INVALID_SEPA_CREDITOR_IDENTIFIER_ERROR,
        ];

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $code);
        }

        $this->assertCount(count($codes), array_unique($codes));
    }
}
