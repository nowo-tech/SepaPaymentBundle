<?php

namespace App\Controller;

use Nowo\SepaPaymentBundle\Converter\CccConverter;
use Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator;
use Nowo\SepaPaymentBundle\Generator\IdentifierGenerator;
use Nowo\SepaPaymentBundle\Parser\DirectDebitParser;
use Nowo\SepaPaymentBundle\Parser\RemesaParser;
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
    #[Route('/demo-credit-transfer', name: 'demo_credit_transfer')]
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
        // Set creditor address (will be included in XML)
        $remesaData->setCreditorAddress([
            'street' => '123 Business Street',
            'city' => 'Madrid',
            'postalCode' => '28001',
            'country' => 'ES',
        ]);

        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'GB82WEST12345698765432',
            'John Doe'
        );

        $transaction->setDebtorBic('WESTGB22');
        $transaction->setRemittanceInformation('Invoice 12345');
        // Set debtor address (will be included in XML)
        $transaction->setDebtorAddress([
            'street' => '456 Customer Avenue',
            'city' => 'London',
            'postalCode' => 'SW1A 1AA',
            'country' => 'GB',
        ]);

        $remesaData->addTransaction($transaction);

        try {
            $xml = $generator->generate($remesaData);

            return $generator->createResponse($xml, 'credit-transfer.xml');
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Demo remesa de pago from array (Credit Transfer).
     * Demonstrates generateFromArray() method with camelCase format.
     *
     * @param RemesaGenerator $generator Remesa generator
     * @return Response
     */
    #[Route('/demo-credit-transfer-array', name: 'demo_credit_transfer_array')]
    public function demoRemesaPagoArray(RemesaGenerator $generator): Response
    {
        $data = [
            'reference' => 'MSG-001',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'creditorIban' => 'ES9121000418450200051332',
            'creditorName' => 'My Company Name',
            'requestedExecutionDate' => '2024-01-20',
            'creditorBic' => 'CAIXESBBXXX',
            'batchBooking' => true,
            'transactions' => [
                [
                    'amount' => 100.50,
                    'currency' => 'EUR',
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                    'endToEndId' => 'E2E-001',
                    'debtorBic' => 'WESTGB22',
                    'remittanceInformation' => 'Invoice 12345',
                ],
            ],
        ];

        try {
            $xml = $generator->generateFromArray($data);

            return $generator->createResponse($xml, 'credit-transfer-array.xml');
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Demo remesa de pago with addresses from array (Credit Transfer).
     * Demonstrates address support in generateFromArray().
     *
     * @param RemesaGenerator $generator Remesa generator
     * @return Response
     */
    #[Route('/demo-credit-transfer-with-addresses', name: 'demo_credit_transfer_with_addresses')]
    public function demoRemesaPagoWithAddresses(RemesaGenerator $generator): Response
    {
        // Example with creditor and debtor addresses
        // Addresses will be included in the generated XML
        $data = [
            'reference' => 'MSG-001',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'creditorIban' => 'ES9121000418450200051332',
            'creditorName' => 'My Company Name',
            'requestedExecutionDate' => '2024-01-20',
            'creditorBic' => 'CAIXESBBXXX',
            'batchBooking' => true,
            // Creditor address (will be included in XML)
            'creditorAddress' => [
                'street' => '123 Business Street',
                'city' => 'Madrid',
                'postalCode' => '28001',
                'country' => 'ES',
            ],
            'transactions' => [
                [
                    'amount' => 100.50,
                    'currency' => 'EUR',
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                    'endToEndId' => 'E2E-001',
                    'debtorBic' => 'WESTGB22',
                    'remittanceInformation' => 'Invoice 12345',
                    // Debtor address (will be included in XML)
                    'debtorAddress' => [
                        'street' => '456 Customer Avenue',
                        'city' => 'London',
                        'postalCode' => 'SW1A 1AA',
                        'country' => 'GB',
                    ],
                ],
            ],
        ];

        try {
            $xml = $generator->generateFromArray($data);

            return $generator->createResponse($xml, 'credit-transfer-with-addresses.xml');
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Demo remesa de pago with snake_case format (Credit Transfer).
     * Demonstrates support for snake_case field names.
     *
     * @param RemesaGenerator $generator Remesa generator
     * @return Response
     */
    #[Route('/demo-credit-transfer-snake-case', name: 'demo_credit_transfer_snake_case')]
    public function demoRemesaPagoSnakeCase(RemesaGenerator $generator): Response
    {
        // Example using snake_case format (also supports camelCase)
        $data = [
            'message_id' => 'MSG-001',
            'initiating_party_name' => 'My Company',
            'payment_info_id' => 'PMT-001',
            'creditor_iban' => 'ES9121000418450200051332',
            'creditor_name' => 'My Company Name',
            'requested_execution_date' => '2024-01-20',
            'creditor_bic' => 'CAIXESBBXXX',
            'batch_booking' => true,
            'items' => [
                [
                    'instruction_id' => 'E2E-001',
                    'amount' => 100.50,
                    'currency' => 'EUR',
                    'debtor_iban' => 'GB82WEST12345698765432',
                    'debtor_name' => 'John Doe',
                    'debtor_bic' => 'WESTGB22',
                    'remittance_information' => 'Invoice 12345',
                ],
            ],
        ];

        try {
            $xml = $generator->generateFromArray($data);

            return $generator->createResponse($xml, 'credit-transfer-snake-case.xml');
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
    #[Route('/demo-direct-debit', name: 'demo_direct_debit')]
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

            return $generator->createResponse($xml, 'direct-debit.xml');
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

    /**
     * Demo remesa de cobro with snake_case format (Direct Debit).
     * Demonstrates support for snake_case field names.
     *
     * @param DirectDebitGenerator $generator Direct debit generator
     * @return Response
     */
    #[Route('/demo-direct-debit-snake-case', name: 'demo_direct_debit_snake_case')]
    public function demoRemesaCobroSnakeCase(DirectDebitGenerator $generator): Response
    {
        // Example using snake_case format (also supports camelCase)
        $data = [
            'message_id' => 'PRE2025121614020000001REM000001',
            'initiating_party_name' => 'My Company',
            'payment_name' => 'PMTINF-1',
            'due_date' => '2025-12-18',
            'creditor_name' => 'My Company Name',
            'creditor_iban' => 'ES2931183364320522274646',
            'creditor_bic' => 'BBVAESMM',
            'sequence_type' => 'RCUR',
            'creditor_id' => 'ES654646464646',
            'instrument_code' => 'CORE',
            'items' => [
                [
                    'instruction_id' => 'E2E-001',
                    'amount' => 2500.0,
                    'debtor_iban' => 'ES3330605615396412039906',
                    'debtor_name' => 'John Doe',
                    'debtor_mandate' => 'MANDATE-001',
                    'debtor_mandate_signature_date' => new \DateTime('2025-09-26'),
                    'information' => 'Periodo:26/09/2025 al 26/09/2025 N. Poliza: 2025-00000001-00003 Recibo Cia: rtrtt',
                    'id' => 'rtrtt', // Additional field stored in additionalData
                ],
            ],
        ];

        try {
            $xml = $generator->generateFromArray($data);

            return $generator->createResponse($xml, 'direct-debit-snake-case.xml');
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Demo remesa de cobro with addresses (Direct Debit).
     * Demonstrates address support (stored internally, not in XML).
     *
     * @param DirectDebitGenerator $generator Direct debit generator
     * @return Response
     */
    #[Route('/demo-direct-debit-with-addresses', name: 'demo_direct_debit_with_addresses')]
    public function demoRemesaCobroWithAddresses(DirectDebitGenerator $generator): Response
    {
        // Example with creditor and debtor addresses
        // Note: Addresses are stored internally but NOT included in SEPA XML
        // (see docs/DEPRECATED_FIELDS.md for details)
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
            // Creditor address (stored internally)
            'creditorAddress' => [
                'street' => '123 Business Street',
                'city' => 'Madrid',
                'postalCode' => '28001',
                'country' => 'ES',
            ],
            'transactions' => [
                [
                    'amount' => 100.50,
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                    'debtorMandate' => 'MANDATE-001',
                    'debtorMandateSignDate' => '2024-01-15',
                    'endToEndId' => 'E2E-001',
                    'remittanceInformation' => 'Invoice 12345',
                    // Debtor address (stored internally)
                    'debtorAddress' => [
                        'street' => '456 Customer Avenue',
                        'city' => 'London',
                        'postalCode' => 'SW1A 1AA',
                        'country' => 'GB',
                    ],
                ],
            ],
        ];

        try {
            $xml = $generator->generateFromArray($data);

            return $generator->createResponse($xml, 'direct-debit-with-addresses.xml');
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Demo parsing SEPA Credit Transfer XML.
     * Generates a sample XML and then parses it to demonstrate the parser functionality.
     *
     * @param RemesaGenerator $generator Credit transfer generator
     * @param RemesaParser    $parser    Credit transfer parser
     * @return JsonResponse
     */
    #[Route('/demo-parse-credit-transfer', name: 'demo_parse_credit_transfer')]
    public function demoParseCreditTransfer(RemesaGenerator $generator, RemesaParser $parser): JsonResponse
    {
        try {
            // First, generate a sample XML
            $data = [
                'reference' => 'MSG-PARSE-001',
                'initiatingPartyName' => 'Demo Company',
                'paymentInfoId' => 'PMT-PARSE-001',
                'creditorIban' => 'ES9121000418450200051332',
                'creditorName' => 'Demo Company Name',
                'requestedExecutionDate' => '2024-01-20',
                'creditorBic' => 'CAIXESBBXXX',
                'transactions' => [
                    [
                        'amount' => 150.75,
                        'currency' => 'EUR',
                        'debtorIban' => 'GB82WEST12345698765432',
                        'debtorName' => 'John Doe',
                        'endToEndId' => 'E2E-PARSE-001',
                        'debtorBic' => 'WESTGB22',
                        'remittanceInformation' => 'Demo Invoice 12345',
                    ],
                ],
            ];

            $xml = $generator->generateFromArray($data);

            // Validate XML format
            $isValid = $parser->isValidCreditTransfer($xml);

            // Parse the XML
            $parsedData = $parser->parseCreditTransfer($xml);

            return new JsonResponse([
                'message' => 'Successfully generated and parsed Credit Transfer XML',
                'isValid' => $isValid,
                'generatedXml' => $xml,
                'parsedData' => $parsedData,
            ], 200, [], JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Demo parsing SEPA Direct Debit XML.
     * Generates a sample XML and then parses it to demonstrate the parser functionality.
     *
     * @param DirectDebitGenerator $generator Direct debit generator
     * @param DirectDebitParser    $parser    Direct debit parser
     * @return JsonResponse
     */
    #[Route('/demo-parse-direct-debit', name: 'demo_parse_direct_debit')]
    public function demoParseDirectDebit(DirectDebitGenerator $generator, DirectDebitParser $parser): JsonResponse
    {
        try {
            // First, generate a sample XML with addresses
            $data = [
                'reference' => 'MSG-PARSE-001',
                'bankAccountOwner' => 'Demo Company',
                'paymentInfoId' => 'PMTINF-PARSE-001',
                'dueDate' => '2024-01-20',
                'creditorName' => 'Demo Company Name',
                'creditorIban' => 'ES9121000418450200051332',
                'creditorBic' => 'CAIXESBBXXX',
                'seqType' => 'FRST',
                'creditorId' => 'ES98ZZZ09999999999',
                'localInstrumentCode' => 'CORE',
                'creditorAddress' => [
                    'street' => '123 Demo Street',
                    'city' => 'Madrid',
                    'postalCode' => '28001',
                    'country' => 'ES',
                ],
                'transactions' => [
                    [
                        'amount' => 200.50,
                        'debtorIban' => 'GB82WEST12345698765432',
                        'debtorName' => 'Jane Smith',
                        'debtorMandate' => 'MANDATE-PARSE-001',
                        'debtorMandateSignDate' => '2023-12-01',
                        'endToEndId' => 'E2E-PARSE-001',
                        'debtorBic' => 'WESTGB22',
                        'remittanceInformation' => 'Demo Invoice 67890',
                        'debtorAddress' => [
                            'street' => '456 Demo Avenue',
                            'city' => 'London',
                            'postalCode' => 'SW1A 1AA',
                            'country' => 'GB',
                        ],
                    ],
                ],
            ];

            $xml = $generator->generateFromArray($data);

            // Validate XML format
            $isValid = $parser->isValidDirectDebit($xml);

            // Parse the XML
            $parsedData = $parser->parseDirectDebit($xml);

            return new JsonResponse([
                'message' => 'Successfully generated and parsed Direct Debit XML',
                'isValid' => $isValid,
                'generatedXml' => $xml,
                'parsedData' => $parsedData,
            ], 200, [], JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

