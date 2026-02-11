<?php

namespace App\Controller;

use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Generator\RemesaGenerator;
use Nowo\SepaPaymentBundle\Model\Remesa\RemesaData;
use Nowo\SepaPaymentBundle\Model\Remesa\Transaction as RemesaTransaction;
use Nowo\SepaPaymentBundle\Parser\RemesaParser;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DeprecatedController extends AbstractController
{
    /**
     * Demo deprecated RemesaGenerator (shows backward compatibility).
     * This endpoint demonstrates that deprecated classes still work.
     *
     * @param RemesaGenerator $generator Deprecated remesa generator
     * @param IbanValidator   $validator IBAN validator
     * @return Response
     */
    #[Route('/demo-remesa-generator-deprecated', name: 'demo_remesa_generator_deprecated')]
    public function demoRemesaGeneratorDeprecated(RemesaGenerator $generator, IbanValidator $validator): Response
    {
        // Using deprecated RemesaData class
        $remesaData = new RemesaData(
            'MSG-DEPRECATED-001',
            new \DateTime(),
            'Deprecated Demo Company',
            'PMT-DEPRECATED-001',
            'ES9121000418450200051332',
            'Deprecated Demo Company Name',
            new \DateTime('tomorrow')
        );
        $remesaData->setCreditorBic('CAIXESBBXXX');
        $remesaData->setBatchBooking(true);

        // Using deprecated Remesa\Transaction class
        $transaction = new RemesaTransaction(
            'E2E-DEPRECATED-001',
            200.50,
            'EUR',
            'GB82WEST12345698765432',
            'John Doe (Deprecated)'
        );
        $transaction->setDebtorBic('WESTGB22');
        $transaction->setRemittanceInformation('Invoice using deprecated classes');
        $remesaData->addTransaction($transaction);

        try {
            // Using deprecated RemesaGenerator (will show deprecation warning but works)
            $xml = $generator->generate($remesaData);

            return $generator->createResponse($xml, 'remesa-deprecated.xml');
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Demo deprecated RemesaGenerator with generateFromArray (shows backward compatibility).
     *
     * @param RemesaGenerator $generator Deprecated remesa generator
     * @return Response
     */
    #[Route('/demo-remesa-generator-array-deprecated', name: 'demo_remesa_generator_array_deprecated')]
    public function demoRemesaGeneratorArrayDeprecated(RemesaGenerator $generator): Response
    {
        // Using deprecated RemesaGenerator with array format
        $data = [
            'reference' => 'MSG-DEPRECATED-ARRAY-001',
            'initiatingPartyName' => 'Deprecated Array Demo',
            'paymentInfoId' => 'PMT-DEPRECATED-ARRAY-001',
            'creditorIban' => 'ES9121000418450200051332',
            'creditorName' => 'Deprecated Array Company',
            'requestedExecutionDate' => '2024-01-20',
            'creditorBic' => 'CAIXESBBXXX',
            'transactions' => [
                [
                    'amount' => 150.75,
                    'currency' => 'EUR',
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe (Deprecated Array)',
                    'endToEndId' => 'E2E-DEPRECATED-ARRAY-001',
                    'debtorBic' => 'WESTGB22',
                    'remittanceInformation' => 'Invoice using deprecated RemesaGenerator',
                ],
            ],
        ];

        try {
            // Using deprecated RemesaGenerator (will show deprecation warning but works)
            $xml = $generator->generateFromArray($data);

            return $generator->createResponse($xml, 'remesa-array-deprecated.xml');
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Demo deprecated RemesaParser (shows backward compatibility).
     *
     * @param CreditTransferGenerator $generator Credit transfer generator (to create sample XML)
     * @param RemesaParser            $parser    Deprecated remesa parser
     * @return JsonResponse
     */
    #[Route('/demo-remesa-parser-deprecated', name: 'demo_remesa_parser_deprecated')]
    public function demoRemesaParserDeprecated(CreditTransferGenerator $generator, RemesaParser $parser): JsonResponse
    {
        try {
            // First, generate a sample XML using new generator
            $data = [
                'reference' => 'MSG-PARSE-DEPRECATED-001',
                'initiatingPartyName' => 'Deprecated Parser Demo',
                'paymentInfoId' => 'PMT-PARSE-DEPRECATED-001',
                // Debtor data (company that PAYS)
                'debtorIban' => 'ES9121000418450200051332',
                'debtorName' => 'Deprecated Parser Company',
                'requestedExecutionDate' => '2024-01-20',
                'debtorBic' => 'CAIXESBBXXX',
                'transactions' => [
                    [
                        'amount' => 175.25,
                        'currency' => 'EUR',
                        'creditorIban' => 'GB82WEST12345698765432',
                        'creditorName' => 'John Doe (Deprecated Parser)',
                        'endToEndId' => 'E2E-PARSE-DEPRECATED-001',
                        'creditorBic' => 'WESTGB22',
                        'remittanceInformation' => 'Invoice parsed with deprecated RemesaParser',
                    ],
                ],
            ];

            $xml = $generator->generateFromArray($data);

            // Using deprecated RemesaParser (will show deprecation warning but works)
            $isValid = $parser->isValidCreditTransfer($xml);
            $parsedData = $parser->parseCreditTransfer($xml);

            $response = new JsonResponse([
                'message' => 'Successfully parsed XML using deprecated RemesaParser (backward compatibility)',
                'isValid' => $isValid,
                'generatedXml' => $xml,
                'parsedData' => $parsedData,
                'note' => 'This endpoint uses deprecated RemesaParser. It still works but shows deprecation warnings. Use CreditTransferParser instead.',
            ]);
            $response->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            return $response;
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Demo comparison: deprecated vs new classes side by side.
     * Shows that both work identically.
     *
     * @param RemesaGenerator         $remesaGenerator         Deprecated generator
     * @param CreditTransferGenerator $creditTransferGenerator New generator
     * @return JsonResponse
     */
    #[Route('/demo-comparison-deprecated-vs-new', name: 'demo_comparison_deprecated_vs_new')]
    public function demoComparisonDeprecatedVsNew(RemesaGenerator $remesaGenerator, CreditTransferGenerator $creditTransferGenerator): JsonResponse
    {
        $data = [
            'reference' => 'MSG-COMPARISON-001',
            'initiatingPartyName' => 'Comparison Demo',
            'paymentInfoId' => 'PMT-COMPARISON-001',
            // Debtor data (company that PAYS)
            'debtorIban' => 'ES9121000418450200051332',
            'debtorName' => 'Comparison Company',
            'requestedExecutionDate' => '2024-01-20',
            'debtorBic' => 'CAIXESBBXXX',
            'transactions' => [
                [
                    'amount' => 100.00,
                    'currency' => 'EUR',
                    'creditorIban' => 'GB82WEST12345698765432',
                    'creditorName' => 'John Doe',
                    'endToEndId' => 'E2E-COMPARISON-001',
                    'creditorBic' => 'WESTGB22',
                    'remittanceInformation' => 'Comparison invoice',
                ],
            ],
        ];

        try {
            // Generate XML using deprecated RemesaGenerator (uses debtor* in arrays)
            $dataForRemesa = $data;
            $dataForRemesa['transactions'][0]['debtorIban'] = $data['transactions'][0]['creditorIban'];
            $dataForRemesa['transactions'][0]['debtorName'] = $data['transactions'][0]['creditorName'];
            $dataForRemesa['transactions'][0]['debtorBic'] = $data['transactions'][0]['creditorBic'];
            unset($dataForRemesa['transactions'][0]['creditorIban'], $dataForRemesa['transactions'][0]['creditorName'], $dataForRemesa['transactions'][0]['creditorBic']);
            $xmlDeprecated = $remesaGenerator->generateFromArray($dataForRemesa);

            // Generate XML using new CreditTransferGenerator (uses debtor* in arrays for level 1, creditor* for transactions)
            $xmlNew = $creditTransferGenerator->generateFromArray($data);

            // Compare results
            $areIdentical = $xmlDeprecated === $xmlNew;

            $response = new JsonResponse([
                'message' => 'Comparison between deprecated and new classes',
                'deprecated' => [
                    'class' => 'RemesaGenerator',
                    'status' => 'deprecated since 1.1.0, will be removed in 2.0.0',
                    'xmlLength' => strlen($xmlDeprecated),
                    'works' => true,
                ],
                'new' => [
                    'class' => 'CreditTransferGenerator',
                    'status' => 'current, recommended',
                    'xmlLength' => strlen($xmlNew),
                    'works' => true,
                ],
                'comparison' => [
                    'xmlsAreIdentical' => $areIdentical,
                    'note' => 'Both generators produce identical XML. The deprecated class still works but shows deprecation warnings.',
                ],
                'recommendation' => 'Migrate to CreditTransferGenerator before upgrading to v2.0.0',
            ]);
            $response->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            return $response;
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
