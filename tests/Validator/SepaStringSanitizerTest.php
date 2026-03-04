<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Validator;

use Nowo\SepaPaymentBundle\Validator\SepaStringSanitizer;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for SepaStringSanitizer.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class SepaStringSanitizerTest extends TestCase
{
    private SepaStringSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new SepaStringSanitizer();
    }

    public function testIsValidWithValidString(): void
    {
        $this->assertTrue($this->sanitizer->isValid('John Doe'));
        $this->assertTrue($this->sanitizer->isValid('Company Ltd.'));
        $this->assertTrue($this->sanitizer->isValid('Street 123, Apt. 4'));
        $this->assertTrue($this->sanitizer->isValid('Invoice 12345'));
        $this->assertTrue($this->sanitizer->isValid('Invoice/12345'));
    }

    public function testIsValidWithInvalidString(): void
    {
        $this->assertFalse($this->sanitizer->isValid(''));
        $this->assertFalse($this->sanitizer->isValid('Test@Company'));
        $this->assertFalse($this->sanitizer->isValid('Test&Company'));
        $this->assertFalse($this->sanitizer->isValid('Test*Company'));
    }

    public function testSanitizeWithAccentedCharacters(): void
    {
        $this->assertEquals('Jose', $this->sanitizer->sanitize('José'));
        $this->assertEquals('Munoz', $this->sanitizer->sanitize('Muñoz'));
        $this->assertEquals('Cafe', $this->sanitizer->sanitize('Café'));
        $this->assertEquals('Garcia', $this->sanitizer->sanitize('García'));
    }

    public function testSanitizeRemovesInvalidCharacters(): void
    {
        $this->assertEquals('Test Company', $this->sanitizer->sanitize('Test@Company'));
        $this->assertEquals('Test Company', $this->sanitizer->sanitize('Test&Company'));
        $this->assertEquals('Test Company', $this->sanitizer->sanitize('Test*Company'));
    }

    public function testSanitizeRemovesMultipleSpaces(): void
    {
        $this->assertEquals('Test Company', $this->sanitizer->sanitize('Test    Company'));
        $this->assertEquals('Test Company', $this->sanitizer->sanitize('Test  Company'));
    }

    public function testIsValidNameLength(): void
    {
        $shortName = str_repeat('A', 50);
        $maxName   = str_repeat('A', 70);
        $longName  = str_repeat('A', 71);

        $this->assertTrue($this->sanitizer->isValidNameLength($shortName));
        $this->assertTrue($this->sanitizer->isValidNameLength($maxName));
        $this->assertFalse($this->sanitizer->isValidNameLength($longName));
    }

    public function testTruncateName(): void
    {
        $longName  = str_repeat('A', 100);
        $truncated = $this->sanitizer->truncateName($longName);

        $this->assertEquals(70, mb_strlen($truncated));
        $this->assertEquals(str_repeat('A', 70), $truncated);
    }

    public function testTruncateRemittanceInfo(): void
    {
        $longInfo  = str_repeat('A', 200);
        $truncated = $this->sanitizer->truncateRemittanceInfo($longInfo);

        $this->assertEquals(140, mb_strlen($truncated));
    }

    public function testIsValidStreetLength(): void
    {
        $shortStreet = str_repeat('A', 50);
        $maxStreet   = str_repeat('A', 70);
        $longStreet  = str_repeat('A', 71);

        $this->assertTrue($this->sanitizer->isValidStreetLength($shortStreet));
        $this->assertTrue($this->sanitizer->isValidStreetLength($maxStreet));
        $this->assertFalse($this->sanitizer->isValidStreetLength($longStreet));
    }

    public function testIsValidCityLength(): void
    {
        $shortCity = str_repeat('A', 20);
        $maxCity   = str_repeat('A', 35);
        $longCity  = str_repeat('A', 36);

        $this->assertTrue($this->sanitizer->isValidCityLength($shortCity));
        $this->assertTrue($this->sanitizer->isValidCityLength($maxCity));
        $this->assertFalse($this->sanitizer->isValidCityLength($longCity));
    }

    public function testIsValidPostalCodeLength(): void
    {
        $shortPostalCode = str_repeat('A', 5);
        $maxPostalCode   = str_repeat('A', 16);
        $longPostalCode  = str_repeat('A', 17);

        $this->assertTrue($this->sanitizer->isValidPostalCodeLength($shortPostalCode));
        $this->assertTrue($this->sanitizer->isValidPostalCodeLength($maxPostalCode));
        $this->assertFalse($this->sanitizer->isValidPostalCodeLength($longPostalCode));
    }

    public function testIsValidRemittanceInfoLength(): void
    {
        $shortInfo = str_repeat('A', 100);
        $maxInfo   = str_repeat('A', 140);
        $longInfo  = str_repeat('A', 141);

        $this->assertTrue($this->sanitizer->isValidRemittanceInfoLength($shortInfo));
        $this->assertTrue($this->sanitizer->isValidRemittanceInfoLength($maxInfo));
        $this->assertFalse($this->sanitizer->isValidRemittanceInfoLength($longInfo));
    }

    public function testValidateAndSanitize(): void
    {
        $input  = 'José García & Company';
        $result = $this->sanitizer->validateAndSanitize($input);

        $this->assertEquals('Jose Garcia Company', $result);
    }

    public function testSanitizeWithEmptyString(): void
    {
        $this->assertEquals('', $this->sanitizer->sanitize(''));
    }

    public function testSanitizeWithOnlyInvalidCharacters(): void
    {
        $this->assertEquals('', $this->sanitizer->sanitize('@@@***'));
    }

    public function testSanitizeWithSpecialCharacters(): void
    {
        $this->assertEquals('Test/123', $this->sanitizer->sanitize('Test/123'));
        $this->assertEquals('Test-123', $this->sanitizer->sanitize('Test-123'));
        $this->assertEquals('Test?123', $this->sanitizer->sanitize('Test?123'));
        $this->assertEquals('Test(123)', $this->sanitizer->sanitize('Test(123)'));
        $this->assertEquals('Test.123', $this->sanitizer->sanitize('Test.123'));
        $this->assertEquals('Test,123', $this->sanitizer->sanitize('Test,123'));
        $this->assertEquals("Test'123", $this->sanitizer->sanitize("Test'123"));
        $this->assertEquals('Test+123', $this->sanitizer->sanitize('Test+123'));
    }

    public function testTruncateNameWithShortName(): void
    {
        $shortName = str_repeat('A', 50);
        $result    = $this->sanitizer->truncateName($shortName);

        $this->assertEquals($shortName, $result);
    }

    public function testTruncateRemittanceInfoWithShortInfo(): void
    {
        $shortInfo = str_repeat('A', 100);
        $result    = $this->sanitizer->truncateRemittanceInfo($shortInfo);

        $this->assertEquals($shortInfo, $result);
    }
}
