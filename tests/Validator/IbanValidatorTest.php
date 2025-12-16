<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Validator;

use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for IbanValidator.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class IbanValidatorTest extends TestCase
{
    /**
     * IBAN validator instance.
     *
     * @var IbanValidator
     */
    private IbanValidator $validator;

    /**
     * Sets up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->validator = new IbanValidator();
    }

    /**
     * Tests valid IBAN validation.
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
     */
    public function testNormalize(): void
    {
        $this->assertEquals('ES9121000418450200051332', $this->validator->normalize('es91 2100 0418 4502 0005 1332'));
        $this->assertEquals('ES9121000418450200051332', $this->validator->normalize('  ES9121000418450200051332  '));
        $this->assertEquals('GB82WEST12345698765432', $this->validator->normalize('gb82 west 1234 5698 7654 32'));
    }

    /**
     * Tests IBAN formatting.
     *
     * @return void
     */
    public function testFormat(): void
    {
        $this->assertEquals('ES91 2100 0418 4502 0005 1332', $this->validator->format('ES9121000418450200051332'));
        $this->assertEquals('GB82 WEST 1234 5698 7654 32', $this->validator->format('GB82WEST12345698765432'));
    }

    /**
     * Tests country code extraction.
     *
     * @return void
     */
    public function testGetCountryCode(): void
    {
        $this->assertEquals('ES', $this->validator->getCountryCode('ES9121000418450200051332'));
        $this->assertEquals('GB', $this->validator->getCountryCode('GB82WEST12345698765432'));
        $this->assertEquals('FR', $this->validator->getCountryCode('FR1420041010050500013M02606'));
    }

    /**
     * Tests check digits extraction.
     *
     * @return void
     */
    public function testGetCheckDigits(): void
    {
        $this->assertEquals('91', $this->validator->getCheckDigits('ES9121000418450200051332'));
        $this->assertEquals('82', $this->validator->getCheckDigits('GB82WEST12345698765432'));
    }

    /**
     * Tests BBAN extraction.
     *
     * @return void
     */
    public function testGetBban(): void
    {
        $this->assertEquals('21000418450200051332', $this->validator->getBban('ES9121000418450200051332'));
        $this->assertEquals('WEST12345698765432', $this->validator->getBban('GB82WEST12345698765432'));
    }

    /**
     * Tests check digits calculation.
     *
     * @return void
     */
    public function testCalculateCheckDigits(): void
    {
        // Test with Spanish IBAN
        $ibanWithPlaceholder = 'ES0021000418450200051332';
        $calculated = $this->validator->calculateCheckDigits($ibanWithPlaceholder);
        $this->assertEquals('91', $calculated);

        // Test with UK IBAN
        $ibanWithPlaceholder = 'GB00WEST12345698765432';
        $calculated = $this->validator->calculateCheckDigits($ibanWithPlaceholder);
        $this->assertEquals('82', $calculated);
    }
}

