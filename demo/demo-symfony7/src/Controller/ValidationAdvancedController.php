<?php

namespace App\Controller;

use Nowo\SepaPaymentBundle\Validator\BicValidator;
use Nowo\SepaPaymentBundle\Validator\CachedBicValidator;
use Nowo\SepaPaymentBundle\Validator\CachedIbanValidator;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Nowo\SepaPaymentBundle\Validator\SepaBusinessRulesValidator;
use Nowo\SepaPaymentBundle\Validator\SepaCountryValidator;
use Nowo\SepaPaymentBundle\Validator\SepaStringSanitizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ValidationAdvancedController extends AbstractController
{
    /**
     * Demo validation caching for IBAN.
     *
     * @param CachedIbanValidator $cachedValidator Cached IBAN validator
     * @param IbanValidator       $validator       Regular IBAN validator
     * @return JsonResponse
     */
    #[Route('/demo-validation-cache-iban', name: 'demo_validation_cache_iban')]
    public function demoValidationCacheIban(CachedIbanValidator $cachedValidator, IbanValidator $validator): JsonResponse
    {
        $iban = 'ES9121000418450200051332';

        // First call (will cache)
        $start1 = microtime(true);
        $result1 = $cachedValidator->isValid($iban);
        $time1 = microtime(true) - $start1;

        // Second call (from cache)
        $start2 = microtime(true);
        $result2 = $cachedValidator->isValid($iban);
        $time2 = microtime(true) - $start2;

        // Regular validator (no cache)
        $start3 = microtime(true);
        $result3 = $validator->isValid($iban);
        $time3 = microtime(true) - $start3;

        $response = new JsonResponse([
            'iban' => $iban,
            'cachedValidator' => [
                'firstCall' => [
                    'result' => $result1,
                    'time' => round($time1 * 1000, 4) . ' ms',
                    'cached' => false,
                ],
                'secondCall' => [
                    'result' => $result2,
                    'time' => round($time2 * 1000, 4) . ' ms',
                    'cached' => true,
                ],
            ],
            'regularValidator' => [
                'result' => $result3,
                'time' => round($time3 * 1000, 4) . ' ms',
                'cached' => false,
            ],
            'note' => 'Cached validator uses cache on second call, improving performance for repeated validations',
        ]);
        $response->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return $response;
    }

    /**
     * Demo validation caching for BIC.
     *
     * @param CachedBicValidator $cachedValidator Cached BIC validator
     * @param BicValidator       $validator       Regular BIC validator
     * @return JsonResponse
     */
    #[Route('/demo-validation-cache-bic', name: 'demo_validation_cache_bic')]
    public function demoValidationCacheBic(CachedBicValidator $cachedValidator, BicValidator $validator): JsonResponse
    {
        $bic = 'CAIXESBBXXX';

        // First call (will cache)
        $start1 = microtime(true);
        $result1 = $cachedValidator->isValid($bic);
        $time1 = microtime(true) - $start1;

        // Second call (from cache)
        $start2 = microtime(true);
        $result2 = $cachedValidator->isValid($bic);
        $time2 = microtime(true) - $start2;

        // Regular validator (no cache)
        $start3 = microtime(true);
        $result3 = $validator->isValid($bic);
        $time3 = microtime(true) - $start3;

        $response = new JsonResponse([
            'bic' => $bic,
            'cachedValidator' => [
                'firstCall' => [
                    'result' => $result1,
                    'time' => round($time1 * 1000, 4) . ' ms',
                    'cached' => false,
                ],
                'secondCall' => [
                    'result' => $result2,
                    'time' => round($time2 * 1000, 4) . ' ms',
                    'cached' => true,
                ],
            ],
            'regularValidator' => [
                'result' => $result3,
                'time' => round($time3 * 1000, 4) . ' ms',
                'cached' => false,
            ],
            'note' => 'Cached validator uses cache on second call, improving performance for repeated validations',
        ]);
        $response->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return $response;
    }

    /**
     * Demo SEPA String Sanitizer.
     *
     * @param Request            $request  Request object
     * @param SepaStringSanitizer $sanitizer String sanitizer
     * @return JsonResponse
     */
    #[Route('/demo-sepa-string-sanitizer', name: 'demo_sepa_string_sanitizer')]
    public function demoSepaStringSanitizer(Request $request, SepaStringSanitizer $sanitizer): JsonResponse
    {
        $input = $request->query->get('input', 'José García & Company');

        $response = new JsonResponse([
            'input' => $input,
            'sanitized' => $sanitizer->sanitize($input),
            'isValid' => $sanitizer->isValid($input),
            'maxLength' => 70,
            'note' => 'SEPA allows only specific characters. Invalid characters are sanitized automatically.',
        ]);
        $response->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return $response;
    }

    /**
     * Demo SEPA Country Validator.
     *
     * @param Request            $request  Request object
     * @param SepaCountryValidator $validator Country validator
     * @return JsonResponse
     */
    #[Route('/demo-sepa-country-validator', name: 'demo_sepa_country_validator')]
    public function demoSepaCountryValidator(Request $request, SepaCountryValidator $validator): JsonResponse
    {
        $country = $request->query->get('country', 'ES');
        $iban = $request->query->get('iban', 'ES9121000418450200051332');

        $ibanValidator = new IbanValidator();
        $countryFromIban = $ibanValidator->getCountryCode($iban);

        $response = new JsonResponse([
            'country' => $country,
            'isSepaCountry' => $validator->isSepaCountry($country),
            'countryName' => $validator->getCountryName($country),
            'iban' => $iban,
            'countryFromIban' => $countryFromIban,
            'isSepaCountryFromIban' => $validator->isSepaCountryFromIban($iban),
            'note' => 'Validates if a country is a SEPA member. Currently 34 countries are supported.',
        ]);
        $response->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return $response;
    }

    /**
     * Demo SEPA Business Rules Validator.
     *
     * @param Request                  $request  Request object
     * @param SepaBusinessRulesValidator $validator Business rules validator
     * @return JsonResponse
     */
    #[Route('/demo-sepa-business-rules', name: 'demo_sepa_business_rules')]
    public function demoSepaBusinessRules(Request $request, SepaBusinessRulesValidator $validator): JsonResponse
    {
        $amount = (float) $request->query->get('amount', '100.50');
        $count = (int) $request->query->get('count', '1');
        $currency = $request->query->get('currency', 'EUR');
        $dateStr = $request->query->get('date', 'tomorrow');
        $date = new \DateTime($dateStr);

        $response = new JsonResponse([
            'amount' => $amount,
            'isValidTransactionAmount' => $validator->isValidTransactionAmount($amount),
            'transactionCount' => $count,
            'isValidTransactionCount' => $validator->isValidTransactionCount($count),
            'currency' => $currency,
            'isValidSepaCurrency' => $validator->isValidSepaCurrency($currency),
            'executionDate' => $date->format('Y-m-d'),
            'isValidExecutionDate' => $validator->isValidExecutionDate($date),
            'isBusinessDay' => $validator->isBusinessDay($date),
            'maxAmount' => 999999999.99,
            'maxTransactionCount' => 99999,
            'note' => 'Validates SEPA business rules and limits according to SEPA standards.',
        ]);
        $response->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return $response;
    }
}
