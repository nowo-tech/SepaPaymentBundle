<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Validator;

use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Test cases for IbanValidator.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class IbanValidatorTest extends TestCase
{
    /**
     * IBAN validator instance.
     */
    private IbanValidator $validator;

    /**
     * Sets up the test environment.
     */
    protected function setUp(): void
    {
        $this->validator = new IbanValidator();
    }

    /**
     * Tests valid IBAN validation.
     */
    public function testValidIban(): void
    {
        $validIbans = [
            'ES9121000418450200051332',
            'GB82WEST12345698765432',
            'FR1420041010050500013M02606',
            'DE89370400440532013000',
        ];

        foreach ($validIbans as $iban) {
            $this->assertTrue($this->validator->isValid($iban), "IBAN should be valid: {$iban}");
        }
    }

    /**
     * Tests invalid IBAN validation.
     */
    public function testInvalidIban(): void
    {
        $invalidIbans = [
            'ES9121000418450200051331', // Wrong check digits
            'INVALID',
            'ES91', // Too short
            'ES91210004184502000513321234567890', // Too long
            'ES912100041845020005133', // Wrong length
        ];

        foreach ($invalidIbans as $iban) {
            $this->assertFalse($this->validator->isValid($iban), "IBAN should be invalid: {$iban}");
        }
    }

    /**
     * Tests IBAN normalization.
     */
    public function testNormalize(): void
    {
        $this->assertEquals('ES9121000418450200051332', $this->validator->normalize('es91 2100 0418 4502 0005 1332'));
        $this->assertEquals('ES9121000418450200051332', $this->validator->normalize('  ES9121000418450200051332  '));
        $this->assertEquals('GB82WEST12345698765432', $this->validator->normalize('gb82 west 1234 5698 7654 32'));
    }

    /**
     * Tests IBAN formatting.
     */
    public function testFormat(): void
    {
        $this->assertEquals('ES91 2100 0418 4502 0005 1332', $this->validator->format('ES9121000418450200051332'));
        $this->assertEquals('GB82 WEST 1234 5698 7654 32', $this->validator->format('GB82WEST12345698765432'));
    }

    /**
     * Tests country code extraction.
     */
    public function testGetCountryCode(): void
    {
        $this->assertEquals('ES', $this->validator->getCountryCode('ES9121000418450200051332'));
        $this->assertEquals('GB', $this->validator->getCountryCode('GB82WEST12345698765432'));
        $this->assertEquals('FR', $this->validator->getCountryCode('FR1420041010050500013M02606'));
    }

    /**
     * Tests check digits extraction.
     */
    public function testGetCheckDigits(): void
    {
        $this->assertEquals('91', $this->validator->getCheckDigits('ES9121000418450200051332'));
        $this->assertEquals('82', $this->validator->getCheckDigits('GB82WEST12345698765432'));
    }

    /**
     * Tests BBAN extraction.
     */
    public function testGetBban(): void
    {
        $this->assertEquals('21000418450200051332', $this->validator->getBban('ES9121000418450200051332'));
        $this->assertEquals('WEST12345698765432', $this->validator->getBban('GB82WEST12345698765432'));
    }

    /**
     * Tests check digits calculation.
     */
    public function testCalculateCheckDigits(): void
    {
        // Test with Spanish IBAN
        $ibanWithPlaceholder = 'ES0021000418450200051332';
        $calculated          = $this->validator->calculateCheckDigits($ibanWithPlaceholder);
        $this->assertEquals('91', $calculated);

        // Test with UK IBAN
        $ibanWithPlaceholder = 'GB00WEST12345698765432';
        $calculated          = $this->validator->calculateCheckDigits($ibanWithPlaceholder);
        $this->assertEquals('82', $calculated);
    }

    /**
     * Tests format with empty string (edge case).
     */
    public function testFormatWithEmptyString(): void
    {
        $this->assertEquals('', $this->validator->format(''));
    }

    /**
     * Tests getBban with short IBAN (less than 4 chars) returns empty string.
     */
    public function testGetBbanWithShortIban(): void
    {
        $this->assertEquals('', $this->validator->getBban('ES'));
        $this->assertEquals('', $this->validator->getBban('ES91'));
    }

    /**
     * Covers validateCheckDigits (private) via reflection.
     */
    public function testValidateCheckDigitsViaReflection(): void
    {
        $ref    = new ReflectionClass(IbanValidator::class);
        $method = $ref->getMethod('validateCheckDigits');
        $this->assertTrue($method->invoke($this->validator, 'ES9121000418450200051332'));
        $this->assertFalse($method->invoke($this->validator, 'ES9021000418450200051332'));
    }

    /**
     * Covers mod97 (private) via reflection.
     */
    public function testMod97ViaReflection(): void
    {
        $ref    = new ReflectionClass(IbanValidator::class);
        $method = $ref->getMethod('mod97');
        $this->assertSame(1, $method->invoke($this->validator, '98'));
        $this->assertSame(0, $method->invoke($this->validator, '97'));
        $this->assertSame(0, $method->invoke($this->validator, '0'));
    }

    /**
     * Covers isValid when IBAN length is greater than 34 (invalid).
     */
    public function testIsValidReturnsFalseWhenLengthExceeds34(): void
    {
        $maxLength = 'ES91' . str_repeat('0', 30);
        $this->assertSame(34, strlen($maxLength));
        $tooLong = $maxLength . '0';
        $this->assertSame(35, strlen($tooLong));
        $this->assertFalse($this->validator->isValid($tooLong));
    }

    /**
     * Covers isValid when IBAN length is less than 15 (invalid).
     */
    public function testIsValidReturnsFalseWhenLengthLessThan15(): void
    {
        $this->assertFalse($this->validator->isValid('ES91210004184'));
        $this->assertFalse($this->validator->isValid('ES91'));
    }

    /**
     * Covers isValid when length is valid but format is invalid (preg_match fails).
     */
    public function testIsValidReturnsFalseWhenFormatInvalid(): void
    {
        $validLengthWrongFormat = 'ES9A21000418450200051332';
        $this->assertSame(24, strlen($validLengthWrongFormat));
        $this->assertFalse($this->validator->isValid($validLengthWrongFormat));
    }
}
