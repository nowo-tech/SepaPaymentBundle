<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Parser;

use Nowo\SepaPaymentBundle\Parser\CreditTransferParser;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for CreditTransferParser.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class CreditTransferParserTest extends TestCase
{
    /**
     * Credit transfer parser instance.
     *
     * @var CreditTransferParser
     */
    private CreditTransferParser $parser;

    /**
     * Sets up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->parser = new CreditTransferParser();
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

    /**
     * Tests parsing Credit Transfer XML with multiple transactions.
     *
     * @return void
     */
    public function testParseCreditTransferWithMultipleTransactions(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.03">
                <CstmrCdtTrfInitn>
                    <GrpHdr>
                        <MsgId>MSG-002</MsgId>
                        <CreDtTm>2024-01-15T10:00:00</CreDtTm>
                        <InitgPty>
                            <Nm>My Company</Nm>
                        </InitgPty>
                    </GrpHdr>
                    <PmtInf>
                        <PmtInfId>PMT-002</PmtInfId>
                        <NbOfTxs>2</NbOfTxs>
                        <CtrlSum>250.75</CtrlSum>
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
                        </CdtTrfTxInf>
                        <CdtTrfTxInf>
                            <EndToEndId>E2E-002</EndToEndId>
                            <InstdAmt Ccy="EUR">150.25</InstdAmt>
                            <CdtrAcct>
                                <Id>
                                    <IBAN>FR1420041010050500013M02606</IBAN>
                                </Id>
                            </CdtrAcct>
                            <Cdtr>
                                <Nm>Jane Smith</Nm>
                            </Cdtr>
                        </CdtTrfTxInf>
                    </PmtInf>
                </CstmrCdtTrfInitn>
            </Document>
            XML;

        $data = $this->parser->parseCreditTransfer($xml);

        $this->assertEquals('MSG-002', $data['messageId']);
        $this->assertEquals(2, $data['numberOfTransactions']);
        $this->assertEquals(250.75, $data['controlSum']);
        $this->assertCount(2, $data['transactions']);

        $this->assertEquals('E2E-001', $data['transactions'][0]['endToEndId']);
        $this->assertEquals(100.50, $data['transactions'][0]['amount']);
        $this->assertEquals('John Doe', $data['transactions'][0]['name']);

        $this->assertEquals('E2E-002', $data['transactions'][1]['endToEndId']);
        $this->assertEquals(150.25, $data['transactions'][1]['amount']);
        $this->assertEquals('Jane Smith', $data['transactions'][1]['name']);
    }

    /**
     * Tests parsing Credit Transfer XML with addresses.
     *
     * @return void
     */
    public function testParseCreditTransferWithAddresses(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.03">
                <CstmrCdtTrfInitn>
                    <GrpHdr>
                        <MsgId>MSG-003</MsgId>
                        <CreDtTm>2024-01-15T10:00:00</CreDtTm>
                        <InitgPty>
                            <Nm>My Company</Nm>
                        </InitgPty>
                    </GrpHdr>
                    <PmtInf>
                        <PmtInfId>PMT-003</PmtInfId>
                        <NbOfTxs>1</NbOfTxs>
                        <CtrlSum>100.50</CtrlSum>
                        <Cdtr>
                            <Nm>My Company Name</Nm>
                            <PstlAdr>
                                <StrtNm>123 Business Street</StrtNm>
                                <TwnNm>Madrid</TwnNm>
                                <PstCd>28001</PstCd>
                                <Ctry>ES</Ctry>
                            </PstlAdr>
                        </Cdtr>
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
                            <Dbtr>
                                <Nm>Jane Smith</Nm>
                                <PstlAdr>
                                    <StrtNm>456 Customer Avenue</StrtNm>
                                    <TwnNm>London</TwnNm>
                                    <PstCd>SW1A 1AA</PstCd>
                                    <Ctry>GB</Ctry>
                                </PstlAdr>
                            </Dbtr>
                            <DbtrAcct>
                                <Id>
                                    <IBAN>GB82WEST12345698765432</IBAN>
                                </Id>
                            </DbtrAcct>
                        </CdtTrfTxInf>
                    </PmtInf>
                </CstmrCdtTrfInitn>
            </Document>
            XML;

        $data = $this->parser->parseCreditTransfer($xml);

        // Note: CreditTransferParser doesn't currently extract addresses
        // This test verifies it doesn't break when addresses are present
        $this->assertEquals('MSG-003', $data['messageId']);
        $this->assertCount(1, $data['transactions']);
        $this->assertEquals('E2E-001', $data['transactions'][0]['endToEndId']);
    }

    /**
     * Tests parsing Credit Transfer XML with optional fields missing.
     *
     * @return void
     */
    public function testParseCreditTransferWithOptionalFieldsMissing(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.03">
                <CstmrCdtTrfInitn>
                    <GrpHdr>
                        <MsgId>MSG-004</MsgId>
                        <CreDtTm>2024-01-15T10:00:00</CreDtTm>
                    </GrpHdr>
                    <PmtInf>
                        <PmtInfId>PMT-004</PmtInfId>
                        <CdtTrfTxInf>
                            <EndToEndId>E2E-001</EndToEndId>
                            <InstdAmt Ccy="EUR">100.50</InstdAmt>
                            <CdtrAcct>
                                <Id>
                                    <IBAN>ES9121000418450200051332</IBAN>
                                </Id>
                            </CdtrAcct>
                        </CdtTrfTxInf>
                    </PmtInf>
                </CstmrCdtTrfInitn>
            </Document>
            XML;

        $data = $this->parser->parseCreditTransfer($xml);

        $this->assertEquals('MSG-004', $data['messageId']);
        $this->assertEquals('2024-01-15T10:00:00', $data['creationDate']);
        $this->assertArrayNotHasKey('initiatingPartyName', $data);
        $this->assertArrayNotHasKey('numberOfTransactions', $data);
        $this->assertArrayNotHasKey('controlSum', $data);
        $this->assertCount(1, $data['transactions']);
        $this->assertEquals('E2E-001', $data['transactions'][0]['endToEndId']);
        $this->assertArrayNotHasKey('name', $data['transactions'][0]);
        $this->assertArrayNotHasKey('remittanceInformation', $data['transactions'][0]);
    }

    /**
     * Tests parsing Credit Transfer XML with different currency.
     *
     * @return void
     */
    public function testParseCreditTransferWithDifferentCurrency(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.03">
                <CstmrCdtTrfInitn>
                    <GrpHdr>
                        <MsgId>MSG-005</MsgId>
                        <CreDtTm>2024-01-15T10:00:00</CreDtTm>
                        <InitgPty>
                            <Nm>My Company</Nm>
                        </InitgPty>
                    </GrpHdr>
                    <PmtInf>
                        <PmtInfId>PMT-005</PmtInfId>
                        <NbOfTxs>1</NbOfTxs>
                        <CtrlSum>100.50</CtrlSum>
                        <CdtTrfTxInf>
                            <EndToEndId>E2E-001</EndToEndId>
                            <InstdAmt Ccy="USD">100.50</InstdAmt>
                            <CdtrAcct>
                                <Id>
                                    <IBAN>ES9121000418450200051332</IBAN>
                                </Id>
                            </CdtrAcct>
                            <Cdtr>
                                <Nm>John Doe</Nm>
                            </Cdtr>
                        </CdtTrfTxInf>
                    </PmtInf>
                </CstmrCdtTrfInitn>
            </Document>
            XML;

        $data = $this->parser->parseCreditTransfer($xml);

        $this->assertEquals('USD', $data['transactions'][0]['currency']);
        $this->assertEquals(100.50, $data['transactions'][0]['amount']);
    }

    /**
     * Tests isValidCreditTransfer with valid XML but wrong namespace.
     *
     * @return void
     */
    public function testIsValidCreditTransferWithWrongNamespace(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">
                <CstmrDrctDbtInitn>
                    <GrpHdr>
                        <MsgId>MSG-001</MsgId>
                    </GrpHdr>
                </CstmrDrctDbtInitn>
            </Document>
            XML;

        $this->assertFalse($this->parser->isValidCreditTransfer($xml));
    }

    /**
     * Tests isValidCreditTransfer with empty XML.
     *
     * @return void
     */
    public function testIsValidCreditTransferWithEmptyXml(): void
    {
        // Empty string should return false (loadXML will fail)
        $result1 = $this->parser->isValidCreditTransfer('');
        $this->assertFalse($result1);

        // Whitespace only should return false
        $result2 = $this->parser->isValidCreditTransfer('   ');
        $this->assertFalse($result2);
    }

    /**
     * Tests isValidCreditTransfer with XML missing required elements.
     *
     * @return void
     */
    public function testIsValidCreditTransferMissingRequiredElements(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.03">
                <CstmrCdtTrfInitn>
                    <GrpHdr>
                        <!-- Missing MsgId -->
                    </GrpHdr>
                </CstmrCdtTrfInitn>
            </Document>
            XML;

        $this->assertFalse($this->parser->isValidCreditTransfer($xml));
    }

    /**
     * Tests isValidCreditTransfer when an exception is thrown during parsing (covers catch block).
     * Uses an error handler to convert a libxml warning into an exception.
     *
     * @return void
     */
    public function testIsValidCreditTransferReturnsFalseWhenExceptionDuringParsing(): void
    {
        $previous = set_error_handler(static function (int $errno, string $errstr): bool {
            throw new \ErrorException($errstr, 0, $errno);
        });
        try {
            $xmlWithUndefinedEntity = '<?xml version="1.0"?><!DOCTYPE root [<!ELEMENT root (#PCDATA)>]><root>&undefined;</root>';
            $result = $this->parser->isValidCreditTransfer($xmlWithUndefinedEntity);
            $this->assertFalse($result);
        } finally {
            restore_error_handler();
            if ($previous) {
                set_error_handler($previous);
            }
        }
    }

    /**
     * Tests parsing with BIC information.
     *
     * @return void
     */
    public function testParseCreditTransferWithBic(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.03">
                <CstmrCdtTrfInitn>
                    <GrpHdr>
                        <MsgId>MSG-BIC-001</MsgId>
                        <CreDtTm>2024-01-15T10:00:00</CreDtTm>
                        <InitgPty>
                            <Nm>My Company</Nm>
                        </InitgPty>
                    </GrpHdr>
                    <PmtInf>
                        <PmtInfId>PMT-BIC-001</PmtInfId>
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
                        <CdtTrfTxInf>
                            <EndToEndId>E2E-BIC-001</EndToEndId>
                            <InstdAmt Ccy="EUR">100.50</InstdAmt>
                            <CdtrAcct>
                                <Id>
                                    <IBAN>ES9121000418450200051332</IBAN>
                                </Id>
                            </CdtrAcct>
                            <Cdtr>
                                <Nm>John Doe</Nm>
                            </Cdtr>
                            <DbtrAcct>
                                <Id>
                                    <IBAN>GB82WEST12345698765432</IBAN>
                                </Id>
                            </DbtrAcct>
                            <DbtrAgt>
                                <FinInstnId>
                                    <BIC>WESTGB22</BIC>
                                </FinInstnId>
                            </DbtrAgt>
                        </CdtTrfTxInf>
                    </PmtInf>
                </CstmrCdtTrfInitn>
            </Document>
            XML;

        $data = $this->parser->parseCreditTransfer($xml);

        $this->assertEquals('MSG-BIC-001', $data['messageId']);
        $this->assertCount(1, $data['transactions']);
        $this->assertEquals('E2E-BIC-001', $data['transactions'][0]['endToEndId']);
    }
}
