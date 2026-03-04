<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Validator;

use ArrayObject;
use Nowo\SepaPaymentBundle\Cache\ValidationCache;
use Nowo\SepaPaymentBundle\Tests\Cache\ArrayCache;
use Nowo\SepaPaymentBundle\Validator\CachedIbanValidator;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use PHPUnit\Framework\TestCase;

use function strlen;

/**
 * Tests for CachedIbanValidator.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class CachedIbanValidatorTest extends TestCase
{
    /**
     * Test validation with cache.
     */
    public function testValidationWithCache(): void
    {
        $ibanValidator   = new IbanValidator();
        $cache           = new ArrayObject();
        $validationCache = new ValidationCache($cache);
        $cachedValidator = new CachedIbanValidator($ibanValidator, $validationCache);

        $iban = 'ES9121000418450200051332';

        // First call should validate and cache
        $result1 = $cachedValidator->isValid($iban);
        $this->assertTrue($result1);

        // Second call should use cache
        $result2 = $cachedValidator->isValid($iban);
        $this->assertTrue($result2);
        $this->assertEquals($result1, $result2);
    }

    /**
     * Test validation without cache.
     */
    public function testValidationWithoutCache(): void
    {
        $ibanValidator   = new IbanValidator();
        $cachedValidator = new CachedIbanValidator($ibanValidator);

        $iban = 'ES9121000418450200051332';
        $this->assertTrue($cachedValidator->isValid($iban));
    }

    /**
     * Test delegate methods.
     */
    public function testDelegateMethods(): void
    {
        $ibanValidator   = new IbanValidator();
        $cachedValidator = new CachedIbanValidator($ibanValidator);

        $iban = 'ES9121000418450200051332';

        $this->assertEquals('ES9121000418450200051332', $cachedValidator->normalize($iban));
        $this->assertStringContainsString(' ', $cachedValidator->format($iban));
        $this->assertEquals('ES', $cachedValidator->getCountryCode($iban));
        $this->assertEquals('91', $cachedValidator->getCheckDigits($iban));
        $this->assertEquals('21000418450200051332', $cachedValidator->getBban($iban));
    }

    /**
     * Test invalid IBAN caching.
     */
    public function testInvalidIbanCaching(): void
    {
        $ibanValidator   = new IbanValidator();
        $cache           = new ArrayObject();
        $validationCache = new ValidationCache($cache);
        $cachedValidator = new CachedIbanValidator($ibanValidator, $validationCache);

        $invalidIban = 'INVALID_IBAN';

        // First call should validate and cache
        $result1 = $cachedValidator->isValid($invalidIban);
        $this->assertFalse($result1);

        // Second call should use cache
        $result2 = $cachedValidator->isValid($invalidIban);
        $this->assertFalse($result2);
    }

    /**
     * Test cache hit when using ArrayCache adapter (cache actually stores and returns).
     */
    public function testValidationWithArrayCacheCacheHit(): void
    {
        $ibanValidator   = new IbanValidator();
        $arrayCache      = new ArrayCache();
        $validationCache = new ValidationCache($arrayCache);
        $cachedValidator = new CachedIbanValidator($ibanValidator, $validationCache);

        $iban    = 'ES9121000418450200051332';
        $result1 = $cachedValidator->isValid($iban);
        $this->assertTrue($result1);

        $result2 = $cachedValidator->isValid($iban);
        $this->assertTrue($result2);
    }

    /**
     * Test calculateCheckDigits delegate.
     */
    public function testCalculateCheckDigits(): void
    {
        $ibanValidator   = new IbanValidator();
        $cachedValidator = new CachedIbanValidator($ibanValidator);
        $digits          = $cachedValidator->calculateCheckDigits('ES0021000418450200051332');
        $this->assertEquals(2, strlen($digits));
        $this->assertMatchesRegularExpression('/^\d{2}$/', $digits);
    }
}
