<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Validator;

use Nowo\SepaPaymentBundle\Validator\XsdValidator;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for XsdValidator.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class XsdValidatorTest extends TestCase
{
    /**
     * XSD validator instance.
     *
     * @var XsdValidator
     */
    private XsdValidator $validator;

    /**
     * Sets up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->validator = new XsdValidator();
    }

    /**
     * Tests validation of valid Credit Transfer XML (without XSD file).
     *
     * @return void
     */
    public function testValidateCreditTransferWithoutXsdFile(): void
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
                        </CdtTrfTxInf>
                    </PmtInf>
                </CstmrCdtTrfInitn>
            </Document>
            XML;

        // Should return true even without XSD file (validation skipped)
        $result = $this->validator->validateCreditTransfer($xml);
        $this->assertTrue($result);
    }

    /**
     * Tests validation of valid Direct Debit XML (without XSD file).
     *
     * @return void
     */
    public function testValidateDirectDebitWithoutXsdFile(): void
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

        // Should return true even without XSD file (validation skipped)
        $result = $this->validator->validateDirectDebit($xml);
        $this->assertTrue($result);
    }

    /**
     * Tests validation of invalid XML format.
     *
     * @return void
     */
    public function testValidateInvalidXml(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid XML format');

        $this->validator->validate('Invalid XML');
    }

    /**
     * Tests validation of malformed XML.
     *
     * @return void
     */
    public function testValidateMalformedXml(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid XML format');

        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Document>
                <UnclosedTag>
            </Document>
            XML;

        $this->validator->validate($xml);
    }

    /**
     * Tests validation with non-existent XSD file path.
     *
     * @return void
     */
    public function testValidateWithNonExistentXsdFile(): void
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

        // Should return true when XSD file doesn't exist (validation skipped)
        $result = $this->validator->validate($xml, '/non/existent/path.xsd');
        $this->assertTrue($result);
    }

    /**
     * Tests validation against schema string.
     *
     * @return void
     */
    public function testValidateAgainstSchemaString(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <root>
                <element>test</element>
            </root>
            XML;

        $xsd = <<<'XSD'
            <?xml version="1.0" encoding="UTF-8"?>
            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:element name="root">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:element name="element" type="xs:string"/>
                        </xs:sequence>
                    </xs:complexType>
                </xs:element>
            </xs:schema>
            XSD;

        $result = $this->validator->validateAgainstSchemaString($xml, $xsd);
        $this->assertTrue($result);
    }

    /**
     * Tests validation against schema string with invalid XML.
     *
     * @return void
     */
    public function testValidateAgainstSchemaStringInvalidXml(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <root>
                <invalid>test</invalid>
            </root>
            XML;

        $xsd = <<<'XSD'
            <?xml version="1.0" encoding="UTF-8"?>
            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:element name="root">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:element name="element" type="xs:string"/>
                        </xs:sequence>
                    </xs:complexType>
                </xs:element>
            </xs:schema>
            XSD;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('XSD validation failed');

        $this->validator->validateAgainstSchemaString($xml, $xsd);
    }

    /**
     * Tests validation with different schema types.
     *
     * @return void
     */
    public function testValidateWithDifferentSchemaTypes(): void
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

        // Test credit_transfer type
        $result1 = $this->validator->validate($xml, null, 'credit_transfer');
        $this->assertTrue($result1);

        // Test direct_debit type (should still work, just uses different default path)
        $result2 = $this->validator->validate($xml, null, 'direct_debit');
        $this->assertTrue($result2);
    }
}
