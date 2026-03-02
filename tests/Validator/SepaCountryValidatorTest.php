<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Validator;

use Nowo\SepaPaymentBundle\Validator\SepaCountryValidator;
use PHPUnit\Framework\TestCase;

use function count;

/**
 * Test cases for SepaCountryValidator.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class SepaCountryValidatorTest extends TestCase
{
    private SepaCountryValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new SepaCountryValidator();
    }

    public function testIsSepaCountryWithValidCountries(): void
    {
        $this->assertTrue($this->validator->isSepaCountry('ES'));
        $this->assertTrue($this->validator->isSepaCountry('FR'));
        $this->assertTrue($this->validator->isSepaCountry('DE'));
        $this->assertTrue($this->validator->isSepaCountry('IT'));
        $this->assertTrue($this->validator->isSepaCountry('GB'));
    }

    public function testIsSepaCountryWithInvalidCountries(): void
    {
        $this->assertFalse($this->validator->isSepaCountry('US'));
        $this->assertFalse($this->validator->isSepaCountry('CA'));
        $this->assertFalse($this->validator->isSepaCountry('MX'));
        $this->assertFalse($this->validator->isSepaCountry('XX'));
    }

    public function testIsSepaCountryCaseInsensitive(): void
    {
        $this->assertTrue($this->validator->isSepaCountry('es'));
        $this->assertTrue($this->validator->isSepaCountry('Es'));
        $this->assertTrue($this->validator->isSepaCountry('ES'));
    }

    public function testIsSepaCountryFromIban(): void
    {
        $this->assertTrue($this->validator->isSepaCountryFromIban('ES9121000418450200051332'));
        $this->assertTrue($this->validator->isSepaCountryFromIban('FR1420041010050500013M02606'));
        $this->assertFalse($this->validator->isSepaCountryFromIban('US64SVBKUS6S3300958879'));
    }

    public function testIsSepaCountryFromIbanWithShortIban(): void
    {
        $this->assertFalse($this->validator->isSepaCountryFromIban('E'));
        $this->assertFalse($this->validator->isSepaCountryFromIban(''));
    }

    public function testGetSepaCountries(): void
    {
        $countries = $this->validator->getSepaCountries();

        $this->assertIsArray($countries);
        $this->assertContains('ES', $countries);
        $this->assertContains('FR', $countries);
        $this->assertContains('DE', $countries);
        $this->assertGreaterThan(30, count($countries));
    }

    public function testGetCountryName(): void
    {
        $this->assertEquals('Spain', $this->validator->getCountryName('ES'));
        $this->assertEquals('France', $this->validator->getCountryName('FR'));
        $this->assertEquals('Germany', $this->validator->getCountryName('DE'));
        $this->assertEquals('XX', $this->validator->getCountryName('XX')); // Unknown country returns code
    }
}
