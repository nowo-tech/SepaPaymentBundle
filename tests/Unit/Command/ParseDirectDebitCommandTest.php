<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Command;

use Nowo\SepaPaymentBundle\Command\ParseDirectDebitCommand;
use Nowo\SepaPaymentBundle\Parser\DirectDebitParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests for ParseDirectDebitCommand.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class ParseDirectDebitCommandTest extends TestCase
{
    /**
     * Test that command definition is set (configure() is invoked when getDefinition() is first called).
     */
    public function testCommandDefinitionHasFileArgumentAndJsonOption(): void
    {
        $parser  = new DirectDebitParser();
        $command = new ParseDirectDebitCommand($parser);

        $def = $command->getDefinition();
        $this->assertTrue($def->hasArgument('file'));
        $this->assertTrue($def->hasOption('json'));
        $this->assertSame('Path to the SEPA Direct Debit XML file', $def->getArgument('file')->getDescription());
    }

    /**
     * Test parsing valid Direct Debit XML.
     */
    public function testParseValidDirectDebit(): void
    {
        $parser  = new DirectDebitParser();
        $command = new ParseDirectDebitCommand($parser);

        $commandTester = new CommandTester($command);

        // Create a temporary XML file
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">
    <CstmrDrctDbtInitn>
        <GrpHdr>
            <MsgId>MSG-001</MsgId>
            <CreDtTm>2024-01-15T10:00:00</CreDtTm>
            <InitgPty>
                <Nm>My Company</Nm>
            </InitgPty>
        </GrpHdr>
        <PmtInf>
            <PmtInfId>PMT-001</PmtInfId>
            <PmtMtd>DD</PmtMtd>
            <BtchBookg>false</BtchBookg>
            <ReqdColltnDt>2024-01-20</ReqdColltnDt>
            <Cdtr>
                <Nm>Creditor Name</Nm>
            </Cdtr>
            <CdtrAcct>
                <Id>
                    <IBAN>ES9121000418450200051332</IBAN>
                </Id>
            </CdtrAcct>
            <CdtrAgt>
                <FinInstnId>
                    <BIC>CAIXESBBXXX</BIC>
                </FinInstnId>
            </CdtrAgt>
            <ChrgBr>SLEV</ChrgBr>
            <CdtrSchmeId>
                <Id>
                    <PrvtId>
                        <Othr>
                            <Id>CREDITOR-ID</Id>
                            <SchmeNm>
                                <Prtry>SEPA</Prtry>
                            </SchmeNm>
                        </Othr>
                    </PrvtId>
                </Id>
            </CdtrSchmeId>
            <DrctDbtTxInf>
                <PmtId>
                    <EndToEndId>E2E-001</EndToEndId>
                </PmtId>
                <InstdAmt Ccy="EUR">100.50</InstdAmt>
                <DrctDbtTx>
                    <MndtRltdInf>
                        <MndtId>MANDATE-001</MndtId>
                        <DtOfSgntr>2024-01-10</DtOfSgntr>
                    </MndtRltdInf>
                </DrctDbtTx>
                <DbtrAgt>
                    <FinInstnId>
                        <BIC>WESTGB22</BIC>
                    </FinInstnId>
                </DbtrAgt>
                <Dbtr>
                    <Nm>John Doe</Nm>
                </Dbtr>
                <DbtrAcct>
                    <Id>
                        <IBAN>GB82WEST12345698765432</IBAN>
                    </Id>
                </DbtrAcct>
                <RmtInf>
                    <Ustrd>Payment for services</Ustrd>
                </RmtInf>
            </DrctDbtTxInf>
        </PmtInf>
    </CstmrDrctDbtInitn>
</Document>';

        $tempFile = sys_get_temp_dir() . '/test_direct_debit_' . uniqid() . '.xml';
        file_put_contents($tempFile, $xml);

        try {
            $commandTester->execute(['file' => $tempFile]);
            $output = $commandTester->getDisplay();

            $this->assertEquals(0, $commandTester->getStatusCode());
            $this->assertStringContainsString('MSG-001', $output);
            $this->assertStringContainsString('E2E-001', $output);
            $this->assertStringContainsString('100.50', $output);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Test parsing with JSON output.
     */
    public function testParseWithJsonOutput(): void
    {
        $parser  = new DirectDebitParser();
        $command = new ParseDirectDebitCommand($parser);

        $commandTester = new CommandTester($command);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">
    <CstmrDrctDbtInitn>
        <GrpHdr>
            <MsgId>MSG-001</MsgId>
            <CreDtTm>2024-01-15T10:00:00</CreDtTm>
            <InitgPty>
                <Nm>My Company</Nm>
            </InitgPty>
        </GrpHdr>
        <PmtInf>
            <PmtInfId>PMT-001</PmtInfId>
            <PmtMtd>DD</PmtMtd>
            <ReqdColltnDt>2024-01-20</ReqdColltnDt>
            <Cdtr>
                <Nm>Creditor Name</Nm>
            </Cdtr>
            <CdtrAcct>
                <Id>
                    <IBAN>ES9121000418450200051332</IBAN>
                </Id>
            </CdtrAcct>
            <CdtrSchmeId>
                <Id>
                    <PrvtId>
                        <Othr>
                            <Id>CREDITOR-ID</Id>
                            <SchmeNm>
                                <Prtry>SEPA</Prtry>
                            </SchmeNm>
                        </Othr>
                    </PrvtId>
                </Id>
            </CdtrSchmeId>
            <DrctDbtTxInf>
                <PmtId>
                    <EndToEndId>E2E-001</EndToEndId>
                </PmtId>
                <InstdAmt Ccy="EUR">100.50</InstdAmt>
                <DrctDbtTx>
                    <MndtRltdInf>
                        <MndtId>MANDATE-001</MndtId>
                        <DtOfSgntr>2024-01-10</DtOfSgntr>
                    </MndtRltdInf>
                </DrctDbtTx>
                <Dbtr>
                    <Nm>John Doe</Nm>
                </Dbtr>
                <DbtrAcct>
                    <Id>
                        <IBAN>GB82WEST12345698765432</IBAN>
                    </Id>
                </DbtrAcct>
            </DrctDbtTxInf>
        </PmtInf>
    </CstmrDrctDbtInitn>
</Document>';

        $tempFile = sys_get_temp_dir() . '/test_direct_debit_' . uniqid() . '.xml';
        file_put_contents($tempFile, $xml);

        try {
            $commandTester->execute(['file' => $tempFile, '--json' => true]);
            $output = $commandTester->getDisplay();

            $this->assertEquals(0, $commandTester->getStatusCode());
            $this->assertStringContainsString('"messageId"', $output);
            $this->assertStringContainsString('"MSG-001"', $output);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Test parsing invalid file.
     */
    public function testParseInvalidFile(): void
    {
        $parser  = new DirectDebitParser();
        $command = new ParseDirectDebitCommand($parser);

        $commandTester = new CommandTester($command);

        $commandTester->execute(['file' => '/nonexistent/file.xml']);
        $this->assertEquals(1, $commandTester->getStatusCode());
        $this->assertStringContainsString('File not found', $commandTester->getDisplay());
    }

    /**
     * Test parsing invalid XML.
     */
    public function testParseInvalidXml(): void
    {
        $parser  = new DirectDebitParser();
        $command = new ParseDirectDebitCommand($parser);

        $commandTester = new CommandTester($command);

        $tempFile = sys_get_temp_dir() . '/test_invalid_' . uniqid() . '.xml';
        file_put_contents($tempFile, '<invalid>xml</invalid>');

        try {
            $commandTester->execute(['file' => $tempFile]);
            $this->assertEquals(1, $commandTester->getStatusCode());
            $this->assertStringContainsString('Invalid', $commandTester->getDisplay());
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Test when file_get_contents returns false (covers lines 85-88).
     * Uses a stream wrapper so the path passes file_exists/is_file/is_readable but file_get_contents fails.
     */
    public function testParseWhenFileGetContentsReturnsFalse(): void
    {
        $wrapper = static function (): bool {
            return stream_wrapper_register('readfail', \Nowo\SepaPaymentBundle\Tests\Unit\Command\ReadFailStreamWrapper::class);
        };
        if (!@$wrapper()) {
            $this->markTestSkipped('Could not register stream wrapper');
        }
        $previous = set_error_handler(static function (int $errno, string $errstr): bool {
            return str_contains($errstr, 'ReadFailStreamWrapper') || str_contains($errstr, 'file_get_contents');
        });
        try {
            $parser         = new DirectDebitParser();
            $command        = new ParseDirectDebitCommand($parser);
            $commandTester  = new CommandTester($command);

            $commandTester->execute(['file' => 'readfail://any']);
            $this->assertEquals(1, $commandTester->getStatusCode());
            $this->assertStringContainsString('Could not read file', $commandTester->getDisplay());
        } finally {
            restore_error_handler();
            if ($previous !== null) {
                set_error_handler($previous);
            }
            stream_wrapper_unregister('readfail');
        }
    }

    /**
     * Test when file cannot be read (file_get_contents returns false).
     */
    public function testParseWhenFileCannotBeRead(): void
    {
        $parser        = new DirectDebitParser();
        $command       = new ParseDirectDebitCommand($parser);
        $commandTester = new CommandTester($command);

        // Passing a directory path causes file_get_contents to return false
        $tempDir = sys_get_temp_dir() . '/sepa_test_dir_' . uniqid();
        mkdir($tempDir);

        try {
            $commandTester->execute(['file' => $tempDir]);
            $this->assertEquals(1, $commandTester->getStatusCode());
            $display = $commandTester->getDisplay();
            $this->assertTrue(
                str_contains($display, 'Could not read file')
                || str_contains($display, 'Invalid SEPA Direct Debit XML')
                || str_contains($display, 'Invalid'),
                'Expected error message when file cannot be read. Got: ' . $display,
            );
        } finally {
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }

    /**
     * Test when path is a file but not readable (is_file true, is_readable false).
     * On some systems chmod 0000 may still allow the owner to read; then we get invalid XML error.
     */
    public function testParseWhenFileExistsButNotReadable(): void
    {
        $parser        = new DirectDebitParser();
        $command       = new ParseDirectDebitCommand($parser);
        $commandTester = new CommandTester($command);

        $tempFile = sys_get_temp_dir() . '/sepa_test_unreadable_' . uniqid() . '.xml';
        file_put_contents($tempFile, '<?xml version="1.0"?><root/>');
        chmod($tempFile, 0000);

        try {
            $commandTester->execute(['file' => $tempFile]);
            $this->assertEquals(1, $commandTester->getStatusCode());
            $display = $commandTester->getDisplay();
            $this->assertTrue(
                str_contains($display, 'Could not read file') || str_contains($display, 'Invalid SEPA Direct Debit XML'),
                'Expected "Could not read file" or "Invalid SEPA Direct Debit XML". Got: ' . $display,
            );
        } finally {
            @chmod($tempFile, 0644);
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    /**
     * Test when parser throws exception during parse.
     */
    public function testParseWhenParserThrowsException(): void
    {
        $parser = $this->createMock(DirectDebitParser::class);
        $parser->method('isValidDirectDebit')->willReturn(true);
        $parser->method('parseDirectDebit')->willThrowException(new RuntimeException('Parse error'));

        $command       = new ParseDirectDebitCommand($parser);
        $commandTester = new CommandTester($command);

        $tempFile = sys_get_temp_dir() . '/test_dd_' . uniqid() . '.xml';
        file_put_contents($tempFile, '<?xml version="1.0"?><root/>');

        try {
            $commandTester->execute(['file' => $tempFile]);
            $this->assertEquals(1, $commandTester->getStatusCode());
            $this->assertStringContainsString('Error parsing XML', $commandTester->getDisplay());
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Test JSON output when json_encode fails (invalid UTF-8 in data) outputs fallback '{}'.
     */
    public function testParseWithJsonOutputWhenJsonEncodeFailsOutputsFallback(): void
    {
        $parser = $this->createMock(DirectDebitParser::class);
        $parser->method('isValidDirectDebit')->willReturn(true);
        // Data containing invalid UTF-8 makes json_encode return false
        $parser->method('parseDirectDebit')->willReturn([
            'messageId' => "invalid \x80 UTF-8",
            'transactions' => [],
        ]);

        $command       = new ParseDirectDebitCommand($parser);
        $commandTester = new CommandTester($command);

        $tempFile = sys_get_temp_dir() . '/test_dd_json_fail_' . uniqid() . '.xml';
        file_put_contents($tempFile, '<?xml version="1.0"?><root/>');

        try {
            $commandTester->execute(['file' => $tempFile, '--json' => true]);
            $this->assertEquals(0, $commandTester->getStatusCode());
            $this->assertStringContainsString('{}', $commandTester->getDisplay());
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Test table output when transactions have non-numeric amount (covers amount branch).
     */
    public function testParseWithNonNumericAmountInTransaction(): void
    {
        $parser = $this->createMock(DirectDebitParser::class);
        $parser->method('isValidDirectDebit')->willReturn(true);
        $parser->method('parseDirectDebit')->willReturn([
            'messageId'           => 'MSG-001',
            'creationDate'        => '2024-01-15T10:00:00',
            'initiatingPartyName' => 'Company',
            'paymentInfoId'       => 'PMT-001',
            'sequenceType'        => 'RCUR',
            'dueDate'             => '2024-01-20',
            'localInstrumentCode' => 'CORE',
            'creditorName'        => 'Creditor',
            'creditorIban'        => 'ES9121000418450200051332',
            'creditorId'          => 'ES98ZZZ',
            'transactions'        => [
                [
                    'endToEndId' => 'E2E-001',
                    'amount'     => 'N/A',
                    'currency'   => 'EUR',
                    'debtorName' => 'John',
                    'debtorIban' => 'GB82WEST12345698765432',
                ],
            ],
        ]);

        $command       = new ParseDirectDebitCommand($parser);
        $commandTester = new CommandTester($command);

        $tempFile = sys_get_temp_dir() . '/test_dd_numeric_' . uniqid() . '.xml';
        file_put_contents($tempFile, '<?xml version="1.0"?><root/>');

        try {
            $commandTester->execute(['file' => $tempFile]);
            $this->assertEquals(0, $commandTester->getStatusCode());
            $this->assertStringContainsString('E2E-001', $commandTester->getDisplay());
            $this->assertStringContainsString('N/A', $commandTester->getDisplay());
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Test table output when data has no transactions (warning path).
     */
    public function testParseWithEmptyTransactionsShowsWarning(): void
    {
        $parser = $this->createMock(DirectDebitParser::class);
        $parser->method('isValidDirectDebit')->willReturn(true);
        $parser->method('parseDirectDebit')->willReturn([
            'messageId'           => 'MSG-001',
            'creationDate'        => '2024-01-15T10:00:00',
            'initiatingPartyName' => 'Company',
            'paymentInfoId'       => 'PMT-001',
            'sequenceType'        => 'RCUR',
            'dueDate'             => '2024-01-20',
            'localInstrumentCode' => 'CORE',
            'creditorName'        => 'Creditor',
            'creditorIban'        => 'ES9121000418450200051332',
            'creditorBic'         => null,
            'creditorId'          => 'ES98ZZZ',
            'transactions'        => [],
        ]);

        $command       = new ParseDirectDebitCommand($parser);
        $commandTester = new CommandTester($command);

        $tempFile = sys_get_temp_dir() . '/test_dd_empty_' . uniqid() . '.xml';
        file_put_contents($tempFile, '<?xml version="1.0"?><root/>');

        try {
            $commandTester->execute(['file' => $tempFile]);
            $this->assertEquals(0, $commandTester->getStatusCode());
            $this->assertStringContainsString('No transactions found', $commandTester->getDisplay());
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
}
