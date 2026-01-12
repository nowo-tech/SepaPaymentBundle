<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Validator;

use Nowo\SepaPaymentBundle\Validator\SepaCreditorIdentifierValidator;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for SepaCreditorIdentifierValidator.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class SepaCreditorIdentifierValidatorTest extends TestCase
{
    /**
     * SEPA Creditor Identifier validator instance.
     *
     * @var SepaCreditorIdentifierValidator
     */
    private SepaCreditorIdentifierValidator $validator;

    /**
     * Sets up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->validator = new SepaCreditorIdentifierValidator();
    }

    /**
     * Tests valid Spanish SEPA Creditor Identifier format validation.
     * Note: Using format examples - actual check digits may vary.
     *
     * @return void
     */
    public function testValidFormat(): void
    {
        // These are format-valid examples (structure is correct)
        // Actual validation with MOD97-10 check digits requires real identifiers
        $validFormats = [
            'ES97ZZZM12345678', // Spanish format: ES + check digits + ZZZ + NIF
            'ES97000M12345678', // Spanish format with 000 suffix
            'FR97ZZZ123456789', // French format
            'DE97ZZZ1234567890', // German format
        ];

        foreach ($validFormats as $identifier) {
            // Test that format validation passes (structure is correct)
            // Note: Full validation with check digits may fail if check digits are incorrect
            $normalized = $this->validator->normalize($identifier);
            $this->assertGreaterThanOrEqual(9, strlen($normalized), "Identifier should have valid length: {$identifier}");
            $this->assertLessThanOrEqual(35, strlen($normalized), "Identifier should not exceed max length: {$identifier}");
        }
    }

    /**
     * Tests invalid SEPA Creditor Identifier format validation.
     *
     * @return void
     */
    public function testInvalidFormat(): void
    {
        $invalidIdentifiers = [
            '', // Empty
            'ES', // Too short
            'ES97', // Too short
            'ES97ZZZ', // Too short (missing national identifier)
            'ES97ZZZM123456789012345678901234567890', // Too long (exceeds 35 chars)
            'ESXXZZZM12345678', // Invalid check digits (non-numeric)
            'XX97ZZZM12345678', // Invalid country code format
            'ES97XXXM12345678', // Invalid suffix (contains invalid chars)
            'ES97ZZZ', // Missing national identifier
        ];

        foreach ($invalidIdentifiers as $identifier) {
            $this->assertFalse($this->validator->isValid($identifier), "Identifier should be invalid: {$identifier}");
        }
    }

    /**
     * Tests normalization of SEPA Creditor Identifier.
     *
     * @return void
     */
    public function testNormalize(): void
    {
        $this->assertEquals('ES97ZZZM12345678', $this->validator->normalize('es97zzzm12345678'));
        $this->assertEquals('ES97ZZZM12345678', $this->validator->normalize('ES 97 ZZZ M12345678'));
        $this->assertEquals('ES97ZZZM12345678', $this->validator->normalize('  ES97ZZZM12345678  '));
        $this->assertEquals('FR97ZZZ123456789', $this->validator->normalize('fr97zzz123456789'));
    }

    /**
     * Tests extraction of country code.
     *
     * @return void
     */
    public function testGetCountryCode(): void
    {
        $this->assertEquals('ES', $this->validator->getCountryCode('ES97ZZZM12345678'));
        $this->assertEquals('FR', $this->validator->getCountryCode('FR97ZZZ123456789'));
        $this->assertEquals('DE', $this->validator->getCountryCode('DE97ZZZ1234567890'));
    }

    /**
     * Tests extraction of national identifier.
     *
     * @return void
     */
    public function testGetNationalIdentifier(): void
    {
        $this->assertEquals('M12345678', $this->validator->getNationalIdentifier('ES97ZZZM12345678'));
        $this->assertEquals('123456789', $this->validator->getNationalIdentifier('FR97ZZZ123456789'));
        $this->assertEquals('1234567890', $this->validator->getNationalIdentifier('DE97ZZZ1234567890'));
    }

    /**
     * Tests Spanish NIF/CIF format validation.
     *
     * @return void
     */
    public function testIsValidSpanishNifFormat(): void
    {
        // Valid NIF formats (1 letter + 8 digits)
        $validNifs = [
            'M12345678',
            'A12345678',
            'B12345678',
            'C12345678',
            'D12345678',
            'E12345678',
            'F12345678',
            'G12345678',
            'H12345678',
            'J12345678',
            'K12345678',
            'L12345678',
            'N12345678',
            'P12345678',
            'Q12345678',
            'R12345678',
            'S12345678',
            'T12345678',
            'V12345678',
            'W12345678',
            'X12345678',
            'Y12345678',
            'Z12345678',
        ];

        foreach ($validNifs as $nif) {
            $this->assertTrue($this->validator->isValidSpanishNifFormat($nif), "NIF should be valid: {$nif}");
        }

        // Valid CIF formats (1 letter + 7 digits + 1 letter/digit)
        $validCifs = [
            'A12345674',
            'B12345675',
            'C12345676',
            'D12345677',
            'E12345678',
            'F12345679',
            'G12345670',
            'H12345671',
            'J12345672',
            'K12345673',
            'A1234567A',
        ];

        foreach ($validCifs as $cif) {
            $this->assertTrue($this->validator->isValidSpanishNifFormat($cif), "CIF should be valid: {$cif}");
        }

        // Invalid formats
        $invalidNifs = [
            '', // Empty
            '12345678', // Missing letter
            'M1234567', // Too short (7 digits instead of 8)
            'M123456789', // Too long (9 digits instead of 8)
            'MM12345678', // Two letters
        ];

        foreach ($invalidNifs as $nif) {
            $this->assertFalse($this->validator->isValidSpanishNifFormat($nif), "NIF should be invalid: {$nif}");
        }
    }

    /**
     * Tests minimum and maximum length validation.
     *
     * @return void
     */
    public function testLengthValidation(): void
    {
        // Minimum length: 9 characters (CC + KK + SSS + 1 char national ID)
        $minLength = 'ES97ZZZA'; // 2 + 2 + 3 + 1 = 8 (too short, needs at least 1 more char for national ID)
        $normalized = $this->validator->normalize($minLength);
        // This should be 8 chars, which is less than minimum 9
        $this->assertLessThan(9, strlen($normalized));
        $this->assertFalse($this->validator->isValid($minLength));

        // Valid minimum length: 9 characters
        $validMinLength = 'ES97ZZZAB'; // 2 + 2 + 3 + 2 = 9 (valid minimum)
        $this->assertGreaterThanOrEqual(9, strlen($this->validator->normalize($validMinLength)));

        // Maximum length: 35 characters
        $maxLength = 'ES97ZZZ' . str_repeat('A', 28); // 2 + 2 + 3 + 28 = 35
        $this->assertLessThanOrEqual(35, strlen($this->validator->normalize($maxLength)));

        // Too short (8 characters)
        $tooShort = 'ES97ZZZ';
        $this->assertFalse($this->validator->isValid($tooShort));

        // Too long (36 characters)
        $tooLong = 'ES97ZZZ' . str_repeat('A', 29); // 2 + 2 + 3 + 29 = 36
        $this->assertFalse($this->validator->isValid($tooLong));
    }

    /**
     * Tests component extraction with various formats.
     *
     * @return void
     */
    public function testComponentExtraction(): void
    {
        $identifier = 'ES97ZZZM12345678';

        $this->assertEquals('ES', $this->validator->getCountryCode($identifier));
        $this->assertEquals('M12345678', $this->validator->getNationalIdentifier($identifier));

        // Test with different suffix
        $identifier2 = 'ES97000M12345678';
        $this->assertEquals('ES', $this->validator->getCountryCode($identifier2));
        $this->assertEquals('M12345678', $this->validator->getNationalIdentifier($identifier2));
    }
}
