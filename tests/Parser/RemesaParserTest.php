<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Parser;

use Nowo\SepaPaymentBundle\Parser\RemesaParser;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;

use const E_ALL;
use const E_DEPRECATED;
use const E_USER_DEPRECATED;

/**
 * Tests for RemesaParser (deprecated API).
 *
 * Deprecation output is suppressed so CI passes on all matrix combinations
 * (PHP 8.1–8.5, Symfony 6.4 / 7.0 / 8.0) without risky tests.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class RemesaParserTest extends TestCase
{
    private RemesaParser $parser;

    protected function setUp(): void
    {
        $this->parser = new RemesaParser();
    }

    #[IgnoreDeprecations]
    public function testParseCreditTransfer(): void
    {
        set_error_handler(static function (int $errno): bool {
            return $errno === E_DEPRECATED || $errno === E_USER_DEPRECATED;
        }, E_ALL);

        try {
            $this->runParseCreditTransfer();
        } finally {
            restore_error_handler();
        }
    }

    private function runParseCreditTransfer(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.03">
                <CstmrCdtTrfInitn>
                    <GrpHdr>
                        <MsgId>MSG-001</MsgId>
                        <CreDtTm>2024-01-15T10:00:00</CreDtTm>
                        <InitgPty><Nm>My Company</Nm></InitgPty>
                    </GrpHdr>
                    <PmtInf>
                        <PmtInfId>PMT-001</PmtInfId>
                        <NbOfTxs>1</NbOfTxs>
                        <CtrlSum>100.50</CtrlSum>
                        <CdtTrfTxInf>
                            <EndToEndId>E2E-001</EndToEndId>
                            <InstdAmt Ccy="EUR">100.50</InstdAmt>
                            <CdtrAcct><Id><IBAN>ES9121000418450200051332</IBAN></Id></CdtrAcct>
                            <Cdtr><Nm>John Doe</Nm></Cdtr>
                        </CdtTrfTxInf>
                    </PmtInf>
                </CstmrCdtTrfInitn>
            </Document>
            XML;

        $data = $this->parser->parseCreditTransfer($xml);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('messageId', $data);
        $this->assertEquals('MSG-001', $data['messageId']);
    }

    #[IgnoreDeprecations]
    public function testIsValidCreditTransfer(): void
    {
        set_error_handler(static function (int $errno): bool {
            return $errno === E_DEPRECATED || $errno === E_USER_DEPRECATED;
        }, E_ALL);

        try {
            $this->runIsValidCreditTransfer();
        } finally {
            restore_error_handler();
        }
    }

    private function runIsValidCreditTransfer(): void
    {
        $validXml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.03">
                <CstmrCdtTrfInitn>
                    <GrpHdr><MsgId>MSG-001</MsgId><InitgPty><Nm>X</Nm></InitgPty></GrpHdr>
                </CstmrCdtTrfInitn>
            </Document>
            XML;
        $this->assertTrue($this->parser->isValidCreditTransfer($validXml));
        $this->assertFalse($this->parser->isValidCreditTransfer('<invalid>'));
    }
}
