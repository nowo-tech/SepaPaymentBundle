<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Converter;

use Nowo\SepaPaymentBundle\Converter\CccConverter;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for CccConverter.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class CccConverterTest extends TestCase
{
    /**
     * CCC converter instance.
     *
     * @var CccConverter
     */
    private CccConverter $converter;

    /**
     * Sets up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $ibanValidator = new IbanValidator();
        $this->converter = new CccConverter($ibanValidator);
    }

    /**
     * Tests CCC to IBAN conversion.
     *
     * @return void
     */
    public function testCccToIban(): void
    {
        // Valid Spanish CCC
        $ccc = '21000418450200051332';
        $iban = $this->converter->cccToIban($ccc);

        $this->assertStringStartsWith('ES', $iban);
        $this->assertEquals(24, strlen($iban));
        $this->assertEquals('21000418450200051332', substr($iban, 4));
    }

    /**
     * Tests CCC to IBAN conversion with spaces.
     *
     * @return void
     */
    public function testCccToIbanWithSpaces(): void
    {
        $ccc = '2100 0418 4502 0005 1332';
        $iban = $this->converter->cccToIban($ccc);

        $this->assertStringStartsWith('ES', $iban);
        $this->assertEquals(24, strlen($iban));
    }

    /**
     * Tests CCC to IBAN conversion with invalid format.
     *
     * @return void
     */
    public function testCccToIbanInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid CCC format. Expected 20 digits.');

        $this->converter->cccToIban('12345');
    }

    /**
     * Tests CCC validation with valid CCC.
     *
     * @return void
     */
    public function testIsValidCcc(): void
    {
        // Valid CCC: 2100 0418 45 0200051332
        $validCcc = '21000418450200051332';
        $this->assertTrue($this->converter->isValidCcc($validCcc));
    }

    /**
     * Tests CCC validation with invalid CCC.
     *
     * @return void
     */
    public function testIsValidCccInvalid(): void
    {
        $invalidCccs = [
            '12345', // Too short
            '210004184502000513321', // Too long
            '21000418450200051331', // Wrong check digits
            'ABCD0418450200051332', // Non-numeric
        ];

        foreach ($invalidCccs as $ccc) {
            $this->assertFalse($this->converter->isValidCcc($ccc), "CCC should be invalid: {$ccc}");
        }
    }

    /**
     * Tests bank code extraction.
     *
     * @return void
     */
    public function testGetBankCode(): void
    {
        $ccc = '2100 0418 4502 0005 1332';
        $this->assertEquals('2100', $this->converter->getBankCode($ccc));
    }

    /**
     * Tests branch code extraction.
     *
     * @return void
     */
    public function testGetBranchCode(): void
    {
        $ccc = '2100 0418 4502 0005 1332';
        $this->assertEquals('0418', $this->converter->getBranchCode($ccc));
    }

    /**
     * Tests account number extraction.
     *
     * @return void
     */
    public function testGetAccountNumber(): void
    {
        $ccc = '2100 0418 4502 0005 1332';
        $this->assertEquals('0200051332', $this->converter->getAccountNumber($ccc));
    }
}

