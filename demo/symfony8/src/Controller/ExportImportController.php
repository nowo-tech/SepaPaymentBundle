<?php

declare(strict_types=1);

namespace App\Controller;

use Exception;
use Nowo\SepaPaymentBundle\Exporter\ExportService;
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator;
use Nowo\SepaPaymentBundle\Parser\CreditTransferParser;
use Nowo\SepaPaymentBundle\Parser\DirectDebitParser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_UNICODE;

class ExportImportController extends AbstractController
{
    /**
     * Demo export Credit Transfer to JSON.
     *
     * @param CreditTransferGenerator $generator Credit transfer generator
     * @param CreditTransferParser $parser Credit transfer parser
     * @param ExportService $exporter Export service
     */
    #[Route('/demo-export-credit-transfer-json', name: 'demo_export_credit_transfer_json')]
    public function demoExportCreditTransferJson(CreditTransferGenerator $generator, CreditTransferParser $parser, ExportService $exporter): JsonResponse
    {
        try {
            // Generate XML
            $data = [
                'reference'           => 'MSG-EXPORT-001',
                'initiatingPartyName' => 'Export Demo Company',
                'paymentInfoId'       => 'PMT-EXPORT-001',
                // Debtor data (company that PAYS)
                'debtorIban'             => 'ES9121000418450200051332',
                'debtorName'             => 'Export Demo Company Name',
                'requestedExecutionDate' => '2024-01-20',
                'debtorBic'              => 'CAIXESBBXXX',
                'transactions'           => [
                    [
                        'amount'                => 150.75,
                        'currency'              => 'EUR',
                        'creditorIban'          => 'GB82WEST12345698765432',
                        'creditorName'          => 'John Doe',
                        'endToEndId'            => 'E2E-EXPORT-001',
                        'creditorBic'           => 'WESTGB22',
                        'remittanceInformation' => 'Export Demo Invoice',
                    ],
                ],
            ];

            $xml        = $generator->generateFromArray($data);
            $parsedData = $parser->parseCreditTransfer($xml);

            // Export to JSON
            $json = $exporter->exportCreditTransferToJson($parsedData, true);

            $response = new JsonResponse([
                'message'      => 'Successfully exported Credit Transfer to JSON',
                'originalData' => $data,
                'parsedData'   => $parsedData,
                'json'         => $json,
                'jsonDecoded'  => json_decode($json, true),
            ]);
            $response->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            return $response;
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Demo export Credit Transfer to CSV.
     *
     * @param CreditTransferGenerator $generator Credit transfer generator
     * @param CreditTransferParser $parser Credit transfer parser
     * @param ExportService $exporter Export service
     */
    #[Route('/demo-export-credit-transfer-csv', name: 'demo_export_credit_transfer_csv')]
    public function demoExportCreditTransferCsv(CreditTransferGenerator $generator, CreditTransferParser $parser, ExportService $exporter): Response
    {
        try {
            // Generate XML
            $data = [
                'reference'           => 'MSG-EXPORT-CSV-001',
                'initiatingPartyName' => 'CSV Export Demo',
                'paymentInfoId'       => 'PMT-EXPORT-CSV-001',
                // Debtor data (company that PAYS)
                'debtorIban'             => 'ES9121000418450200051332',
                'debtorName'             => 'CSV Export Company',
                'requestedExecutionDate' => '2024-01-20',
                'debtorBic'              => 'CAIXESBBXXX',
                'transactions'           => [
                    [
                        'amount'                => 100.50,
                        'currency'              => 'EUR',
                        'creditorIban'          => 'GB82WEST12345698765432',
                        'creditorName'          => 'John Doe',
                        'endToEndId'            => 'E2E-EXPORT-CSV-001',
                        'creditorBic'           => 'WESTGB22',
                        'remittanceInformation' => 'CSV Export Invoice',
                    ],
                    [
                        'amount'                => 200.75,
                        'currency'              => 'EUR',
                        'creditorIban'          => 'FR1420041010050500013M02606',
                        'creditorName'          => 'Jane Smith',
                        'endToEndId'            => 'E2E-EXPORT-CSV-002',
                        'creditorBic'           => 'BNPAFRPPXXX',
                        'remittanceInformation' => 'CSV Export Invoice 2',
                    ],
                ],
            ];

            $xml        = $generator->generateFromArray($data);
            $parsedData = $parser->parseCreditTransfer($xml);

            // Export to CSV
            $csv = $exporter->exportCreditTransferToCsv($parsedData);

            $response = new Response($csv);
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="credit-transfer-export.csv"');

            return $response;
        } catch (Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Demo export Direct Debit to JSON.
     *
     * @param DirectDebitGenerator $generator Direct debit generator
     * @param DirectDebitParser $parser Direct debit parser
     * @param ExportService $exporter Export service
     */
    #[Route('/demo-export-direct-debit-json', name: 'demo_export_direct_debit_json')]
    public function demoExportDirectDebitJson(DirectDebitGenerator $generator, DirectDebitParser $parser, ExportService $exporter): JsonResponse
    {
        try {
            // Generate XML
            $data = [
                'reference'           => 'MSG-EXPORT-DD-001',
                'bankAccountOwner'    => 'Direct Debit Export Demo',
                'paymentInfoId'       => 'PMT-EXPORT-DD-001',
                'creditorIban'        => 'ES9121000418450200051332',
                'creditorName'        => 'Direct Debit Export Company',
                'dueDate'             => '2024-01-20',
                'creditorBic'         => 'CAIXESBBXXX',
                'creditorId'          => 'ES98ZZZ09999999999',
                'seqType'             => 'FRST',
                'localInstrumentCode' => 'CORE',
                'transactions'        => [
                    [
                        'amount'                => 150.50,
                        'currency'              => 'EUR',
                        'debtorIban'            => 'GB82WEST12345698765432',
                        'debtorName'            => 'John Doe',
                        'endToEndId'            => 'E2E-EXPORT-DD-001',
                        'debtorBic'             => 'WESTGB22',
                        'debtorMandate'         => 'MANDATE-EXPORT-001',
                        'debtorMandateSignDate' => '2023-12-01',
                        'remittanceInformation' => 'Direct Debit Export Invoice',
                    ],
                ],
            ];

            $xml        = $generator->generateFromArray($data);
            $parsedData = $parser->parseDirectDebit($xml);

            // Export to JSON
            $json = $exporter->exportDirectDebitToJson($parsedData, true);

            $response = new JsonResponse([
                'message'      => 'Successfully exported Direct Debit to JSON',
                'originalData' => $data,
                'parsedData'   => $parsedData,
                'json'         => $json,
                'jsonDecoded'  => json_decode($json, true),
            ]);
            $response->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            return $response;
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Demo export Direct Debit to CSV.
     *
     * @param DirectDebitGenerator $generator Direct debit generator
     * @param DirectDebitParser $parser Direct debit parser
     * @param ExportService $exporter Export service
     */
    #[Route('/demo-export-direct-debit-csv', name: 'demo_export_direct_debit_csv')]
    public function demoExportDirectDebitCsv(DirectDebitGenerator $generator, DirectDebitParser $parser, ExportService $exporter): Response
    {
        try {
            // Generate XML
            $data = [
                'reference'           => 'MSG-EXPORT-DD-CSV-001',
                'bankAccountOwner'    => 'Direct Debit CSV Export',
                'paymentInfoId'       => 'PMT-EXPORT-DD-CSV-001',
                'creditorIban'        => 'ES9121000418450200051332',
                'creditorName'        => 'Direct Debit CSV Company',
                'dueDate'             => '2024-01-20',
                'creditorBic'         => 'CAIXESBBXXX',
                'creditorId'          => 'ES98ZZZ09999999999',
                'seqType'             => 'FRST',
                'localInstrumentCode' => 'CORE',
                'transactions'        => [
                    [
                        'amount'                => 100.50,
                        'currency'              => 'EUR',
                        'debtorIban'            => 'GB82WEST12345698765432',
                        'debtorName'            => 'John Doe',
                        'endToEndId'            => 'E2E-EXPORT-DD-CSV-001',
                        'debtorBic'             => 'WESTGB22',
                        'debtorMandate'         => 'MANDATE-EXPORT-CSV-001',
                        'debtorMandateSignDate' => '2023-12-01',
                        'remittanceInformation' => 'Direct Debit CSV Invoice',
                    ],
                ],
            ];

            $xml        = $generator->generateFromArray($data);
            $parsedData = $parser->parseDirectDebit($xml);

            // Export to CSV
            $csv = $exporter->exportDirectDebitToCsv($parsedData);

            $response = new Response($csv);
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="direct-debit-export.csv"');

            return $response;
        } catch (Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Demo import from JSON.
     *
     * @param ExportService $exporter Export service
     */
    #[Route('/demo-import-from-json', name: 'demo_import_from_json')]
    public function demoImportFromJson(ExportService $exporter): JsonResponse
    {
        $json = '{
            "messageId": "MSG-IMPORT-001",
            "creationDate": "2024-01-15T10:00:00",
            "initiatingPartyName": "Import Demo Company",
            "paymentInfoId": "PMT-IMPORT-001",
            "debtorIban": "ES9121000418450200051332",
            "debtorName": "Import Demo Company Name",
            "requestedExecutionDate": "2024-01-20",
            "debtorBic": "CAIXESBBXXX",
            "transactions": [
                {
                    "endToEndId": "E2E-IMPORT-001",
                    "amount": 250.00,
                    "currency": "EUR",
                    "creditorIban": "GB82WEST12345698765432",
                    "creditorName": "John Doe",
                    "creditorBic": "WESTGB22",
                    "remittanceInformation": "Imported from JSON"
                }
            ]
        }';

        try {
            $data = $exporter->importCreditTransferFromJson($json);

            $response = new JsonResponse([
                'message'      => 'Successfully imported Credit Transfer from JSON',
                'importedData' => $data,
                'note'         => 'This data can now be used with CreditTransferGenerator::generateFromArray()',
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
