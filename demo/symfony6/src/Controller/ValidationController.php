<?php

namespace App\Controller;

use Nowo\SepaPaymentBundle\Converter\CccConverter;
use Nowo\SepaPaymentBundle\Generator\IdentifierGenerator;
use Nowo\SepaPaymentBundle\Validator\BicValidator;
use Nowo\SepaPaymentBundle\Validator\CreditCardValidator;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Nowo\SepaPaymentBundle\Validator\SepaCreditorIdentifierValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ValidationController extends AbstractController
{
    /**
     * Validate IBAN endpoint.
     *
     * @param Request       $request  Request object
     * @param IbanValidator $validator IBAN validator
     * @return JsonResponse
     */
    #[Route('/validate-iban', name: 'demo_validate_iban')]
    public function validateIban(Request $request, IbanValidator $validator): JsonResponse
    {
        $iban = $request->query->get('iban', 'ES9121000418450200051332');

        return new JsonResponse([
            'iban' => $iban,
            'isValid' => $validator->isValid($iban),
            'normalized' => $validator->normalize($iban),
            'formatted' => $validator->format($iban),
            'countryCode' => $validator->getCountryCode($iban),
            'checkDigits' => $validator->getCheckDigits($iban),
            'bban' => $validator->getBban($iban),
        ]);
    }

    /**
     * Validate BIC endpoint.
     *
     * @param Request      $request  Request object
     * @param BicValidator $validator BIC validator
     * @return JsonResponse
     */
    #[Route('/validate-bic', name: 'demo_validate_bic')]
    public function validateBic(Request $request, BicValidator $validator): JsonResponse
    {
        $bic = $request->query->get('bic', 'ESPBESMM');

        return new JsonResponse([
            'bic' => $bic,
            'isValid' => $validator->isValid($bic),
            'normalized' => $validator->normalize($bic),
            'bankCode' => $validator->getBankCode($bic),
            'countryCode' => $validator->getCountryCode($bic),
            'locationCode' => $validator->getLocationCode($bic),
            'branchCode' => $validator->getBranchCode($bic),
        ]);
    }

    /**
     * Validate credit card endpoint.
     *
     * @param Request            $request  Request object
     * @param CreditCardValidator $validator Credit card validator
     * @return JsonResponse
     */
    #[Route('/validate-credit-card', name: 'demo_validate_credit_card')]
    public function validateCreditCard(Request $request, CreditCardValidator $validator): JsonResponse
    {
        $cardNumber = $request->query->get('card', '4532015112830366');

        return new JsonResponse([
            'cardNumber' => $cardNumber,
            'isValid' => $validator->isValid($cardNumber),
            'normalized' => $validator->normalize($cardNumber),
            'formatted' => $validator->format($cardNumber),
            'masked' => $validator->mask($cardNumber),
            'cardType' => $validator->getCardType($cardNumber),
            'bin' => $validator->getBin($cardNumber),
            'lastFour' => $validator->getLastFour($cardNumber),
        ]);
    }

    /**
     * Convert CCC to IBAN endpoint.
     *
     * @param Request      $request  Request object
     * @param CccConverter $converter CCC converter
     * @return JsonResponse
     */
    #[Route('/convert-ccc', name: 'demo_convert_ccc')]
    public function convertCcc(Request $request, CccConverter $converter): JsonResponse
    {
        $ccc = $request->query->get('ccc', '21000418450200051332');

        try {
            $iban = $converter->cccToIban($ccc);

            return new JsonResponse([
                'ccc' => $ccc,
                'iban' => $iban,
                'isValidCcc' => $converter->isValidCcc($ccc),
                'bankCode' => $converter->getBankCode($ccc),
                'branchCode' => $converter->getBranchCode($ccc),
                'accountNumber' => $converter->getAccountNumber($ccc),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Validate SEPA Creditor Identifier endpoint.
     *
     * @param Request                         $request  Request object
     * @param SepaCreditorIdentifierValidator $validator SEPA Creditor Identifier validator
     * @return JsonResponse
     */
    #[Route('/validate-sepa-creditor-identifier', name: 'demo_validate_sepa_creditor_identifier')]
    public function validateSepaCreditorIdentifier(Request $request, SepaCreditorIdentifierValidator $validator): JsonResponse
    {
        $creditorId = $request->query->get('creditor_id', 'ES97ZZZM12345678');

        return new JsonResponse([
            'creditorId' => $creditorId,
            'isValid' => $validator->isValid($creditorId),
            'normalized' => $validator->normalize($creditorId),
            'countryCode' => $validator->getCountryCode($creditorId),
            'nationalIdentifier' => $validator->getNationalIdentifier($creditorId),
            'isValidSpanishNifFormat' => $validator->isValidSpanishNifFormat($validator->getNationalIdentifier($creditorId)),
        ]);
    }

    /**
     * Generate identifier endpoint.
     *
     * @param IdentifierGenerator $generator Identifier generator
     * @return JsonResponse
     */
    #[Route('/generate-identifier', name: 'demo_generate_identifier')]
    public function generateIdentifier(IdentifierGenerator $generator): JsonResponse
    {
        return new JsonResponse([
            'messageId' => $generator->generateMessageId(),
            'paymentInfoId' => $generator->generatePaymentInfoId(),
            'endToEndId' => $generator->generateEndToEndId(),
            'mandateId' => $generator->generateMandateId(),
            'customId' => $generator->generateCustomId('CUSTOM'),
        ]);
    }
}
