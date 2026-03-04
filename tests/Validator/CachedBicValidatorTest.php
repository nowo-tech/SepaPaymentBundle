<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Validator;

use ArrayObject;
use Nowo\SepaPaymentBundle\Cache\ValidationCache;
use Nowo\SepaPaymentBundle\Tests\Cache\ArrayCache;
use Nowo\SepaPaymentBundle\Validator\BicValidator;
use Nowo\SepaPaymentBundle\Validator\CachedBicValidator;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CachedBicValidator.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class CachedBicValidatorTest extends TestCase
{
    /**
     * Test validation with cache.
     */
    public function testValidationWithCache(): void
    {
        $bicValidator    = new BicValidator();
        $cache           = new ArrayObject();
        $validationCache = new ValidationCache($cache);
        $cachedValidator = new CachedBicValidator($bicValidator, $validationCache);

        $bic = 'CAIXESBBXXX';

        // First call should validate and cache
        $result1 = $cachedValidator->isValid($bic);
        $this->assertTrue($result1);

        // Second call should use cache
        $result2 = $cachedValidator->isValid($bic);
        $this->assertTrue($result2);
        $this->assertEquals($result1, $result2);
    }

    /**
     * Test validation without cache.
     */
    public function testValidationWithoutCache(): void
    {
        $bicValidator    = new BicValidator();
        $cachedValidator = new CachedBicValidator($bicValidator);

        $bic = 'CAIXESBBXXX';
        $this->assertTrue($cachedValidator->isValid($bic));
    }

    /**
     * Test delegate methods.
     */
    public function testDelegateMethods(): void
    {
        $bicValidator    = new BicValidator();
        $cachedValidator = new CachedBicValidator($bicValidator);

        $bic = 'CAIXESBBXXX';

        $this->assertEquals('CAIXESBBXXX', $cachedValidator->normalize($bic));
        $this->assertEquals('CAIX', $cachedValidator->getBankCode($bic));
        $this->assertEquals('ES', $cachedValidator->getCountryCode($bic));
        $this->assertEquals('BB', $cachedValidator->getLocationCode($bic));
        $this->assertEquals('XXX', $cachedValidator->getBranchCode($bic));
    }

    /**
     * Test invalid BIC caching.
     */
    public function testInvalidBicCaching(): void
    {
        $bicValidator    = new BicValidator();
        $cache           = new ArrayObject();
        $validationCache = new ValidationCache($cache);
        $cachedValidator = new CachedBicValidator($bicValidator, $validationCache);

        $invalidBic = 'INVALID';

        // First call should validate and cache
        $result1 = $cachedValidator->isValid($invalidBic);
        $this->assertFalse($result1);

        // Second call should use cache
        $result2 = $cachedValidator->isValid($invalidBic);
        $this->assertFalse($result2);
    }

    /**
     * Test cache hit when using ArrayCache adapter (cache actually stores and returns).
     */
    public function testValidationWithArrayCacheCacheHit(): void
    {
        $bicValidator    = new BicValidator();
        $arrayCache      = new ArrayCache();
        $validationCache = new ValidationCache($arrayCache);
        $cachedValidator = new CachedBicValidator($bicValidator, $validationCache);

        $bic     = 'CAIXESBBXXX';
        $result1 = $cachedValidator->isValid($bic);
        $this->assertTrue($result1);

        $result2 = $cachedValidator->isValid($bic);
        $this->assertTrue($result2);
    }
}
