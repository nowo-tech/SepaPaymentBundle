<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Parser;

use DOMDocument;
use DOMNode;
use DOMXPath;
use ErrorException;
use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Parser\DirectDebitParser;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use stdClass;

/**
 * Test cases for DirectDebitParser.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class DirectDebitParserTest extends TestCase
{
    /**
     * Direct debit parser instance.
     */
    private DirectDebitParser $parser;

    /**
     * Sets up the test environment.
     */
    protected function setUp(): void
    {
        $this->parser = new DirectDebitParser();
    }

    /**
     * Tests parsing a valid SEPA Direct Debit XML.
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
     */
    public function testParseInvalidXml(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid XML format');

        $this->parser->parseDirectDebit('Invalid XML');
    }

    /**
     * Tests validation of valid SEPA Direct Debit XML.
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
     */
    public function testIsValidDirectDebitInvalid(): void
    {
        $this->assertFalse($this->parser->isValidDirectDebit('Invalid XML'));
        $this->assertFalse($this->parser->isValidDirectDebit('<xml></xml>'));
    }

    /**
     * Covers isValidDirectDebit when XML has correct namespace but missing CstmrDrctDbtInitn (returns false).
     */
    public function testIsValidDirectDebitReturnsFalseWhenMissingCstmrDrctDbtInitn(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">
                <OtherRoot>
                    <GrpHdr><MsgId>MSG-1</MsgId></GrpHdr>
                </OtherRoot>
            </Document>
            XML;
        $this->assertFalse($this->parser->isValidDirectDebit($xml));
    }

    /**
     * Tests parsing Direct Debit XML with partial address information.
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

    /**
     * Tests parsing Direct Debit XML with different sequence types.
     */
    public function testParseDirectDebitWithDifferentSequenceTypes(): void
    {
        $sequenceTypes = ['FRST', 'RCUR', 'OOFF', 'FNAL'];

        foreach ($sequenceTypes as $seqType) {
            $xml = <<<XML
                <?xml version="1.0" encoding="UTF-8"?>
                <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">
                    <CstmrDrctDbtInitn>
                        <GrpHdr>
                            <MsgId>MSG-{$seqType}</MsgId>
                            <CreDtTm>2024-01-15T10:00:00</CreDtTm>
                            <InitgPty>
                                <Nm>My Company</Nm>
                            </InitgPty>
                        </GrpHdr>
                        <PmtInf>
                            <PmtInfId>PMT-{$seqType}</PmtInfId>
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
                                <SeqTp>{$seqType}</SeqTp>
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
            $this->assertEquals($seqType, $data['sequenceType'], "Failed to parse sequence type: {$seqType}");
        }
    }

    /**
     * Tests parsing Direct Debit XML without BIC.
     */
    public function testParseDirectDebitWithoutBic(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">
                <CstmrDrctDbtInitn>
                    <GrpHdr>
                        <MsgId>MSG-005</MsgId>
                        <CreDtTm>2024-01-15T10:00:00</CreDtTm>
                        <InitgPty>
                            <Nm>My Company</Nm>
                        </InitgPty>
                    </GrpHdr>
                    <PmtInf>
                        <PmtInfId>PMT-005</PmtInfId>
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

        $this->assertArrayNotHasKey('debtorBic', $data['transactions'][0]);
    }

    /**
     * Tests parsing Direct Debit XML without remittance information.
     */
    public function testParseDirectDebitWithoutRemittanceInformation(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">
                <CstmrDrctDbtInitn>
                    <GrpHdr>
                        <MsgId>MSG-006</MsgId>
                        <CreDtTm>2024-01-15T10:00:00</CreDtTm>
                        <InitgPty>
                            <Nm>My Company</Nm>
                        </InitgPty>
                    </GrpHdr>
                    <PmtInf>
                        <PmtInfId>PMT-006</PmtInfId>
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

        $this->assertArrayNotHasKey('remittanceInformation', $data['transactions'][0]);
    }

    /**
     * Tests parsing Direct Debit XML with B2B local instrument code.
     */
    public function testParseDirectDebitWithB2BInstrumentCode(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">
                <CstmrDrctDbtInitn>
                    <GrpHdr>
                        <MsgId>MSG-007</MsgId>
                        <CreDtTm>2024-01-15T10:00:00</CreDtTm>
                        <InitgPty>
                            <Nm>My Company</Nm>
                        </InitgPty>
                    </GrpHdr>
                    <PmtInf>
                        <PmtInfId>PMT-007</PmtInfId>
                        <PmtMtd>DD</PmtMtd>
                        <NbOfTxs>1</NbOfTxs>
                        <CtrlSum>100.50</CtrlSum>
                        <PmtTpInf>
                            <SvcLvl>
                                <Cd>SEPA</Cd>
                            </SvcLvl>
                            <LclInstrm>
                                <Cd>B2B</Cd>
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

        $this->assertEquals('B2B', $data['localInstrumentCode']);
    }

    /**
     * Tests parsing Direct Debit XML with optional fields missing.
     */
    public function testParseDirectDebitWithOptionalFieldsMissing(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">
                <CstmrDrctDbtInitn>
                    <GrpHdr>
                        <MsgId>MSG-008</MsgId>
                        <CreDtTm>2024-01-15T10:00:00</CreDtTm>
                    </GrpHdr>
                    <PmtInf>
                        <PmtInfId>PMT-008</PmtInfId>
                        <PmtMtd>DD</PmtMtd>
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

        $this->assertEquals('MSG-008', $data['messageId']);
        $this->assertArrayNotHasKey('initiatingPartyName', $data);
        $this->assertArrayNotHasKey('numberOfTransactions', $data);
        $this->assertArrayNotHasKey('controlSum', $data);
        $this->assertArrayNotHasKey('creditorName', $data);
        $this->assertCount(1, $data['transactions']);
        $this->assertArrayNotHasKey('debtorName', $data['transactions'][0]);
    }

    /**
     * Tests isValidDirectDebit with valid XML but wrong namespace.
     */
    public function testIsValidDirectDebitWithWrongNamespace(): void
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

        $this->assertFalse($this->parser->isValidDirectDebit($xml));
    }

    /**
     * Tests isValidDirectDebit with empty XML.
     */
    public function testIsValidDirectDebitWithEmptyXml(): void
    {
        // Empty string should return false (loadXML will fail)
        $result1 = $this->parser->isValidDirectDebit('');
        $this->assertFalse($result1);

        // Whitespace only should return false
        $result2 = $this->parser->isValidDirectDebit('   ');
        $this->assertFalse($result2);
    }

    /**
     * Covers isValidDirectDebit catch block when an exception is thrown during parsing.
     */
    public function testIsValidDirectDebitReturnsFalseWhenExceptionDuringParsing(): void
    {
        $previous = set_error_handler(static function (int $errno, string $errstr): bool {
            throw new ErrorException($errstr, 0, $errno);
        });

        try {
            $xmlWithUndefinedEntity = '<?xml version="1.0"?><!DOCTYPE root [<!ELEMENT root (#PCDATA)>]><root>&undefined;</root>';
            $result                 = $this->parser->isValidDirectDebit($xmlWithUndefinedEntity);
            $this->assertFalse($result);
        } finally {
            restore_error_handler();
            if ($previous) {
                set_error_handler($previous);
            }
        }
    }

    /**
     * Tests parsing Direct Debit with all optional fields missing.
     */
    public function testParseDirectDebitWithMinimalData(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">
                <CstmrDrctDbtInitn>
                    <GrpHdr>
                        <MsgId>MSG-MIN-001</MsgId>
                        <CreDtTm>2024-01-15T10:00:00</CreDtTm>
                    </GrpHdr>
                    <PmtInf>
                        <PmtInfId>PMT-MIN-001</PmtInfId>
                        <PmtMtd>DD</PmtMtd>
                        <DrctDbtTxInf>
                            <EndToEndId>E2E-MIN-001</EndToEndId>
                            <InstdAmt Ccy="EUR">100.50</InstdAmt>
                            <DrctDbtTx>
                                <MndtRltdInf>
                                    <MndtId>MANDATE-MIN-001</MndtId>
                                    <DtOfSgntr>2023-12-01</DtOfSgntr>
                                </MndtRltdInf>
                            </DrctDbtTx>
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

        $this->assertEquals('MSG-MIN-001', $data['messageId']);
        $this->assertCount(1, $data['transactions']);
        $this->assertEquals('E2E-MIN-001', $data['transactions'][0]['endToEndId']);
        $this->assertEquals('MANDATE-MIN-001', $data['transactions'][0]['mandateId']);
    }

    /**
     * Covers the defensive continue when a transaction node is not a DOMNode (getDirectDebitTransactionNodes is overridden to return non-DOMNode).
     */
    public function testParseSkipsNonDomNodeInTransactionNodes(): void
    {
        $parser = new class () extends DirectDebitParser {
            public function getDirectDebitTransactionNodes(\DOMXPath $xpath): iterable
            {
                return [new stdClass()];
            }
        };
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">
                <CstmrDrctDbtInitn>
                    <GrpHdr>
                        <MsgId>MSG-SKIP</MsgId>
                        <CreDtTm>2024-01-15T10:00:00</CreDtTm>
                        <InitgPty><Nm>Co</Nm></InitgPty>
                    </GrpHdr>
                    <PmtInf>
                        <PmtInfId>PMT-SKIP</PmtInfId>
                        <PmtMtd>DD</PmtMtd>
                        <NbOfTxs>0</NbOfTxs>
                        <CtrlSum>0</CtrlSum>
                        <ReqdColltnDt>2024-01-20</ReqdColltnDt>
                        <Cdtr><Nm>C</Nm></Cdtr>
                        <CdtrAcct><Id><IBAN>ES9121000418450200051332</IBAN></Id></CdtrAcct>
                        <CdtrSchmeId><Id><PrvtId><Othr><Id>ES1234567890123456789012</Id></Othr></PrvtId></Id></CdtrSchmeId>
                    </PmtInf>
                </CstmrDrctDbtInitn>
            </Document>
            XML;
        $data = $parser->parseDirectDebit($xml);
        $this->assertSame('MSG-SKIP', $data['messageId']);
        $this->assertCount(0, $data['transactions']);
    }

    /**
     * Covers getFirstNode (private) via reflection with global query (no context).
     */
    public function testGetFirstNodeViaReflectionGlobalQueryReturnsNode(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">
                <CstmrDrctDbtInitn><GrpHdr><MsgId>MSG-GLOBAL</MsgId></GrpHdr></CstmrDrctDbtInitn>
            </Document>
            XML;
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('sepa', 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02');

        $ref    = new ReflectionClass(DirectDebitParser::class);
        $method = $ref->getMethod('getFirstNode');
        $node   = $method->invoke($this->parser, $xpath, '//sepa:MsgId', null);
        $this->assertInstanceOf(DOMNode::class, $node);
        $this->assertSame('MSG-GLOBAL', $node->nodeValue);
    }

    /**
     * Covers getFirstNode (private) via reflection with context node.
     */
    public function testGetFirstNodeViaReflectionWithContext(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">
                <CstmrDrctDbtInitn><GrpHdr><MsgId>M1</MsgId></GrpHdr>
                <PmtInf><DrctDbtTxInf><EndToEndId>E2E-1</EndToEndId></DrctDbtTxInf></PmtInf>
                </CstmrDrctDbtInitn>
            </Document>
            XML;
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('sepa', 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02');
        $context = $xpath->query('//sepa:DrctDbtTxInf')->item(0);
        $this->assertInstanceOf(DOMNode::class, $context);

        $ref    = new ReflectionClass(DirectDebitParser::class);
        $method = $ref->getMethod('getFirstNode');
        $node   = $method->invoke($this->parser, $xpath, './/sepa:EndToEndId', $context);
        $this->assertInstanceOf(DOMNode::class, $node);
        $this->assertSame('E2E-1', $node->nodeValue);
    }

    /**
     * Covers getFirstNode (private) returning null when no match.
     */
    public function testGetFirstNodeViaReflectionReturnsNullWhenNoMatch(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">
                <CstmrDrctDbtInitn><GrpHdr><MsgId>M1</MsgId></GrpHdr></CstmrDrctDbtInitn>
            </Document>
            XML;
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('sepa', 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02');

        $ref    = new ReflectionClass(DirectDebitParser::class);
        $method = $ref->getMethod('getFirstNode');
        $node   = $method->invoke($this->parser, $xpath, '//sepa:NonExistent');
        $this->assertNull($node);
    }

    /**
     * Covers extractAddress (private) via reflection when PstlAdr is present.
     */
    public function testExtractAddressViaReflection(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">
                <Dbtr>
                    <PstlAdr>
                        <StrtNm>Street</StrtNm>
                        <TwnNm>City</TwnNm>
                        <PstCd>28001</PstCd>
                        <Ctry>ES</Ctry>
                    </PstlAdr>
                </Dbtr>
            </Document>
            XML;
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('sepa', 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02');
        $parent = $xpath->query('//sepa:Dbtr')->item(0);
        $this->assertInstanceOf(DOMNode::class, $parent);

        $ref    = new ReflectionClass(DirectDebitParser::class);
        $method = $ref->getMethod('extractAddress');
        $address = $method->invoke($this->parser, $xpath, $parent);
        $this->assertSame('Street', $address['street'] ?? null);
        $this->assertSame('City', $address['city'] ?? null);
        $this->assertSame('28001', $address['postalCode'] ?? null);
        $this->assertSame('ES', $address['country'] ?? null);
    }

    /**
     * Covers extractAddress (private) when parent has no PstlAdr (empty address).
     */
    public function testExtractAddressViaReflectionReturnsEmptyWhenNoPstlAdr(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">
                <Dbtr><Nm>Name</Nm></Dbtr>
            </Document>
            XML;
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('sepa', 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02');
        $parent = $xpath->query('//sepa:Dbtr')->item(0);
        $this->assertInstanceOf(DOMNode::class, $parent);

        $ref    = new ReflectionClass(DirectDebitParser::class);
        $method = $ref->getMethod('extractAddress');
        $address = $method->invoke($this->parser, $xpath, $parent);
        $this->assertSame([], $address);
    }
}
