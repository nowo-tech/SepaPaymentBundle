<?php

namespace App\Controller;

use Nowo\SepaPaymentBundle\Converter\CccConverter;
use Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator;
use Nowo\SepaPaymentBundle\Generator\IdentifierGenerator;
use Nowo\SepaPaymentBundle\Parser\DirectDebitParser;
use Nowo\SepaPaymentBundle\Parser\CreditTransferParser;
use Nowo\SepaPaymentBundle\Validator\BicValidator;
use Nowo\SepaPaymentBundle\Validator\CreditCardValidator;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Nowo\SepaPaymentBundle\Validator\SepaStringSanitizer;
use Nowo\SepaPaymentBundle\Validator\SepaCountryValidator;
use Nowo\SepaPaymentBundle\Validator\SepaBusinessRulesValidator;
use Nowo\SepaPaymentBundle\Validator\CachedIbanValidator;
use Nowo\SepaPaymentBundle\Validator\CachedBicValidator;
use Nowo\SepaPaymentBundle\Cache\ValidationCache;
use Nowo\SepaPaymentBundle\Exporter\ExportService;
use Nowo\SepaPaymentBundle\Model\Mandate\Mandate;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\CreditTransferData;
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\Transaction;
use Nowo\SepaPaymentBundle\Generator\RemesaGenerator;
use Nowo\SepaPaymentBundle\Parser\RemesaParser;
use Nowo\SepaPaymentBundle\Model\Remesa\RemesaData;
use Nowo\SepaPaymentBundle\Model\Remesa\Transaction as RemesaTransaction;
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
     * @param CreditTransferGenerator $generator Credit transfer generator
     * @param IbanValidator           $validator IBAN validator
     * @return Response
     */
    #[Route('/demo-credit-transfer', name: 'demo_credit_transfer')]
    public function demoRemesaPago(CreditTransferGenerator $generator, IbanValidator $validator): Response
    {
        $creditTransferData = new CreditTransferData(
            'MSG-001',
            new \DateTime('2024-01-15 10:00:00'),
            'My Company',
            'PMT-001',
            'ES9121000418450200051332',
            'My Company Name',
            new \DateTime('2024-01-20')
        );

        $creditTransferData->setCreditorBic('CAIXESBBXXX');
        $creditTransferData->setBatchBooking(true);
        // Set creditor address (will be included in XML)
        $creditTransferData->setCreditorAddress([
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

        $creditTransferData->addTransaction($transaction);

        try {
            $xml = $generator->generate($creditTransferData);

            return $generator->createResponse($xml, 'credit-transfer.xml');
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Demo remesa de pago from array (Credit Transfer).
     * Demonstrates generateFromArray() method with camelCase format.
     *
     * @param CreditTransferGenerator $generator Credit transfer generator
     * @return Response
     */
    #[Route('/demo-credit-transfer-array', name: 'demo_credit_transfer_array')]
    public function demoRemesaPagoArray(CreditTransferGenerator $generator): Response
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
     * @param CreditTransferGenerator $generator Credit transfer generator
     * @return Response
     */
    #[Route('/demo-credit-transfer-with-addresses', name: 'demo_credit_transfer_with_addresses')]
    public function demoRemesaPagoWithAddresses(CreditTransferGenerator $generator): Response
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
     * @param CreditTransferGenerator $generator Credit transfer generator
     * @return Response
     */
    #[Route('/demo-credit-transfer-snake-case', name: 'demo_credit_transfer_snake_case')]
    public function demoRemesaPagoSnakeCase(CreditTransferGenerator $generator): Response
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
     * @param CreditTransferGenerator $generator Credit transfer generator
     * @param CreditTransferParser     $parser    Credit transfer parser
     * @return JsonResponse
     */
    #[Route('/demo-parse-credit-transfer', name: 'demo_parse_credit_transfer')]
    public function demoParseCreditTransfer(CreditTransferGenerator $generator, CreditTransferParser $parser): JsonResponse
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
                'creditorIban' => 'ES9121000418450200051332',
                'creditorName' => 'Deprecated Parser Company',
                'requestedExecutionDate' => '2024-01-20',
                'creditorBic' => 'CAIXESBBXXX',
                'transactions' => [
                    [
                        'amount' => 175.25,
                        'currency' => 'EUR',
                        'debtorIban' => 'GB82WEST12345698765432',
                        'debtorName' => 'John Doe (Deprecated Parser)',
                        'endToEndId' => 'E2E-PARSE-DEPRECATED-001',
                        'debtorBic' => 'WESTGB22',
                        'remittanceInformation' => 'Invoice parsed with deprecated RemesaParser',
                    ],
                ],
            ];

            $xml = $generator->generateFromArray($data);

            // Using deprecated RemesaParser (will show deprecation warning but works)
            $isValid = $parser->isValidCreditTransfer($xml);
            $parsedData = $parser->parseCreditTransfer($xml);

            return new JsonResponse([
                'message' => 'Successfully parsed XML using deprecated RemesaParser (backward compatibility)',
                'isValid' => $isValid,
                'generatedXml' => $xml,
                'parsedData' => $parsedData,
                'note' => 'This endpoint uses deprecated RemesaParser. It still works but shows deprecation warnings. Use CreditTransferParser instead.',
            ], 200, [], JSON_PRETTY_PRINT);
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
            'creditorIban' => 'ES9121000418450200051332',
            'creditorName' => 'Comparison Company',
            'requestedExecutionDate' => '2024-01-20',
            'creditorBic' => 'CAIXESBBXXX',
            'transactions' => [
                [
                    'amount' => 100.00,
                    'currency' => 'EUR',
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                    'endToEndId' => 'E2E-COMPARISON-001',
                    'debtorBic' => 'WESTGB22',
                    'remittanceInformation' => 'Comparison invoice',
                ],
            ],
        ];

        try {
            // Generate XML using deprecated RemesaGenerator
            $xmlDeprecated = $remesaGenerator->generateFromArray($data);

            // Generate XML using new CreditTransferGenerator
            $xmlNew = $creditTransferGenerator->generateFromArray($data);

            // Compare results
            $areIdentical = $xmlDeprecated === $xmlNew;

            return new JsonResponse([
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
            ], 200, [], JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Demo export Credit Transfer to JSON.
     *
     * @param CreditTransferGenerator $generator Credit transfer generator
     * @param CreditTransferParser    $parser    Credit transfer parser
     * @param ExportService           $exporter  Export service
     * @return JsonResponse
     */
    #[Route('/demo-export-credit-transfer-json', name: 'demo_export_credit_transfer_json')]
    public function demoExportCreditTransferJson(CreditTransferGenerator $generator, CreditTransferParser $parser, ExportService $exporter): JsonResponse
    {
        try {
            // Generate XML
            $data = [
                'reference' => 'MSG-EXPORT-001',
                'initiatingPartyName' => 'Export Demo Company',
                'paymentInfoId' => 'PMT-EXPORT-001',
                'creditorIban' => 'ES9121000418450200051332',
                'creditorName' => 'Export Demo Company Name',
                'requestedExecutionDate' => '2024-01-20',
                'creditorBic' => 'CAIXESBBXXX',
                'transactions' => [
                    [
                        'amount' => 150.75,
                        'currency' => 'EUR',
                        'debtorIban' => 'GB82WEST12345698765432',
                        'debtorName' => 'John Doe',
                        'endToEndId' => 'E2E-EXPORT-001',
                        'debtorBic' => 'WESTGB22',
                        'remittanceInformation' => 'Export Demo Invoice',
                    ],
                ],
            ];

            $xml = $generator->generateFromArray($data);
            $parsedData = $parser->parseCreditTransfer($xml);

            // Export to JSON
            $json = $exporter->exportCreditTransferToJson($parsedData, true);

            return new JsonResponse([
                'message' => 'Successfully exported Credit Transfer to JSON',
                'originalData' => $data,
                'parsedData' => $parsedData,
                'json' => $json,
                'jsonDecoded' => json_decode($json, true),
            ], 200, [], JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Demo export Credit Transfer to CSV.
     *
     * @param CreditTransferGenerator $generator Credit transfer generator
     * @param CreditTransferParser    $parser    Credit transfer parser
     * @param ExportService           $exporter  Export service
     * @return Response
     */
    #[Route('/demo-export-credit-transfer-csv', name: 'demo_export_credit_transfer_csv')]
    public function demoExportCreditTransferCsv(CreditTransferGenerator $generator, CreditTransferParser $parser, ExportService $exporter): Response
    {
        try {
            // Generate XML
            $data = [
                'reference' => 'MSG-EXPORT-CSV-001',
                'initiatingPartyName' => 'CSV Export Demo',
                'paymentInfoId' => 'PMT-EXPORT-CSV-001',
                'creditorIban' => 'ES9121000418450200051332',
                'creditorName' => 'CSV Export Company',
                'requestedExecutionDate' => '2024-01-20',
                'creditorBic' => 'CAIXESBBXXX',
                'transactions' => [
                    [
                        'amount' => 100.50,
                        'currency' => 'EUR',
                        'debtorIban' => 'GB82WEST12345698765432',
                        'debtorName' => 'John Doe',
                        'endToEndId' => 'E2E-EXPORT-CSV-001',
                        'debtorBic' => 'WESTGB22',
                        'remittanceInformation' => 'CSV Export Invoice',
                    ],
                    [
                        'amount' => 200.75,
                        'currency' => 'EUR',
                        'debtorIban' => 'FR1420041010050500013M02606',
                        'debtorName' => 'Jane Smith',
                        'endToEndId' => 'E2E-EXPORT-CSV-002',
                        'debtorBic' => 'BNPAFRPPXXX',
                        'remittanceInformation' => 'CSV Export Invoice 2',
                    ],
                ],
            ];

            $xml = $generator->generateFromArray($data);
            $parsedData = $parser->parseCreditTransfer($xml);

            // Export to CSV
            $csv = $exporter->exportCreditTransferToCsv($parsedData);

            $response = new Response($csv);
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="credit-transfer-export.csv"');

            return $response;
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Demo export Direct Debit to JSON.
     *
     * @param DirectDebitGenerator $generator Direct debit generator
     * @param DirectDebitParser     $parser    Direct debit parser
     * @param ExportService         $exporter Export service
     * @return JsonResponse
     */
    #[Route('/demo-export-direct-debit-json', name: 'demo_export_direct_debit_json')]
    public function demoExportDirectDebitJson(DirectDebitGenerator $generator, DirectDebitParser $parser, ExportService $exporter): JsonResponse
    {
        try {
            // Generate XML
            $data = [
                'reference' => 'MSG-EXPORT-DD-001',
                'initiatingPartyName' => 'Direct Debit Export Demo',
                'paymentInfoId' => 'PMT-EXPORT-DD-001',
                'creditorIban' => 'ES9121000418450200051332',
                'creditorName' => 'Direct Debit Export Company',
                'dueDate' => '2024-01-20',
                'creditorBic' => 'CAIXESBBXXX',
                'creditorId' => 'ES98ZZZ09999999999',
                'seqType' => 'FRST',
                'localInstrumentCode' => 'CORE',
                'transactions' => [
                    [
                        'amount' => 150.50,
                        'currency' => 'EUR',
                        'debtorIban' => 'GB82WEST12345698765432',
                        'debtorName' => 'John Doe',
                        'endToEndId' => 'E2E-EXPORT-DD-001',
                        'debtorBic' => 'WESTGB22',
                        'debtorMandate' => 'MANDATE-EXPORT-001',
                        'debtorMandateSignDate' => '2023-12-01',
                        'remittanceInformation' => 'Direct Debit Export Invoice',
                    ],
                ],
            ];

            $xml = $generator->generateFromArray($data);
            $parsedData = $parser->parseDirectDebit($xml);

            // Export to JSON
            $json = $exporter->exportDirectDebitToJson($parsedData, true);

            return new JsonResponse([
                'message' => 'Successfully exported Direct Debit to JSON',
                'originalData' => $data,
                'parsedData' => $parsedData,
                'json' => $json,
                'jsonDecoded' => json_decode($json, true),
            ], 200, [], JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Demo export Direct Debit to CSV.
     *
     * @param DirectDebitGenerator $generator Direct debit generator
     * @param DirectDebitParser     $parser    Direct debit parser
     * @param ExportService         $exporter Export service
     * @return Response
     */
    #[Route('/demo-export-direct-debit-csv', name: 'demo_export_direct_debit_csv')]
    public function demoExportDirectDebitCsv(DirectDebitGenerator $generator, DirectDebitParser $parser, ExportService $exporter): Response
    {
        try {
            // Generate XML
            $data = [
                'reference' => 'MSG-EXPORT-DD-CSV-001',
                'initiatingPartyName' => 'Direct Debit CSV Export',
                'paymentInfoId' => 'PMT-EXPORT-DD-CSV-001',
                'creditorIban' => 'ES9121000418450200051332',
                'creditorName' => 'Direct Debit CSV Company',
                'dueDate' => '2024-01-20',
                'creditorBic' => 'CAIXESBBXXX',
                'creditorId' => 'ES98ZZZ09999999999',
                'seqType' => 'FRST',
                'localInstrumentCode' => 'CORE',
                'transactions' => [
                    [
                        'amount' => 100.50,
                        'currency' => 'EUR',
                        'debtorIban' => 'GB82WEST12345698765432',
                        'debtorName' => 'John Doe',
                        'endToEndId' => 'E2E-EXPORT-DD-CSV-001',
                        'debtorBic' => 'WESTGB22',
                        'debtorMandate' => 'MANDATE-EXPORT-CSV-001',
                        'debtorMandateSignDate' => '2023-12-01',
                        'remittanceInformation' => 'Direct Debit CSV Invoice',
                    ],
                ],
            ];

            $xml = $generator->generateFromArray($data);
            $parsedData = $parser->parseDirectDebit($xml);

            // Export to CSV
            $csv = $exporter->exportDirectDebitToCsv($parsedData);

            $response = new Response($csv);
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="direct-debit-export.csv"');

            return $response;
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Demo import from JSON.
     *
     * @param ExportService $exporter Export service
     * @return JsonResponse
     */
    #[Route('/demo-import-from-json', name: 'demo_import_from_json')]
    public function demoImportFromJson(ExportService $exporter): JsonResponse
    {
        $json = '{
            "messageId": "MSG-IMPORT-001",
            "creationDate": "2024-01-15T10:00:00",
            "initiatingPartyName": "Import Demo Company",
            "paymentInfoId": "PMT-IMPORT-001",
            "creditorIban": "ES9121000418450200051332",
            "creditorName": "Import Demo Company Name",
            "requestedExecutionDate": "2024-01-20",
            "creditorBic": "CAIXESBBXXX",
            "transactions": [
                {
                    "endToEndId": "E2E-IMPORT-001",
                    "amount": 250.00,
                    "currency": "EUR",
                    "debtorIban": "GB82WEST12345698765432",
                    "debtorName": "John Doe",
                    "debtorBic": "WESTGB22",
                    "remittanceInformation": "Imported from JSON"
                }
            ]
        }';

        try {
            $data = $exporter->importCreditTransferFromJson($json);

            return new JsonResponse([
                'message' => 'Successfully imported Credit Transfer from JSON',
                'importedData' => $data,
                'note' => 'This data can now be used with CreditTransferGenerator::generateFromArray()',
            ], 200, [], JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

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

        return new JsonResponse([
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
        ], 200, [], JSON_PRETTY_PRINT);
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

        return new JsonResponse([
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
        ], 200, [], JSON_PRETTY_PRINT);
    }
}

