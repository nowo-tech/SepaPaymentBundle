<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Parser;

use Nowo\SepaPaymentBundle\Parser\RemesaParser;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for RemesaParser.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class RemesaParserTest extends TestCase
{
    /**
     * Remesa parser instance.
     *
     * @var RemesaParser
     */
    private RemesaParser $parser;

    /**
     * Sets up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->parser = new RemesaParser();
    }

    /**
     * Tests parsing a valid SEPA Credit Transfer XML.
     *
     * @return void
     */
    public function testParseCreditTransfer(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.03">
                <CstmrCdtTrfInitn>
                    <GrpHdr>
                        <MsgId>MSG-001</MsgId>
                        <CreDtTm>2024-01-15T10:00:00</CreDtTm>
                        <InitgPty>
                            <Nm>My Company</Nm>
                        </InitgPty>
                    </GrpHdr>
                    <PmtInf>
                        <PmtInfId>PMT-001</PmtInfId>
                        <NbOfTxs>1</NbOfTxs>
                        <CtrlSum>100.50</CtrlSum>
                        <CdtTrfTxInf>
                            <EndToEndId>E2E-001</EndToEndId>
                            <InstdAmt Ccy="EUR">100.50</InstdAmt>
                            <CdtrAcct>
                                <Id>
                                    <IBAN>ES9121000418450200051332</IBAN>
                                </Id>
                            </CdtrAcct>
                            <Cdtr>
                                <Nm>John Doe</Nm>
                            </Cdtr>
                            <RmtInf>
                                <Ustrd>Invoice 12345</Ustrd>
                            </RmtInf>
                        </CdtTrfTxInf>
                    </PmtInf>
                </CstmrCdtTrfInitn>
            </Document>
            XML;

        $data = $this->parser->parseCreditTransfer($xml);

        $this->assertEquals('MSG-001', $data['messageId']);
        $this->assertEquals('2024-01-15T10:00:00', $data['creationDate']);
        $this->assertEquals('My Company', $data['initiatingPartyName']);
        $this->assertEquals('PMT-001', $data['paymentInfoId']);
        $this->assertEquals(1, $data['numberOfTransactions']);
        $this->assertEquals(100.50, $data['controlSum']);
        $this->assertCount(1, $data['transactions']);
        $this->assertEquals('E2E-001', $data['transactions'][0]['endToEndId']);
        $this->assertEquals(100.50, $data['transactions'][0]['amount']);
        $this->assertEquals('EUR', $data['transactions'][0]['currency']);
        $this->assertEquals('ES9121000418450200051332', $data['transactions'][0]['iban']);
        $this->assertEquals('John Doe', $data['transactions'][0]['name']);
        $this->assertEquals('Invoice 12345', $data['transactions'][0]['remittanceInformation']);
    }

    /**
     * Tests parsing invalid XML.
     *
     * @return void
     */
    public function testParseInvalidXml(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid XML format');

        $this->parser->parseCreditTransfer('Invalid XML');
    }

    /**
     * Tests validation of valid SEPA Credit Transfer XML.
     *
     * @return void
     */
    public function testIsValidCreditTransfer(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.03">
                <CstmrCdtTrfInitn>
                    <GrpHdr>
                        <MsgId>MSG-001</MsgId>
                    </GrpHdr>
                </CstmrCdtTrfInitn>
            </Document>
            XML;

        $this->assertTrue($this->parser->isValidCreditTransfer($xml));
    }

    /**
     * Tests validation of invalid XML.
     *
     * @return void
     */
    public function testIsValidCreditTransferInvalid(): void
    {
        $this->assertFalse($this->parser->isValidCreditTransfer('Invalid XML'));
        $this->assertFalse($this->parser->isValidCreditTransfer('<xml></xml>'));
    }
}
