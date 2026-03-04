<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Validator;

use Nowo\SepaPaymentBundle\Validator\CreditCardValidator;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for CreditCardValidator.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class CreditCardValidatorTest extends TestCase
{
    /**
     * Credit card validator instance.
     */
    private CreditCardValidator $validator;

    /**
     * Sets up the test environment.
     */
    protected function setUp(): void
    {
        $this->validator = new CreditCardValidator();
    }

    /**
     * Tests valid credit card numbers.
     */
    public function testValidCardNumbers(): void
    {
        // Valid test card numbers (Luhn algorithm)
        $validCards = [
            '4532015112830366', // Visa
            '5555555555554444', // Mastercard
            '378282246310005', // Amex
            '6011111111111117', // Discover
        ];

        foreach ($validCards as $card) {
            $this->assertTrue($this->validator->isValid($card), "Card should be valid: {$card}");
        }
    }

    /**
     * Tests invalid credit card numbers.
     */
    public function testInvalidCardNumbers(): void
    {
        $invalidCards = [
            '4532015112830365', // Invalid Luhn
            '12345', // Too short
            '45320151128303661234567890', // Too long
            'ABCD1234567890', // Non-numeric
            '453201511283036', // Wrong length
        ];

        foreach ($invalidCards as $card) {
            $this->assertFalse($this->validator->isValid($card), "Card should be invalid: {$card}");
        }
    }

    /**
     * Tests card number normalization.
     */
    public function testNormalize(): void
    {
        $this->assertEquals('4532015112830366', $this->validator->normalize('4532 0151 1283 0366'));
        $this->assertEquals('4532015112830366', $this->validator->normalize('4532-0151-1283-0366'));
        $this->assertEquals('4532015112830366', $this->validator->normalize('  4532 0151 1283 0366  '));
    }

    /**
     * Tests card number formatting.
     */
    public function testFormat(): void
    {
        $this->assertEquals('4532 0151 1283 0366', $this->validator->format('4532015112830366'));
        $this->assertEquals('3782 8224 6310 005', $this->validator->format('378282246310005'));
    }

    /**
     * Tests card type detection - Visa.
     */
    public function testGetCardTypeVisa(): void
    {
        $this->assertEquals(CreditCardValidator::TYPE_VISA, $this->validator->getCardType('4532015112830366'));
        $this->assertEquals(CreditCardValidator::TYPE_VISA, $this->validator->getCardType('4111111111111'));
    }

    /**
     * Tests card type detection - Mastercard.
     */
    public function testGetCardTypeMastercard(): void
    {
        $this->assertEquals(CreditCardValidator::TYPE_MASTERCARD, $this->validator->getCardType('5555555555554444'));
        $this->assertEquals(CreditCardValidator::TYPE_MASTERCARD, $this->validator->getCardType('2221000000000009'));
    }

    /**
     * Tests card type detection - Amex.
     */
    public function testGetCardTypeAmex(): void
    {
        $this->assertEquals(CreditCardValidator::TYPE_AMEX, $this->validator->getCardType('378282246310005'));
        $this->assertEquals(CreditCardValidator::TYPE_AMEX, $this->validator->getCardType('371449635398431'));
    }

    /**
     * Tests card type detection - Discover.
     */
    public function testGetCardTypeDiscover(): void
    {
        $this->assertEquals(CreditCardValidator::TYPE_DISCOVER, $this->validator->getCardType('6011111111111117'));
        $this->assertEquals(CreditCardValidator::TYPE_DISCOVER, $this->validator->getCardType('6500000000000002'));
    }

    /**
     * Tests card type detection - Unknown.
     */
    public function testGetCardTypeUnknown(): void
    {
        $this->assertEquals(CreditCardValidator::TYPE_UNKNOWN, $this->validator->getCardType('1234567890123456'));
        $this->assertEquals(CreditCardValidator::TYPE_UNKNOWN, $this->validator->getCardType(''));
    }

    /**
     * Tests card type detection - Diners Club.
     */
    public function testGetCardTypeDinersClub(): void
    {
        $this->assertEquals(CreditCardValidator::TYPE_DINERS_CLUB, $this->validator->getCardType('30569309025904'));
        $this->assertEquals(CreditCardValidator::TYPE_DINERS_CLUB, $this->validator->getCardType('36942143870374'));
    }

    /**
     * Tests card type detection - JCB.
     */
    public function testGetCardTypeJcb(): void
    {
        $this->assertEquals(CreditCardValidator::TYPE_JCB, $this->validator->getCardType('3530111333300000'));
    }

    /**
     * Tests getCardType when normalized is '0' returns unknown.
     */
    public function testGetCardTypeWithZeroReturnsUnknown(): void
    {
        $this->assertEquals(CreditCardValidator::TYPE_UNKNOWN, $this->validator->getCardType('0'));
    }

    /**
     * Tests mask with short number (length < 4).
     */
    public function testMaskWithShortNumber(): void
    {
        $this->assertEquals('***', $this->validator->mask('123'));
        $this->assertEquals('*', $this->validator->mask('1'));
        $this->assertEquals('', $this->validator->mask(''));
    }

    /**
     * Tests BIN extraction.
     */
    public function testGetBin(): void
    {
        $this->assertEquals('453201', $this->validator->getBin('4532015112830366'));
        $this->assertEquals('555555', $this->validator->getBin('5555555555554444'));
    }

    /**
     * Tests last four digits extraction.
     */
    public function testGetLastFour(): void
    {
        $this->assertEquals('0366', $this->validator->getLastFour('4532015112830366'));
        $this->assertEquals('4444', $this->validator->getLastFour('5555555555554444'));
        $this->assertEquals('0005', $this->validator->getLastFour('378282246310005'));
    }

    /**
     * Tests card number masking.
     */
    public function testMask(): void
    {
        $this->assertEquals('************0366', $this->validator->mask('4532015112830366'));
        // 16-digit card: 16 - 4 = 12 mask characters
        $this->assertEquals('############0366', $this->validator->mask('4532015112830366', '#'));
    }

    /**
     * Tests validation for specific card type.
     */
    public function testIsValidForType(): void
    {
        $this->assertTrue($this->validator->isValidForType('4532015112830366', CreditCardValidator::TYPE_VISA));
        $this->assertTrue($this->validator->isValidForType('5555555555554444', CreditCardValidator::TYPE_MASTERCARD));
        $this->assertFalse($this->validator->isValidForType('4532015112830366', CreditCardValidator::TYPE_MASTERCARD));
        $this->assertFalse($this->validator->isValidForType('12345', CreditCardValidator::TYPE_VISA));
    }
}
