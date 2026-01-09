<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Command;

use Nowo\SepaPaymentBundle\Command\ParseDirectDebitCommand;
use Nowo\SepaPaymentBundle\Parser\DirectDebitParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests for ParseDirectDebitCommand.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class ParseDirectDebitCommandTest extends TestCase
{
    /**
     * Test parsing valid Direct Debit XML.
     */
    public function testParseValidDirectDebit(): void
    {
        $parser = new DirectDebitParser();
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
        $parser = new DirectDebitParser();
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
        $parser = new DirectDebitParser();
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
        $parser = new DirectDebitParser();
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
}
