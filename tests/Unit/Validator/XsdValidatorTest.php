<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Validator;

use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Tests\Unit\Helper\TranslationHelper;
use Nowo\SepaPaymentBundle\Validator\XsdValidator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
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
            TranslationHelper::createTranslatorCallback(),
        );
        $this->validator = new XsdValidator($translator);
    }

    /**
     * Regression: an incomplete pain.001.001.03 document used to "pass" because no schema was found; it must fail now.
     */
    public function testIncompleteCreditTransferFailsBundledSchema(): void
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

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^XSD validation failed: .*InitgPty/');

        $this->validator->validateCreditTransfer($xml);
    }

    /**
     * Regression: an incomplete pain.008.001.02 document used to "pass" because no schema was found; it must fail now.
     */
    public function testIncompleteDirectDebitFailsBundledSchema(): void
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

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^XSD validation failed: .*InitgPty/');

        $this->validator->validateDirectDebit($xml);
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

        // A missing schema must throw instead of silently skipping validation
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('XSD schema not found: /non/existent/path.xsd');

        $this->validator->validate($xml, '/non/existent/path.xsd');
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
     * A credit transfer document validated as direct debit uses the pain.008 default schema and fails.
     */
    public function testValidateWithMismatchedSchemaTypeFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^XSD validation failed/');

        $this->validator->validate(self::validCreditTransfer03(), null, 'direct_debit');
    }

    /**
     * Unknown schema type: there is no schema to validate against, so it throws.
     */
    public function testValidateWithUnknownSchemaTypeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('XSD schema not found: unknown_schema_type');

        $this->validator->validate('<root><element>test</element></root>', null, 'unknown_schema_type');
    }

    /**
     * A namespace of a pain version that is not bundled (e.g. pain.001.001.05) throws instead of skipping.
     */
    public function testValidateWithUnbundledPainVersionThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^XSD schema not found: .*pain\.001\.001\.05\.xsd$/');

        $this->validator->validate('<Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.05"/>', null, 'credit_transfer');
    }

    /**
     * The official pain.001.001.03 schema accepts a complete credit transfer document.
     */
    public function testValidCreditTransfer03PassesBundledSchema(): void
    {
        $this->assertTrue($this->validator->validateCreditTransfer(self::validCreditTransfer03()));
    }

    /**
     * The official pain.008.001.02 schema accepts a complete direct debit document.
     */
    public function testValidDirectDebit02PassesBundledSchema(): void
    {
        $this->assertTrue($this->validator->validateDirectDebit(self::validDirectDebit02()));
    }

    /**
     * Regression: PstlAdr children in the old order (StrtNm, TwnNm, PstCd) violate the ISO 20022 sequence.
     */
    public function testPostalAddressInWrongOrderFailsBundledSchema(): void
    {
        $xml = str_replace(
            '<PstCd>28001</PstCd><TwnNm>Madrid</TwnNm>',
            '<TwnNm>Madrid</TwnNm><PstCd>28001</PstCd>',
            self::validCreditTransfer03(),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^XSD validation failed: .*PstCd/');

        $this->validator->validateCreditTransfer($xml);
    }

    /**
     * Regression: the bundled schema directory is src/Resources/schemas and ships the official XSD files.
     */
    public function testBundledSchemasAreShipped(): void
    {
        $dir = XsdValidator::getSchemaDirectory();

        $this->assertSame(realpath(__DIR__ . '/../../../src/Resources/schemas'), realpath($dir));
        foreach (['pain.001.001.03', 'pain.008.001.02', 'pain.001.001.09', 'pain.008.001.09'] as $schema) {
            $file = $dir . '/' . $schema . '.xsd';
            $this->assertFileExists($file);
            $this->assertStringContainsString('targetNamespace="urn:iso:std:iso:20022:tech:xsd:' . $schema . '"', (string) file_get_contents($file));
        }
    }

    /**
     * Complete pain.001.001.03 credit transfer document with a structured postal address.
     */
    private static function validCreditTransfer03(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.03"><CstmrCdtTrfInitn>'
            . '<GrpHdr><MsgId>MSG-001</MsgId><CreDtTm>2024-01-15T10:00:00</CreDtTm><NbOfTxs>1</NbOfTxs><CtrlSum>15000.00</CtrlSum>'
            . '<InitgPty><Nm>My Company</Nm></InitgPty></GrpHdr>'
            . '<PmtInf><PmtInfId>PMT-001</PmtInfId><PmtMtd>TRF</PmtMtd><NbOfTxs>1</NbOfTxs><CtrlSum>15000.00</CtrlSum>'
            . '<PmtTpInf><SvcLvl><Cd>SEPA</Cd></SvcLvl></PmtTpInf><ReqdExctnDt>2024-01-20</ReqdExctnDt>'
            . '<Dbtr><Nm>My Company</Nm><PstlAdr><StrtNm>Calle Mayor 1</StrtNm><PstCd>28001</PstCd><TwnNm>Madrid</TwnNm><Ctry>ES</Ctry></PstlAdr></Dbtr>'
            . '<DbtrAcct><Id><IBAN>ES9121000418450200051332</IBAN></Id></DbtrAcct>'
            . '<DbtrAgt><FinInstnId><BIC>CAIXESBBXXX</BIC></FinInstnId></DbtrAgt><ChrgBr>SLEV</ChrgBr>'
            . '<CdtTrfTxInf><PmtId><EndToEndId>E2E-001</EndToEndId></PmtId><Amt><InstdAmt Ccy="EUR">15000.00</InstdAmt></Amt>'
            . '<Cdtr><Nm>John Doe</Nm></Cdtr><CdtrAcct><Id><IBAN>GB82WEST12345698765432</IBAN></Id></CdtrAcct></CdtTrfTxInf>'
            . '</PmtInf></CstmrCdtTrfInitn></Document>';
    }

    /**
     * Complete pain.008.001.02 direct debit document.
     */
    private static function validDirectDebit02(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02"><CstmrDrctDbtInitn>'
            . '<GrpHdr><MsgId>MSG-001</MsgId><CreDtTm>2024-01-15T10:00:00</CreDtTm><NbOfTxs>1</NbOfTxs><CtrlSum>100.50</CtrlSum>'
            . '<InitgPty><Nm>My Company</Nm></InitgPty></GrpHdr>'
            . '<PmtInf><PmtInfId>PMT-001</PmtInfId><PmtMtd>DD</PmtMtd><NbOfTxs>1</NbOfTxs><CtrlSum>100.50</CtrlSum>'
            . '<PmtTpInf><SvcLvl><Cd>SEPA</Cd></SvcLvl><LclInstrm><Cd>CORE</Cd></LclInstrm><SeqTp>FRST</SeqTp></PmtTpInf>'
            . '<ReqdColltnDt>2024-01-20</ReqdColltnDt>'
            . '<Cdtr><Nm>My Company Name</Nm><PstlAdr><StrtNm>Calle Mayor 1</StrtNm><PstCd>28001</PstCd><TwnNm>Madrid</TwnNm><Ctry>ES</Ctry></PstlAdr></Cdtr>'
            . '<CdtrAcct><Id><IBAN>ES9121000418450200051332</IBAN></Id></CdtrAcct>'
            . '<CdtrAgt><FinInstnId><BIC>CAIXESBBXXX</BIC></FinInstnId></CdtrAgt><ChrgBr>SLEV</ChrgBr>'
            . '<CdtrSchmeId><Id><PrvtId><Othr><Id>ES59ZZZX1234567</Id><SchmeNm><Prtry>SEPA</Prtry></SchmeNm></Othr></PrvtId></Id></CdtrSchmeId>'
            . '<DrctDbtTxInf><PmtId><EndToEndId>E2E-001</EndToEndId></PmtId><InstdAmt Ccy="EUR">100.50</InstdAmt>'
            . '<DrctDbtTx><MndtRltdInf><MndtId>MANDATE-001</MndtId><DtOfSgntr>2023-12-01</DtOfSgntr></MndtRltdInf></DrctDbtTx>'
            . '<DbtrAgt><FinInstnId><BIC>NWBKGB2LXXX</BIC></FinInstnId></DbtrAgt>'
            . '<Dbtr><Nm>John Doe</Nm></Dbtr><DbtrAcct><Id><IBAN>GB82WEST12345698765432</IBAN></Id></DbtrAcct></DrctDbtTxInf>'
            . '</PmtInf></CstmrDrctDbtInitn></Document>';
    }

    /**
     * Tests validate() when xsdPath points to existing file and XML is valid (covers return $valid branch).
     */
    public function testValidateWithExistingXsdFileAndValidXmlReturnsTrue(): void
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
        $tempXsd = sys_get_temp_dir() . '/sepa_valid_' . uniqid() . '.xsd';
        file_put_contents($tempXsd, $xsdContent);

        try {
            $xml = <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <root>
                    <element>valid</element>
                </root>
                XML;
            $result = $this->validator->validate($xml, $tempXsd, 'credit_transfer');
            $this->assertTrue($result);
        } finally {
            if (file_exists($tempXsd)) {
                unlink($tempXsd);
            }
        }
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

    /**
     * Tests getDefaultSchemaPath (private) via reflection to cover schema path resolution.
     */
    public function testGetDefaultSchemaPathViaReflection(): void
    {
        $ref    = new ReflectionClass(XsdValidator::class);
        $method = $ref->getMethod('getDefaultSchemaPath');

        $pathCt      = $method->invoke($this->validator, 'credit_transfer');
        $pathDd      = $method->invoke($this->validator, 'direct_debit');
        $pathUnknown = $method->invoke($this->validator, 'unknown_schema_type');

        $this->assertNull($pathUnknown, 'Unknown schema type should return null');
        $this->assertSame(XsdValidator::getSchemaDirectory() . '/pain.001.001.03.xsd', $pathCt);
        $this->assertSame(XsdValidator::getSchemaDirectory() . '/pain.008.001.02.xsd', $pathDd);
        $this->assertFileExists((string) $pathCt);
        $this->assertFileExists((string) $pathDd);

        // The document namespace selects the matching bundled version of the same message family only
        $ns = 'urn:iso:std:iso:20022:tech:xsd:';
        $this->assertStringEndsWith('/pain.001.001.09.xsd', (string) $method->invoke($this->validator, 'credit_transfer', $ns . 'pain.001.001.09'));
        $this->assertStringEndsWith('/pain.008.001.09.xsd', (string) $method->invoke($this->validator, 'direct_debit', $ns . 'pain.008.001.09'));
        $this->assertStringEndsWith('/pain.008.001.02.xsd', (string) $method->invoke($this->validator, 'direct_debit', $ns . 'pain.001.001.09'));
        $this->assertStringEndsWith('/pain.001.001.03.xsd', (string) $method->invoke($this->validator, 'credit_transfer', 'urn:example'));
    }

    /**
     * The process-wide libxml error mode must be restored after each call (worker mode keeps PHP globals between requests).
     */
    public function testLibxmlInternalErrorsModeIsRestored(): void
    {
        foreach ([true, false] as $previous) {
            $this->assertLibxmlModeRestored($previous);
        }
    }

    private function assertLibxmlModeRestored(bool $previous): void
    {
        $original = libxml_use_internal_errors($previous);
        $xsd      = <<<'XSD'
            <?xml version="1.0" encoding="UTF-8"?>
            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:element name="root" type="xs:string"/>
            </xs:schema>
            XSD;
        $tempXsd = sys_get_temp_dir() . '/sepa_mode_' . uniqid() . '.xsd';
        file_put_contents($tempXsd, $xsd);

        try {
            $this->validator->validateAgainstSchemaString('<root>ok</root>', $xsd);
            $this->assertSame($previous, libxml_use_internal_errors($previous));

            $this->validator->validate('<root>ok</root>', $tempXsd);
            $this->assertSame($previous, libxml_use_internal_errors($previous));

            try {
                $this->validator->validate('<root><unclosed></root>');
                $this->fail('Malformed XML must throw');
            } catch (InvalidArgumentException) {
                $this->assertSame($previous, libxml_use_internal_errors($previous));
            }

            try {
                $this->validator->validateAgainstSchemaString('<other/>', $xsd);
                $this->fail('Schema violation must throw');
            } catch (InvalidArgumentException) {
                $this->assertSame($previous, libxml_use_internal_errors($previous));
            }
        } finally {
            libxml_use_internal_errors($original);
            if (file_exists($tempXsd)) {
                unlink($tempXsd);
            }
        }
    }
}
