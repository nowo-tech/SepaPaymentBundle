<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Parser;

use Nowo\SepaPaymentBundle\Parser\DirectDebitParser;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for DirectDebitParser.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class DirectDebitParserTest extends TestCase
{
    /**
     * Direct debit parser instance.
     *
     * @var DirectDebitParser
     */
    private DirectDebitParser $parser;

    /**
     * Sets up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->parser = new DirectDebitParser();
    }

    /**
     * Tests parsing a valid SEPA Direct Debit XML.
     *
     * @return void
     */
    public function testParseDirectDebit(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
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
                        <NbOfTxs>1</NbOfTxs>
                        <CtrlSum>100.50</CtrlSum>
                        <PmtTpInf>
                            <SvcLvl>
                                <Cd>SEPA</Cd>
                            </SvcLvl>
                            <LclInstrm>
                                <Cd>CORE</Cd>
                            </LclInstrm>
                            <SeqTp>FRST</SeqTp>
                        </PmtTpInf>
                        <ReqdColltnDt>2024-01-20</ReqdColltnDt>
                        <Cdtr>
                            <Nm>My Company Name</Nm>
                            <PstlAdr>
                                <StrtNm>123 Business Street</StrtNm>
                                <TwnNm>Madrid</TwnNm>
                                <PstCd>28001</PstCd>
                                <Ctry>ES</Ctry>
                            </PstlAdr>
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
                                        <Id>ES1234567890123456789012</Id>
                                    </Othr>
                                </PrvtId>
                            </Id>
                        </CdtrSchmeId>
                        <DrctDbtTxInf>
                            <PmtId>
                                <EndToEndId>E2E-001</EndToEndId>
                            </PmtId>
                            <InstdAmt Ccy="EUR">100.50</InstdAmt>
                            <MndtRltdInf>
                                <MndtId>MANDATE-001</MndtId>
                                <DtOfSgntr>2023-12-01</DtOfSgntr>
                            </MndtRltdInf>
                            <DbtrAgt>
                                <FinInstnId>
                                    <BIC>WESTGB22</BIC>
                                </FinInstnId>
                            </DbtrAgt>
                            <Dbtr>
                                <Nm>John Doe</Nm>
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
                            <RmtInf>
                                <Ustrd>Invoice 12345</Ustrd>
                            </RmtInf>
                        </DrctDbtTxInf>
                    </PmtInf>
                </CstmrDrctDbtInitn>
            </Document>
            XML;

        $data = $this->parser->parseDirectDebit($xml);

        // Group header
        $this->assertEquals('MSG-001', $data['messageId']);
        $this->assertEquals('2024-01-15T10:00:00', $data['creationDate']);
        $this->assertEquals('My Company', $data['initiatingPartyName']);

        // Payment information
        $this->assertEquals('PMT-001', $data['paymentInfoId']);
        $this->assertEquals('DD', $data['paymentMethod']);
        $this->assertEquals(1, $data['numberOfTransactions']);
        $this->assertEquals(100.50, $data['controlSum']);
        $this->assertEquals('FRST', $data['sequenceType']);
        $this->assertEquals('2024-01-20', $data['dueDate']);
        $this->assertEquals('CORE', $data['localInstrumentCode']);

        // Creditor information
        $this->assertEquals('My Company Name', $data['creditorName']);
        $this->assertEquals('ES9121000418450200051332', $data['creditorIban']);
        $this->assertEquals('ES1234567890123456789012', $data['creditorId']);

        // Creditor address
        $this->assertArrayHasKey('creditorAddress', $data);
        $this->assertEquals('123 Business Street', $data['creditorAddress']['street']);
        $this->assertEquals('Madrid', $data['creditorAddress']['city']);
        $this->assertEquals('28001', $data['creditorAddress']['postalCode']);
        $this->assertEquals('ES', $data['creditorAddress']['country']);

        // Transactions
        $this->assertCount(1, $data['transactions']);
        $transaction = $data['transactions'][0];
        $this->assertEquals('E2E-001', $transaction['endToEndId']);
        $this->assertEquals(100.50, $transaction['amount']);
        $this->assertEquals('EUR', $transaction['currency']);
        $this->assertEquals('MANDATE-001', $transaction['mandateId']);
        $this->assertEquals('2023-12-01', $transaction['mandateSignDate']);
        $this->assertEquals('John Doe', $transaction['debtorName']);
        $this->assertEquals('GB82WEST12345698765432', $transaction['debtorIban']);
        $this->assertEquals('WESTGB22', $transaction['debtorBic']);
        $this->assertEquals('Invoice 12345', $transaction['remittanceInformation']);

        // Debtor address
        $this->assertArrayHasKey('debtorAddress', $transaction);
        $this->assertEquals('456 Customer Avenue', $transaction['debtorAddress']['street']);
        $this->assertEquals('London', $transaction['debtorAddress']['city']);
        $this->assertEquals('SW1A 1AA', $transaction['debtorAddress']['postalCode']);
        $this->assertEquals('GB', $transaction['debtorAddress']['country']);
    }

    /**
     * Tests parsing Direct Debit XML with multiple transactions.
     *
     * @return void
     */
    public function testParseDirectDebitWithMultipleTransactions(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">
                <CstmrDrctDbtInitn>
                    <GrpHdr>
                        <MsgId>MSG-002</MsgId>
                        <CreDtTm>2024-01-15T10:00:00</CreDtTm>
                        <InitgPty>
                            <Nm>My Company</Nm>
                        </InitgPty>
                    </GrpHdr>
                    <PmtInf>
                        <PmtInfId>PMT-002</PmtInfId>
                        <PmtMtd>DD</PmtMtd>
                        <NbOfTxs>2</NbOfTxs>
                        <CtrlSum>250.75</CtrlSum>
                        <PmtTpInf>
                            <SvcLvl>
                                <Cd>SEPA</Cd>
                            </SvcLvl>
                            <LclInstrm>
                                <Cd>CORE</Cd>
                            </LclInstrm>
                            <SeqTp>RCUR</SeqTp>
                        </PmtTpInf>
                        <ReqdColltnDt>2024-01-20</ReqdColltnDt>
                        <Cdtr>
                            <Nm>My Company Name</Nm>
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
                                        <Id>ES1234567890123456789012</Id>
                                    </Othr>
                                </PrvtId>
                            </Id>
                        </CdtrSchmeId>
                        <DrctDbtTxInf>
                            <PmtId>
                                <EndToEndId>E2E-001</EndToEndId>
                            </PmtId>
                            <InstdAmt Ccy="EUR">100.50</InstdAmt>
                            <MndtRltdInf>
                                <MndtId>MANDATE-001</MndtId>
                                <DtOfSgntr>2023-12-01</DtOfSgntr>
                            </MndtRltdInf>
                            <Dbtr>
                                <Nm>John Doe</Nm>
                            </Dbtr>
                            <DbtrAcct>
                                <Id>
                                    <IBAN>GB82WEST12345698765432</IBAN>
                                </Id>
                            </DbtrAcct>
                        </DrctDbtTxInf>
                        <DrctDbtTxInf>
                            <PmtId>
                                <EndToEndId>E2E-002</EndToEndId>
                            </PmtId>
                            <InstdAmt Ccy="EUR">150.25</InstdAmt>
                            <MndtRltdInf>
                                <MndtId>MANDATE-002</MndtId>
                                <DtOfSgntr>2023-12-01</DtOfSgntr>
                            </MndtRltdInf>
                            <Dbtr>
                                <Nm>Jane Smith</Nm>
                            </Dbtr>
                            <DbtrAcct>
                                <Id>
                                    <IBAN>FR1420041010050500013M02606</IBAN>
                                </Id>
                            </DbtrAcct>
                        </DrctDbtTxInf>
                    </PmtInf>
                </CstmrDrctDbtInitn>
            </Document>
            XML;

        $data = $this->parser->parseDirectDebit($xml);

        $this->assertEquals('MSG-002', $data['messageId']);
        $this->assertEquals(2, $data['numberOfTransactions']);
        $this->assertEquals(250.75, $data['controlSum']);
        $this->assertCount(2, $data['transactions']);

        $this->assertEquals('E2E-001', $data['transactions'][0]['endToEndId']);
        $this->assertEquals(100.50, $data['transactions'][0]['amount']);
        $this->assertEquals('MANDATE-001', $data['transactions'][0]['mandateId']);
        $this->assertEquals('John Doe', $data['transactions'][0]['debtorName']);

        $this->assertEquals('E2E-002', $data['transactions'][1]['endToEndId']);
        $this->assertEquals(150.25, $data['transactions'][1]['amount']);
        $this->assertEquals('MANDATE-002', $data['transactions'][1]['mandateId']);
        $this->assertEquals('Jane Smith', $data['transactions'][1]['debtorName']);
    }

    /**
     * Tests parsing Direct Debit XML without addresses.
     *
     * @return void
     */
    public function testParseDirectDebitWithoutAddresses(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">
                <CstmrDrctDbtInitn>
                    <GrpHdr>
                        <MsgId>MSG-003</MsgId>
                        <CreDtTm>2024-01-15T10:00:00</CreDtTm>
                        <InitgPty>
                            <Nm>My Company</Nm>
                        </InitgPty>
                    </GrpHdr>
                    <PmtInf>
                        <PmtInfId>PMT-003</PmtInfId>
                        <PmtMtd>DD</PmtMtd>
                        <NbOfTxs>1</NbOfTxs>
                        <CtrlSum>100.50</CtrlSum>
                        <PmtTpInf>
                            <SvcLvl>
                                <Cd>SEPA</Cd>
                            </SvcLvl>
                            <LclInstrm>
                                <Cd>CORE</Cd>
                            </LclInstrm>
                            <SeqTp>FRST</SeqTp>
                        </PmtTpInf>
                        <ReqdColltnDt>2024-01-20</ReqdColltnDt>
                        <Cdtr>
                            <Nm>My Company Name</Nm>
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
                                        <Id>ES1234567890123456789012</Id>
                                    </Othr>
                                </PrvtId>
                            </Id>
                        </CdtrSchmeId>
                        <DrctDbtTxInf>
                            <PmtId>
                                <EndToEndId>E2E-001</EndToEndId>
                            </PmtId>
                            <InstdAmt Ccy="EUR">100.50</InstdAmt>
                            <MndtRltdInf>
                                <MndtId>MANDATE-001</MndtId>
                                <DtOfSgntr>2023-12-01</DtOfSgntr>
                            </MndtRltdInf>
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
            </Document>
            XML;

        $data = $this->parser->parseDirectDebit($xml);

        $this->assertArrayNotHasKey('creditorAddress', $data);
        $this->assertArrayNotHasKey('debtorAddress', $data['transactions'][0]);
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

        $this->parser->parseDirectDebit('Invalid XML');
    }

    /**
     * Tests validation of valid SEPA Direct Debit XML.
     *
     * @return void
     */
    public function testIsValidDirectDebit(): void
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

        $this->assertTrue($this->parser->isValidDirectDebit($xml));
    }

    /**
     * Tests validation of invalid XML.
     *
     * @return void
     */
    public function testIsValidDirectDebitInvalid(): void
    {
        $this->assertFalse($this->parser->isValidDirectDebit('Invalid XML'));
        $this->assertFalse($this->parser->isValidDirectDebit('<xml></xml>'));
    }

    /**
     * Tests parsing Direct Debit XML with partial address information.
     *
     * @return void
     */
    public function testParseDirectDebitWithPartialAddress(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">
                <CstmrDrctDbtInitn>
                    <GrpHdr>
                        <MsgId>MSG-004</MsgId>
                        <CreDtTm>2024-01-15T10:00:00</CreDtTm>
                        <InitgPty>
                            <Nm>My Company</Nm>
                        </InitgPty>
                    </GrpHdr>
                    <PmtInf>
                        <PmtInfId>PMT-004</PmtInfId>
                        <PmtMtd>DD</PmtMtd>
                        <NbOfTxs>1</NbOfTxs>
                        <CtrlSum>100.50</CtrlSum>
                        <PmtTpInf>
                            <SvcLvl>
                                <Cd>SEPA</Cd>
                            </SvcLvl>
                            <LclInstrm>
                                <Cd>CORE</Cd>
                            </LclInstrm>
                            <SeqTp>FRST</SeqTp>
                        </PmtTpInf>
                        <ReqdColltnDt>2024-01-20</ReqdColltnDt>
                        <Cdtr>
                            <Nm>My Company Name</Nm>
                            <PstlAdr>
                                <StrtNm>123 Business Street</StrtNm>
                                <TwnNm>Madrid</TwnNm>
                            </PstlAdr>
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
                                        <Id>ES1234567890123456789012</Id>
                                    </Othr>
                                </PrvtId>
                            </Id>
                        </CdtrSchmeId>
                        <DrctDbtTxInf>
                            <PmtId>
                                <EndToEndId>E2E-001</EndToEndId>
                            </PmtId>
                            <InstdAmt Ccy="EUR">100.50</InstdAmt>
                            <MndtRltdInf>
                                <MndtId>MANDATE-001</MndtId>
                                <DtOfSgntr>2023-12-01</DtOfSgntr>
                            </MndtRltdInf>
                            <Dbtr>
                                <Nm>John Doe</Nm>
                                <PstlAdr>
                                    <PstCd>SW1A 1AA</PstCd>
                                    <Ctry>GB</Ctry>
                                </PstlAdr>
                            </Dbtr>
                            <DbtrAcct>
                                <Id>
                                    <IBAN>GB82WEST12345698765432</IBAN>
                                </Id>
                            </DbtrAcct>
                        </DrctDbtTxInf>
                    </PmtInf>
                </CstmrDrctDbtInitn>
            </Document>
            XML;

        $data = $this->parser->parseDirectDebit($xml);

        $this->assertArrayHasKey('creditorAddress', $data);
        $this->assertEquals('123 Business Street', $data['creditorAddress']['street']);
        $this->assertEquals('Madrid', $data['creditorAddress']['city']);
        $this->assertArrayNotHasKey('postalCode', $data['creditorAddress']);
        $this->assertArrayNotHasKey('country', $data['creditorAddress']);

        $this->assertArrayHasKey('debtorAddress', $data['transactions'][0]);
        $this->assertArrayNotHasKey('street', $data['transactions'][0]['debtorAddress']);
        $this->assertArrayNotHasKey('city', $data['transactions'][0]['debtorAddress']);
        $this->assertEquals('SW1A 1AA', $data['transactions'][0]['debtorAddress']['postalCode']);
        $this->assertEquals('GB', $data['transactions'][0]['debtorAddress']['country']);
    }
}
