<?php

namespace App\Controller;

use Nowo\SepaPaymentBundle\Converter\CccConverter;
use Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator;
use Nowo\SepaPaymentBundle\Generator\IdentifierGenerator;
use Nowo\SepaPaymentBundle\Validator\BicValidator;
use Nowo\SepaPaymentBundle\Validator\CreditCardValidator;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Nowo\SepaPaymentBundle\Model\Mandate\Mandate;
use Nowo\SepaPaymentBundle\Model\Remesa\RemesaData;
use Nowo\SepaPaymentBundle\Generator\RemesaGenerator;
use Nowo\SepaPaymentBundle\Model\Remesa\Transaction;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DemoController extends AbstractController
{
    /**
     * Demo page showing bundle features.
     *
     * @return Response
     */
    #[Route('/', name: 'demo_index')]
    public function index(): Response
    {
        return $this->render('demo/index.html.twig');
    }

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
     * Demo mandate creation.
     *
     * @return JsonResponse
     */
    #[Route('/demo-mandate', name: 'demo_mandate')]
    public function demoMandate(): JsonResponse
    {
        $mandate = new Mandate(
            'MANDATE-001',
            new \DateTime('2024-01-15'),
            'ES9121000418450200051332',
            'John Doe',
            'CORE',
            'FRST'
        );

        $mandate->setDebtorBic('CAIXESBBXXX');
        $mandate->setSequenceType('RCUR');
        $mandate->setActive(true);

        return new JsonResponse([
            'mandateId' => $mandate->getMandateId(),
            'signatureDate' => $mandate->getSignatureDate()->format('Y-m-d'),
            'debtorIban' => $mandate->getDebtorIban(),
            'debtorBic' => $mandate->getDebtorBic(),
            'debtorName' => $mandate->getDebtorName(),
            'type' => $mandate->getType(),
            'sequenceType' => $mandate->getSequenceType(),
            'active' => $mandate->isActive(),
        ]);
    }

    /**
     * Demo remesa de pago (Credit Transfer).
     *
     * @param RemesaGenerator $generator Remesa generator
     * @param IbanValidator   $validator IBAN validator
     * @return Response
     */
    #[Route('/demo-remesa-pago', name: 'demo_remesa_pago')]
    public function demoRemesaPago(RemesaGenerator $generator, IbanValidator $validator): Response
    {
        $remesaData = new RemesaData(
            'MSG-001',
            new \DateTime('2024-01-15 10:00:00'),
            'My Company',
            'PMT-001',
            'ES9121000418450200051332',
            'My Company Name',
            new \DateTime('2024-01-20')
        );

        $remesaData->setCreditorBic('CAIXESBBXXX');
        $remesaData->setBatchBooking(true);

        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'GB82WEST12345698765432',
            'John Doe'
        );

        $transaction->setDebtorBic('WESTGB22');
        $transaction->setRemittanceInformation('Invoice 12345');

        $remesaData->addTransaction($transaction);

        try {
            $xml = $generator->generate($remesaData);

            return new Response($xml, 200, [
                'Content-Type' => 'application/xml',
                'Content-Disposition' => 'attachment; filename="remesa-pago.xml"',
            ]);
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Demo remesa de cobro (Direct Debit).
     *
     * @param DirectDebitGenerator $generator Direct debit generator
     * @return Response
     */
    #[Route('/demo-remesa-cobro', name: 'demo_remesa_cobro')]
    public function demoRemesaCobro(DirectDebitGenerator $generator): Response
    {
        $data = [
            'reference' => 'MSG-001',
            'bankAccountOwner' => 'My Company',
            'paymentInfoId' => 'PMTINF-1',
            'dueDate' => '2024-01-20',
            'creditorName' => 'My Company Name',
            'creditorIban' => 'ES9121000418450200051332',
            'creditorBic' => 'CAIXESBBXXX',
            'seqType' => 'RCUR',
            'creditorId' => 'ES98ZZZ09999999999',
            'localInstrumentCode' => 'CORE',
            'transactions' => [
                [
                    'amount' => 100.50,
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                    'debtorMandate' => 'MANDATE-001',
                    'debtorMandateSignDate' => '2024-01-15',
                    'endToEndId' => 'E2E-001',
                    'remittanceInformation' => 'Invoice 12345',
                ],
            ],
        ];

        try {
            $xml = $generator->generateFromArray($data);

            return new Response($xml, 200, [
                'Content-Type' => 'application/xml',
                'Content-Disposition' => 'attachment; filename="remesa-cobro.xml"',
            ]);
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
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

