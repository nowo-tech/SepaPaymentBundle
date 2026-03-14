<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Exporter;

use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Exporter\CsvStreamHandlerInterface;
use Nowo\SepaPaymentBundle\Exporter\ExportService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function count;

/**
 * Test cases for ExportService.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class ExportServiceTest extends TestCase
{
    private ExportService $exporter;

    protected function setUp(): void
    {
        $this->exporter = new ExportService();
    }

    public function testExportCreditTransferToJson(): void
    {
        $data = [
            'messageId'           => 'MSG-001',
            'creationDate'        => '2024-01-15T10:00:00',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'creditorIban'        => 'ES9121000418450200051332',
            'creditorName'        => 'My Company Name',
            'transactions'        => [
                [
                    'endToEndId' => 'E2E-001',
                    'amount'     => 100.50,
                    'currency'   => 'EUR',
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                ],
            ],
        ];

        $json = $this->exporter->exportCreditTransferToJson($data);

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertEquals($data, $decoded);
    }

    public function testExportDirectDebitToJson(): void
    {
        $data = [
            'messageId'           => 'MSG-001',
            'creationDate'        => '2024-01-15T10:00:00',
            'initiatingPartyName' => 'My Company',
            'paymentInfo'         => [
                'paymentInfoId' => 'PMT-001',
                'creditorIban'  => 'ES9121000418450200051332',
                'creditorName'  => 'My Company Name',
                'dueDate'       => '2024-01-20',
                'sequenceType'  => 'FRST',
            ],
            'transactions' => [
                [
                    'endToEndId' => 'E2E-001',
                    'amount'     => 100.50,
                    'currency'   => 'EUR',
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                    'mandateId'  => 'MANDATE-001',
                ],
            ],
        ];

        $json = $this->exporter->exportDirectDebitToJson($data);

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertEquals($data, $decoded);
    }

    public function testExportCreditTransferToCsv(): void
    {
        $data = [
            'messageId'              => 'MSG-001',
            'creationDate'           => '2024-01-15T10:00:00',
            'initiatingPartyName'    => 'My Company',
            'paymentInfoId'          => 'PMT-001',
            'creditorIban'           => 'ES9121000418450200051332',
            'creditorName'           => 'My Company Name',
            'creditorBic'            => 'CAIXESBBXXX',
            'requestedExecutionDate' => '2024-01-20',
            'transactions'           => [
                [
                    'endToEndId'            => 'E2E-001',
                    'amount'                => 100.50,
                    'currency'              => 'EUR',
                    'debtorIban'            => 'GB82WEST12345698765432',
                    'debtorName'            => 'John Doe',
                    'debtorBic'             => 'WESTGB22',
                    'remittanceInformation' => 'Invoice 12345',
                ],
            ],
        ];

        $csv = $this->exporter->exportCreditTransferToCsv($data);

        $this->assertStringContainsString('Message ID', $csv);
        $this->assertStringContainsString('MSG-001', $csv);
        $this->assertStringContainsString('E2E-001', $csv);
        $this->assertStringContainsString('100.5', $csv);
    }

    public function testExportDirectDebitToCsv(): void
    {
        $data = [
            'messageId'           => 'MSG-001',
            'creationDate'        => '2024-01-15T10:00:00',
            'initiatingPartyName' => 'My Company',
            'paymentInfo'         => [
                'paymentInfoId'       => 'PMT-001',
                'creditorIban'        => 'ES9121000418450200051332',
                'creditorName'        => 'My Company Name',
                'creditorBic'         => 'CAIXESBBXXX',
                'creditorId'          => 'ES98ZZZ09999999999',
                'dueDate'             => '2024-01-20',
                'sequenceType'        => 'FRST',
                'localInstrumentCode' => 'CORE',
            ],
            'transactions' => [
                [
                    'endToEndId'            => 'E2E-001',
                    'amount'                => 100.50,
                    'currency'              => 'EUR',
                    'debtorIban'            => 'GB82WEST12345698765432',
                    'debtorName'            => 'John Doe',
                    'debtorBic'             => 'WESTGB22',
                    'mandateId'             => 'MANDATE-001',
                    'mandateSignatureDate'  => '2023-12-01',
                    'remittanceInformation' => 'Invoice 12345',
                ],
            ],
        ];

        $csv = $this->exporter->exportDirectDebitToCsv($data);

        $this->assertStringContainsString('Message ID', $csv);
        $this->assertStringContainsString('MSG-001', $csv);
        $this->assertStringContainsString('E2E-001', $csv);
        $this->assertStringContainsString('FRST', $csv);
        $this->assertStringContainsString('MANDATE-001', $csv);
    }

    public function testImportCreditTransferFromJson(): void
    {
        $json = '{"messageId":"MSG-001","creationDate":"2024-01-15T10:00:00","initiatingPartyName":"My Company","paymentInfoId":"PMT-001","creditorIban":"ES9121000418450200051332","creditorName":"My Company Name","transactions":[{"endToEndId":"E2E-001","amount":100.5,"currency":"EUR","debtorIban":"GB82WEST12345698765432","debtorName":"John Doe"}]}';

        $data = $this->exporter->importCreditTransferFromJson($json);

        $this->assertIsArray($data);
        $this->assertEquals('MSG-001', $data['messageId']);
        $this->assertArrayHasKey('transactions', $data);
    }

    public function testImportDirectDebitFromJson(): void
    {
        $json = '{"messageId":"MSG-001","creationDate":"2024-01-15T10:00:00","initiatingPartyName":"My Company","paymentInfo":{"paymentInfoId":"PMT-001","creditorIban":"ES9121000418450200051332","creditorName":"My Company Name","dueDate":"2024-01-20","sequenceType":"FRST"},"transactions":[{"endToEndId":"E2E-001","amount":100.5,"currency":"EUR","debtorIban":"GB82WEST12345698765432","debtorName":"John Doe","mandateId":"MANDATE-001"}]}';

        $data = $this->exporter->importDirectDebitFromJson($json);

        $this->assertIsArray($data);
        $this->assertEquals('MSG-001', $data['messageId']);
        $this->assertArrayHasKey('paymentInfo', $data);
    }

    public function testImportFromJsonWithInvalidJson(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON format');

        $this->exporter->importCreditTransferFromJson('invalid json');
    }

    public function testExportToJsonWithPrettyPrint(): void
    {
        $data = ['messageId' => 'MSG-001', 'creationDate' => '2024-01-15'];
        $json = $this->exporter->exportCreditTransferToJson($data, true);

        $this->assertStringContainsString("\n", $json);
    }

    public function testExportToJsonWithoutPrettyPrint(): void
    {
        $data = ['messageId' => 'MSG-001', 'creationDate' => '2024-01-15'];
        $json = $this->exporter->exportCreditTransferToJson($data, false);

        $this->assertStringNotContainsString("\n", $json);
    }

    public function testExportCreditTransferToCsvWithMultipleTransactions(): void
    {
        $data = [
            'messageId'           => 'MSG-001',
            'creationDate'        => '2024-01-15T10:00:00',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'creditorIban'        => 'ES9121000418450200051332',
            'creditorName'        => 'My Company Name',
            'transactions'        => [
                [
                    'endToEndId' => 'E2E-001',
                    'amount'     => 100.50,
                    'currency'   => 'EUR',
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                ],
                [
                    'endToEndId' => 'E2E-002',
                    'amount'     => 200.75,
                    'currency'   => 'EUR',
                    'debtorIban' => 'FR1420041010050500013M02606',
                    'debtorName' => 'Jane Smith',
                ],
            ],
        ];

        $csv = $this->exporter->exportCreditTransferToCsv($data);

        $lines = explode("\n", trim($csv));
        $this->assertGreaterThan(2, count($lines)); // Header + 2 transactions
        $this->assertStringContainsString('E2E-001', $csv);
        $this->assertStringContainsString('E2E-002', $csv);
    }

    public function testExportCreditTransferToCsvWithoutTransactions(): void
    {
        $data = [
            'messageId'           => 'MSG-001',
            'creationDate'        => '2024-01-15T10:00:00',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'creditorIban'        => 'ES9121000418450200051332',
            'creditorName'        => 'My Company Name',
            'transactions'        => [],
        ];

        $csv = $this->exporter->exportCreditTransferToCsv($data);

        $this->assertStringContainsString('Message ID', $csv);
        $this->assertStringContainsString('MSG-001', $csv);
    }

    /**
     * Tests export when transactions are under paymentInfo (branch: empty($transactions) && isset($data['paymentInfo']['transactions'])).
     */
    public function testExportCreditTransferToCsvWithTransactionsInPaymentInfo(): void
    {
        $data = [
            'messageId'           => 'MSG-001',
            'creationDate'        => '2024-01-15T10:00:00',
            'initiatingPartyName' => 'My Company',
            'paymentInfo'         => [
                'paymentInfoId' => 'PMT-001',
                'creditorIban'  => 'ES9121000418450200051332',
                'creditorName'  => 'My Company Name',
                'transactions'  => [
                    [
                        'endToEndId' => 'E2E-001',
                        'amount'     => 100.50,
                        'currency'   => 'EUR',
                        'debtorIban' => 'GB82WEST12345698765432',
                        'debtorName' => 'John Doe',
                    ],
                ],
            ],
        ];

        $csv = $this->exporter->exportCreditTransferToCsv($data);
        $this->assertStringContainsString('E2E-001', $csv);
        $this->assertStringContainsString('MSG-001', $csv);
    }

    public function testExportDirectDebitToCsvWithoutTransactions(): void
    {
        $data = [
            'messageId'           => 'MSG-001',
            'creationDate'        => '2024-01-15T10:00:00',
            'initiatingPartyName' => 'My Company',
            'paymentInfo'         => [
                'paymentInfoId' => 'PMT-001',
                'creditorIban'  => 'ES9121000418450200051332',
                'creditorName'  => 'My Company Name',
            ],
            'transactions' => [],
        ];

        $csv = $this->exporter->exportDirectDebitToCsv($data);

        $this->assertStringContainsString('Message ID', $csv);
        $this->assertStringContainsString('MSG-001', $csv);
    }

    /**
     * Tests export direct debit when transactions are under paymentInfo (branch: empty($transactions) && isset($data['paymentInfo']['transactions'])).
     */
    public function testExportDirectDebitToCsvWithTransactionsInPaymentInfo(): void
    {
        $data = [
            'messageId'           => 'MSG-001',
            'creationDate'        => '2024-01-15T10:00:00',
            'initiatingPartyName' => 'My Company',
            'paymentInfo'         => [
                'paymentInfoId' => 'PMT-001',
                'creditorIban'  => 'ES9121000418450200051332',
                'creditorName'  => 'My Company Name',
                'transactions'  => [
                    [
                        'endToEndId' => 'E2E-001',
                        'amount'     => 50.00,
                        'currency'   => 'EUR',
                        'debtorIban' => 'GB82WEST12345698765432',
                        'debtorName' => 'John Doe',
                        'mandateId'  => 'MANDATE-001',
                    ],
                ],
            ],
        ];

        $csv = $this->exporter->exportDirectDebitToCsv($data);
        $this->assertStringContainsString('E2E-001', $csv);
        $this->assertStringContainsString('MSG-001', $csv);
    }

    public function testExportCreditTransferToCsvWithCustomDelimiter(): void
    {
        $data = [
            'messageId'           => 'MSG-001',
            'creationDate'        => '2024-01-15T10:00:00',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'creditorIban'        => 'ES9121000418450200051332',
            'creditorName'        => 'My Company Name',
            'transactions'        => [
                [
                    'endToEndId' => 'E2E-001',
                    'amount'     => 100.50,
                    'currency'   => 'EUR',
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                ],
            ],
        ];

        $csv = $this->exporter->exportCreditTransferToCsv($data, ';', '"');

        $this->assertStringContainsString(';', $csv);
        $this->assertStringNotContainsString(',', $csv);
    }

    public function testExportDirectDebitToCsvWithCustomDelimiter(): void
    {
        $data = [
            'messageId'           => 'MSG-001',
            'creationDate'        => '2024-01-15T10:00:00',
            'initiatingPartyName' => 'My Company',
            'paymentInfo'         => [
                'paymentInfoId' => 'PMT-001',
                'creditorIban'  => 'ES9121000418450200051332',
                'creditorName'  => 'My Company Name',
            ],
            'transactions' => [
                [
                    'endToEndId' => 'E2E-001',
                    'amount'     => 100.50,
                    'currency'   => 'EUR',
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                ],
            ],
        ];

        $csv = $this->exporter->exportDirectDebitToCsv($data, '|', "'");

        $this->assertStringContainsString('|', $csv);
    }

    public function testImportFromJsonWithNonArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON must contain an object/array');

        $this->exporter->importCreditTransferFromJson('"string"');
    }

    public function testExportCreditTransferToCsvWithPaymentInfoStructure(): void
    {
        $data = [
            'messageId'           => 'MSG-001',
            'creationDate'        => '2024-01-15T10:00:00',
            'initiatingPartyName' => 'My Company',
            'paymentInfo'         => [
                'paymentInfoId' => 'PMT-001',
                'creditorIban'  => 'ES9121000418450200051332',
                'creditorName'  => 'My Company Name',
            ],
            'transactions' => [
                [
                    'endToEndId' => 'E2E-001',
                    'amount'     => 100.50,
                    'currency'   => 'EUR',
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                ],
            ],
        ];

        $csv = $this->exporter->exportCreditTransferToCsv($data);

        $this->assertStringContainsString('PMT-001', $csv);
        $this->assertStringContainsString('E2E-001', $csv);
    }

    public function testExportDirectDebitToCsvWithMultipleTransactions(): void
    {
        $data = [
            'messageId'           => 'MSG-001',
            'creationDate'        => '2024-01-15T10:00:00',
            'initiatingPartyName' => 'My Company',
            'paymentInfo'         => [
                'paymentInfoId' => 'PMT-001',
                'creditorIban'  => 'ES9121000418450200051332',
                'creditorName'  => 'My Company Name',
            ],
            'transactions' => [
                [
                    'endToEndId' => 'E2E-001',
                    'amount'     => 100.50,
                    'currency'   => 'EUR',
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                ],
                [
                    'endToEndId' => 'E2E-002',
                    'amount'     => 200.75,
                    'currency'   => 'EUR',
                    'debtorIban' => 'FR1420041010050500013M02606',
                    'debtorName' => 'Jane Smith',
                ],
            ],
        ];

        $csv = $this->exporter->exportDirectDebitToCsv($data);

        $lines = explode("\n", trim($csv));
        $this->assertGreaterThan(2, count($lines));
        $this->assertStringContainsString('E2E-001', $csv);
        $this->assertStringContainsString('E2E-002', $csv);
    }

    public function testExportCreditTransferToCsvWithEmptyData(): void
    {
        $data = [];
        $csv  = $this->exporter->exportCreditTransferToCsv($data);

        $this->assertStringContainsString('Message ID', $csv);
    }

    public function testExportDirectDebitToCsvWithEmptyData(): void
    {
        $data = [];
        $csv  = $this->exporter->exportDirectDebitToCsv($data);

        $this->assertStringContainsString('Message ID', $csv);
    }

    public function testExportCreditTransferToCsvWithSpecialCharacters(): void
    {
        $data = [
            'messageId'           => 'MSG-001',
            'initiatingPartyName' => 'Company "Test" & Co.',
            'creditorName'        => 'Name with, comma',
            'transactions'        => [
                [
                    'endToEndId'            => 'E2E-001',
                    'amount'                => 100.50,
                    'currency'              => 'EUR',
                    'debtorIban'            => 'GB82WEST12345698765432',
                    'debtorName'            => 'John "Doe"',
                    'remittanceInformation' => 'Invoice #12345',
                ],
            ],
        ];

        $csv = $this->exporter->exportCreditTransferToCsv($data);

        $this->assertStringContainsString('MSG-001', $csv);
        $this->assertStringContainsString('E2E-001', $csv);
    }

    public function testExportCreditTransferToJsonThrowsWhenJsonEncodeFails(): void
    {
        $data = [
            'messageId' => "invalid \x80 UTF-8 sequence",
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to encode data to JSON');

        $this->exporter->exportCreditTransferToJson($data);
    }

    /**
     * Tests importDirectDebitFromJson with invalid JSON (covers method and exception path).
     */
    public function testImportDirectDebitFromJsonWithInvalidJson(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON format');

        $this->exporter->importDirectDebitFromJson('not valid json {{{');
    }

    public function testExportCreditTransferToCsvThrowsWhenStreamOpenFails(): void
    {
        $handler = new class () implements CsvStreamHandlerInterface {
            public function open()
            {
                return false;
            }

            public function getContents($stream): string|false
            {
                return '';
            }
        };
        $exporter = new ExportService($handler);
        $data     = [
            'messageId'    => 'MSG-001',
            'transactions' => [['endToEndId' => 'E2E-001', 'amount' => 1, 'currency' => 'EUR']],
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to open temporary stream for CSV generation');

        $exporter->exportCreditTransferToCsv($data);
    }

    public function testExportCreditTransferToCsvThrowsWhenGetContentsFails(): void
    {
        $handler = new class () implements CsvStreamHandlerInterface {
            public function open()
            {
                $r = fopen('php://temp', 'r+');

                return $r !== false ? $r : false;
            }

            public function getContents($stream): string|false
            {
                return false;
            }
        };
        $exporter = new ExportService($handler);
        $data     = [
            'messageId'    => 'MSG-001',
            'transactions' => [['endToEndId' => 'E2E-001', 'amount' => 1, 'currency' => 'EUR']],
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to generate CSV content');

        $exporter->exportCreditTransferToCsv($data);
    }
}
