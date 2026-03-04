<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Validator;

use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Validator\XsdValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Test cases for XsdValidator.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class XsdValidatorTest extends TestCase
{
    /**
     * XSD validator instance.
     */
    private XsdValidator $validator;

    /**
     * Sets up the test environment.
     */
    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            \Nowo\SepaPaymentBundle\Tests\Helper\TranslationHelper::createTranslatorCallback(),
        );
        $this->validator = new XsdValidator($translator);
    }

    /**
     * Tests validation of valid Credit Transfer XML (without XSD file).
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
     */
    public function testValidateInvalidXml(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^Invalid XML format/');

        $this->validator->validate('Invalid XML');
    }

    /**
     * Tests validation of malformed XML.
     */
    public function testValidateMalformedXml(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^Invalid XML format/');

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

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^XSD validation failed/');

        $this->validator->validateAgainstSchemaString($xml, $xsd);
    }

    /**
     * Tests validateAgainstSchemaString with non-well-formed XML (loadXML fails).
     */
    public function testValidateAgainstSchemaStringNonWellFormedXml(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid XML format|invalid_xml_format/');

        $xsd = <<<'XSD'
            <?xml version="1.0"?>
            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:element name="root" type="xs:string"/>
            </xs:schema>
            XSD;

        $this->validator->validateAgainstSchemaString('not xml at all <<<', $xsd);
    }

    /**
     * Tests validation with different schema types.
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

    /**
     * Tests validate with unknown schema type (getDefaultSchemaPath returns null, validation skipped).
     */
    public function testValidateWithUnknownSchemaType(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <root><element>test</element></root>
            XML;
        $result = $this->validator->validate($xml, null, 'unknown_schema_type');
        $this->assertTrue($result);
    }

    /**
     * Tests validate() when xsdPath is provided but file does not exist (validation skipped, returns true).
     */
    public function testValidateWithNonExistentXsdPathSkipsValidationAndReturnsTrue(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <root><element>test</element></root>
            XML;
        $result = $this->validator->validate($xml, '/non/existent/path.xsd', 'credit_transfer');
        $this->assertTrue($result);
    }

    /**
     * Tests validate() when xsdPath points to existing file and XML fails schema validation (covers throw branch).
     */
    public function testValidateWithExistingXsdFileAndInvalidXmlThrows(): void
    {
        $xsdContent = <<<'XSD'
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
        $tempXsd = sys_get_temp_dir() . '/sepa_test_' . uniqid() . '.xsd';
        file_put_contents($tempXsd, $xsdContent);

        try {
            $xml = <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <root>
                    <wrongelement>invalid</wrongelement>
                </root>
                XML;
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessageMatches('/XSD validation failed|xsd_validation_failed/');
            $this->validator->validate($xml, $tempXsd, 'credit_transfer');
        } finally {
            if (file_exists($tempXsd)) {
                unlink($tempXsd);
            }
        }
    }
}
