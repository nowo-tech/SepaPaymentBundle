<?php

declare(strict_types=1);

namespace App\Controller;

use DateTime;
use Exception;
use Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator;
use Nowo\SepaPaymentBundle\Parser\DirectDebitParser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_UNICODE;

class DirectDebitController extends AbstractController
{
    /**
     * Demo remesa de cobro (Direct Debit).
     *
     * @param DirectDebitGenerator $generator Direct debit generator
     */
    #[Route('/demo-direct-debit', name: 'demo_direct_debit')]
    public function demoRemesaCobro(DirectDebitGenerator $generator): Response
    {
        $data = [
            'reference'           => 'MSG-001',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMTINF-1',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'creditorBic'         => 'CAIXESBBXXX',
            'seqType'             => 'RCUR',
            'creditorId'          => 'ES98ZZZ09999999999',
            'localInstrumentCode' => 'CORE',
            'transactions'        => [
                [
                    'amount'                => 100.50,
                    'debtorIban'            => 'GB82WEST12345698765432',
                    'debtorName'            => 'John Doe',
                    'debtorMandate'         => 'MANDATE-001',
                    'debtorMandateSignDate' => '2024-01-15',
                    'endToEndId'            => 'E2E-001',
                    'remittanceInformation' => 'Invoice 12345',
                ],
            ],
        ];

        try {
            $xml = $generator->generateFromArray($data);

            return $generator->createResponse($xml, 'direct-debit.xml');
        } catch (Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Demo remesa de cobro with snake_case format (Direct Debit).
     * Demonstrates support for snake_case field names.
     *
     * @param DirectDebitGenerator $generator Direct debit generator
     */
    #[Route('/demo-direct-debit-snake-case', name: 'demo_direct_debit_snake_case')]
    public function demoRemesaCobroSnakeCase(DirectDebitGenerator $generator): Response
    {
        // Example using snake_case format (also supports camelCase)
        $data = [
            'message_id'            => 'PRE2025121614020000001REM000001',
            'initiating_party_name' => 'My Company',
            'payment_name'          => 'PMTINF-1',
            'due_date'              => '2025-12-18',
            'creditor_name'         => 'My Company Name',
            'creditor_iban'         => 'ES2931183364320522274646',
            'creditor_bic'          => 'BBVAESMM',
            'sequence_type'         => 'RCUR',
            'creditor_id'           => 'ES654646464646',
            'instrument_code'       => 'CORE',
            'items'                 => [
                [
                    'instruction_id'                => 'E2E-001',
                    'amount'                        => 2500.0,
                    'debtor_iban'                   => 'ES3330605615396412039906',
                    'debtor_name'                   => 'John Doe',
                    'debtor_mandate'                => 'MANDATE-001',
                    'debtor_mandate_signature_date' => new DateTime('2025-09-26'),
                    'information'                   => 'Periodo:26/09/2025 al 26/09/2025 N. Poliza: 2025-00000001-00003 Recibo Cia: rtrtt',
                    'id'                            => 'rtrtt', // Additional field stored in additionalData
                ],
            ],
        ];

        try {
            $xml = $generator->generateFromArray($data);

            return $generator->createResponse($xml, 'direct-debit-snake-case.xml');
        } catch (Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Demo remesa de cobro with addresses (Direct Debit).
     * Demonstrates address support (stored internally, not in XML).
     *
     * @param DirectDebitGenerator $generator Direct debit generator
     */
    #[Route('/demo-direct-debit-with-addresses', name: 'demo_direct_debit_with_addresses')]
    public function demoRemesaCobroWithAddresses(DirectDebitGenerator $generator): Response
    {
        // Example with creditor and debtor addresses
        // Note: Addresses are stored internally but NOT included in SEPA XML
        // (see docs/DEPRECATED_FIELDS.md for details)
        $data = [
            'reference'           => 'MSG-001',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMTINF-1',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'creditorBic'         => 'CAIXESBBXXX',
            'seqType'             => 'RCUR',
            'creditorId'          => 'ES98ZZZ09999999999',
            'localInstrumentCode' => 'CORE',
            // Creditor address (stored internally)
            'creditorAddress' => [
                'street'     => '123 Business Street',
                'city'       => 'Madrid',
                'postalCode' => '28001',
                'country'    => 'ES',
            ],
            'transactions' => [
                [
                    'amount'                => 100.50,
                    'debtorIban'            => 'GB82WEST12345698765432',
                    'debtorName'            => 'John Doe',
                    'debtorMandate'         => 'MANDATE-001',
                    'debtorMandateSignDate' => '2024-01-15',
                    'endToEndId'            => 'E2E-001',
                    'remittanceInformation' => 'Invoice 12345',
                    // Debtor address (stored internally)
                    'debtorAddress' => [
                        'street'     => '456 Customer Avenue',
                        'city'       => 'London',
                        'postalCode' => 'SW1A 1AA',
                        'country'    => 'GB',
                    ],
                ],
            ],
        ];

        try {
            $xml = $generator->generateFromArray($data);

            return $generator->createResponse($xml, 'direct-debit-with-addresses.xml');
        } catch (Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Demo parsing SEPA Direct Debit XML.
     * Generates a sample XML and then parses it to demonstrate the parser functionality.
     *
     * @param DirectDebitGenerator $generator Direct debit generator
     * @param DirectDebitParser $parser Direct debit parser
     */
    #[Route('/demo-parse-direct-debit', name: 'demo_parse_direct_debit')]
    public function demoParseDirectDebit(DirectDebitGenerator $generator, DirectDebitParser $parser): JsonResponse
    {
        try {
            // First, generate a sample XML with addresses
            $data = [
                'reference'           => 'MSG-PARSE-001',
                'bankAccountOwner'    => 'Demo Company',
                'paymentInfoId'       => 'PMTINF-PARSE-001',
                'dueDate'             => '2024-01-20',
                'creditorName'        => 'Demo Company Name',
                'creditorIban'        => 'ES9121000418450200051332',
                'creditorBic'         => 'CAIXESBBXXX',
                'seqType'             => 'FRST',
                'creditorId'          => 'ES98ZZZ09999999999',
                'localInstrumentCode' => 'CORE',
                'creditorAddress'     => [
                    'street'     => '123 Demo Street',
                    'city'       => 'Madrid',
                    'postalCode' => '28001',
                    'country'    => 'ES',
                ],
                'transactions' => [
                    [
                        'amount'                => 200.50,
                        'debtorIban'            => 'GB82WEST12345698765432',
                        'debtorName'            => 'Jane Smith',
                        'debtorMandate'         => 'MANDATE-PARSE-001',
                        'debtorMandateSignDate' => '2023-12-01',
                        'endToEndId'            => 'E2E-PARSE-001',
                        'debtorBic'             => 'WESTGB22',
                        'remittanceInformation' => 'Demo Invoice 67890',
                        'debtorAddress'         => [
                            'street'     => '456 Demo Avenue',
                            'city'       => 'London',
                            'postalCode' => 'SW1A 1AA',
                            'country'    => 'GB',
                        ],
                    ],
                ],
            ];

            $xml = $generator->generateFromArray($data);

            // Validate XML format
            $isValid = $parser->isValidDirectDebit($xml);

            // Parse the XML
            $parsedData = $parser->parseDirectDebit($xml);

            $response = new JsonResponse([
                'message'      => 'Successfully generated and parsed Direct Debit XML',
                'isValid'      => $isValid,
                'generatedXml' => $xml,
                'parsedData'   => $parsedData,
            ]);
            $response->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            return $response;
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
