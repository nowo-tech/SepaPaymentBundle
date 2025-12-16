<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Validator;

use Nowo\SepaPaymentBundle\Validator\BicValidator;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for BicValidator.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class BicValidatorTest extends TestCase
{
    /**
     * BIC validator instance.
     *
     * @var BicValidator
     */
    private BicValidator $validator;

    /**
     * Sets up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->validator = new BicValidator();
    }

    /**
     * Tests valid BIC validation (8 characters).
     *
     * @return void
     */
    public function testValidBic8Chars(): void
    {
        $validBics = [
            'ESPBESMM',
            'DEUTDEFF',
            'BNPAFRPP',
            'CHASUS33',
        ];

        foreach ($validBics as $bic) {
            $this->assertTrue($this->validator->isValid($bic), "BIC should be valid: {$bic}");
        }
    }

    /**
     * Tests valid BIC validation (11 characters).
     *
     * @return void
     */
    public function testValidBic11Chars(): void
    {
        $validBics = [
            'ESPBESMMXXX',
            'DEUTDEFF500',
            'BNPAFRPP123',
        ];

        foreach ($validBics as $bic) {
            $this->assertTrue($this->validator->isValid($bic), "BIC should be valid: {$bic}");
        }
    }

    /**
     * Tests invalid BIC validation.
     *
     * @return void
     */
    public function testInvalidBic(): void
    {
        $invalidBics = [
            'ESPBESM', // Too short (7 chars)
            'ESPBESMMM', // Wrong length (9 chars)
            'ESPBESMMXXXX', // Too long (12 chars)
            '1234ESMM', // Numbers in bank code
            'ESPBES12', // Numbers in country code
            'INVALID', // Invalid format
        ];

        foreach ($invalidBics as $bic) {
            $this->assertFalse($this->validator->isValid($bic), "BIC should be invalid: {$bic}");
        }
    }

    /**
     * Tests BIC normalization.
     *
     * @return void
     */
    public function testNormalize(): void
    {
        $this->assertEquals('ESPBESMM', $this->validator->normalize('espb esmm'));
        $this->assertEquals('ESPBESMM', $this->validator->normalize('  ESPBESMM  '));
        $this->assertEquals('ESPBESMMXXX', $this->validator->normalize('espb esmm xxx'));
    }

    /**
     * Tests bank code extraction.
     *
     * @return void
     */
    public function testGetBankCode(): void
    {
        $this->assertEquals('ESPB', $this->validator->getBankCode('ESPBESMM'));
        $this->assertEquals('DEUT', $this->validator->getBankCode('DEUTDEFF500'));
    }

    /**
     * Tests country code extraction.
     *
     * @return void
     */
    public function testGetCountryCode(): void
    {
        $this->assertEquals('ES', $this->validator->getCountryCode('ESPBESMM'));
        $this->assertEquals('DE', $this->validator->getCountryCode('DEUTDEFF500'));
    }

    /**
     * Tests location code extraction.
     *
     * @return void
     */
    public function testGetLocationCode(): void
    {
        $this->assertEquals('MM', $this->validator->getLocationCode('ESPBESMM'));
        $this->assertEquals('FF', $this->validator->getLocationCode('DEUTDEFF500'));
    }

    /**
     * Tests branch code extraction (8 chars BIC).
     *
     * @return void
     */
    public function testGetBranchCode8Chars(): void
    {
        $this->assertNull($this->validator->getBranchCode('ESPBESMM'));
    }

    /**
     * Tests branch code extraction (11 chars BIC).
     *
     * @return void
     */
    public function testGetBranchCode11Chars(): void
    {
        $this->assertEquals('XXX', $this->validator->getBranchCode('ESPBESMMXXX'));
        $this->assertEquals('500', $this->validator->getBranchCode('DEUTDEFF500'));
    }
}

