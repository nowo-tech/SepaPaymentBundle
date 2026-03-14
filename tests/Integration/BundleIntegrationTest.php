<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Integration;

use Nowo\SepaPaymentBundle\Tests\Kernel\TestKernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Integration tests: kernel boots with the bundle and services are available.
 * Follows the pattern from TwigInspectorBundle (BUNDLES_STANDARDS_PROMPT §7.1.2).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class BundleIntegrationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testKernelBoots(): void
    {
        self::bootKernel();
        $this->assertTrue(self::getContainer()->has('kernel'));
    }

    public function testBundleServicesAreRegistered(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $this->assertTrue(
            $container->has('nowo_sepa_payment.validator.iban_validator'),
            'IBAN validator (public) should be registered',
        );
        $this->assertTrue(
            $container->has('nowo_sepa_payment.converter.ccc_converter'),
            'CCC converter should be registered',
        );
        $this->assertTrue(
            $container->has('nowo_sepa_payment.generator.credit_transfer_generator'),
            'Credit transfer generator should be registered',
        );
        $this->assertTrue(
            $container->has('nowo_sepa_payment.generator.direct_debit_generator'),
            'Direct debit generator should be registered',
        );
        $this->assertTrue(
            $container->has('nowo_sepa_payment.parser.credit_transfer_parser'),
            'Credit transfer parser should be registered',
        );
        $this->assertTrue(
            $container->has('nowo_sepa_payment.parser.direct_debit_parser'),
            'Direct debit parser should be registered',
        );

        $application = new Application(self::$kernel);
        $this->assertTrue($application->has('nowo:sepa:validate-iban'), 'Validate IBAN command should be registered');
        $this->assertTrue($application->has('nowo:sepa:ccc-to-iban'), 'CCC to IBAN command should be registered');
        $this->assertTrue($application->has('sepa:validate-credit-card'), 'Validate credit card command should be registered');
        $this->assertTrue($application->has('nowo:sepa:parse-direct-debit'), 'Parse Direct Debit command should be registered');
    }

    public function testParseDirectDebitCommandRunsViaApplication(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('nowo:sepa:parse-direct-debit');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">
    <CstmrDrctDbtInitn>
        <GrpHdr>
            <MsgId>MSG-INT</MsgId>
            <CreDtTm>2024-01-15T10:00:00</CreDtTm>
            <InitgPty><Nm>Test</Nm></InitgPty>
        </GrpHdr>
        <PmtInf>
            <PmtInfId>PMT-001</PmtInfId>
            <PmtMtd>DD</PmtMtd>
            <ReqdColltnDt>2024-01-20</ReqdColltnDt>
            <Cdtr><Nm>Creditor</Nm></Cdtr>
            <CdtrAcct><Id><IBAN>ES9121000418450200051332</IBAN></Id></CdtrAcct>
            <CdtrSchmeId><Id><PrvtId><Othr><Id>ES98ZZZ</Id></Othr></PrvtId></Id></CdtrSchmeId>
            <DrctDbtTxInf>
                <PmtId><EndToEndId>E2E-001</EndToEndId></PmtId>
                <InstdAmt Ccy="EUR">50.00</InstdAmt>
                <MndtRltdInf><MndtId>MND-1</MndtId><DtOfSgntr>2023-01-01</DtOfSgntr></MndtRltdInf>
                <Dbtr><Nm>Debtor</Nm></Dbtr>
                <DbtrAcct><Id><IBAN>GB82WEST12345698765432</IBAN></Id></DbtrAcct>
            </DrctDbtTxInf>
        </PmtInf>
    </CstmrDrctDbtInitn>
</Document>';
        $tempFile = sys_get_temp_dir() . '/sepa_int_parse_' . uniqid() . '.xml';
        file_put_contents($tempFile, $xml);

        try {
            $tester = new CommandTester($command);
            $tester->execute(['file' => $tempFile]);
            $this->assertSame(0, $tester->getStatusCode());
            $this->assertStringContainsString('MSG-INT', $tester->getDisplay());
            $this->assertStringContainsString('E2E-001', $tester->getDisplay());

            $tester->execute(['file' => $tempFile, '--json' => true]);
            $this->assertSame(0, $tester->getStatusCode());
            $this->assertStringContainsString('"messageId"', $tester->getDisplay());
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function testValidateIbanCommandRunsWithValidIban(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('nowo:sepa:validate-iban');
        $tester = new CommandTester($command);
        $tester->execute(['iban' => 'ES9121000418450200051332']);
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testValidateIbanCommandFailsWithInvalidIban(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('nowo:sepa:validate-iban');
        $tester = new CommandTester($command);
        $tester->execute(['iban' => 'INVALID']);
        $this->assertNotSame(0, $tester->getStatusCode());
    }

    public function testCreditTransferGeneratorProducesValidXml(): void
    {
        self::bootKernel();
        $generator = self::getContainer()->get('nowo_sepa_payment.generator.credit_transfer_generator');
        $data = [
            'reference' => 'MSG-001',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'debtorIban' => 'ES9121000418450200051332',
            'debtorName' => 'My Company Name',
            'requestedExecutionDate' => '2024-01-20',
            'transactions' => [
                [
                    'amount' => 100.50,
                    'creditorIban' => 'GB82WEST12345698765432',
                    'creditorName' => 'John Doe',
                    'endToEndId' => 'E2E-001',
                ],
            ],
        ];
        $xml = $generator->generateFromArray($data);
        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
        $this->assertStringContainsString('MSG-001', $xml);
    }

    public function testDirectDebitGeneratorProducesValidXml(): void
    {
        self::bootKernel();
        $generator = self::getContainer()->get('nowo_sepa_payment.generator.direct_debit_generator');
        $data = [
            'reference' => 'MSG-001',
            'bankAccountOwner' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'dueDate' => '2024-01-20',
            'creditorName' => 'My Company Name',
            'creditorIban' => 'ES9121000418450200051332',
            'seqType' => 'FRST',
            'creditorId' => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
            'transactions' => [
                [
                    'amount' => 50.00,
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'Jane Doe',
                    'endToEndId' => 'E2E-002',
                    'debtorMandate' => 'MND-001',
                    'debtorMandateSignDate' => '2023-01-15',
                ],
            ],
        ];
        $xml = $generator->generateFromArray($data);
        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
        $this->assertStringContainsString('MSG-001', $xml);
    }

    public function testCreditTransferParserParsesGeneratedXml(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $generator = $container->get('nowo_sepa_payment.generator.credit_transfer_generator');
        $parser = $container->get('nowo_sepa_payment.parser.credit_transfer_parser');
        $data = [
            'reference' => 'MSG-INT-001',
            'initiatingPartyName' => 'Test',
            'paymentInfoId' => 'PMT-001',
            'debtorIban' => 'ES9121000418450200051332',
            'debtorName' => 'Creditor',
            'requestedExecutionDate' => '2024-01-20',
            'transactions' => [
                [
                    'amount' => 10.00,
                    'creditorIban' => 'GB82WEST12345698765432',
                    'creditorName' => 'Debtor',
                    'endToEndId' => 'E2E-INT-001',
                ],
            ],
        ];
        $xml = $generator->generateFromArray($data);
        $parsed = $parser->parseCreditTransfer($xml);
        $this->assertIsArray($parsed);
        $this->assertArrayHasKey('transactions', $parsed);
        $this->assertIsArray($parsed['transactions']);
    }

    public function testDirectDebitParserParsesGeneratedXml(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $generator = $container->get('nowo_sepa_payment.generator.direct_debit_generator');
        $parser = $container->get('nowo_sepa_payment.parser.direct_debit_parser');
        $data = [
            'reference' => 'MSG-INT-002',
            'bankAccountOwner' => 'Test',
            'paymentInfoId' => 'PMT-002',
            'dueDate' => '2024-01-20',
            'creditorName' => 'Creditor',
            'creditorIban' => 'ES9121000418450200051332',
            'seqType' => 'FRST',
            'creditorId' => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
            'transactions' => [
                [
                    'amount' => 25.00,
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'Debtor',
                    'endToEndId' => 'E2E-INT-002',
                    'debtorMandate' => 'MND-002',
                    'debtorMandateSignDate' => '2023-06-01',
                ],
            ],
        ];
        $xml = $generator->generateFromArray($data);
        $parsed = $parser->parseDirectDebit($xml);
        $this->assertIsArray($parsed);
        $this->assertArrayHasKey('transactions', $parsed);
        $this->assertIsArray($parsed['transactions']);
    }

    public function testIbanValidatorServiceIsUsable(): void
    {
        self::bootKernel();
        $validator = self::getContainer()->get('nowo_sepa_payment.validator.iban_validator');
        $this->assertTrue($validator->isValid('ES9121000418450200051332'));
        $this->assertFalse($validator->isValid('INVALID'));
    }

    public function testCccConverterServiceIsUsable(): void
    {
        self::bootKernel();
        $converter = self::getContainer()->get('nowo_sepa_payment.converter.ccc_converter');
        $iban = $converter->cccToIban('21000418450200051332');
        $this->assertStringStartsWith('ES', $iban);
        $this->assertTrue(strlen($iban) >= 20);
    }

    public function testCreditTransferGeneratorCreateResponse(): void
    {
        self::bootKernel();
        $generator = self::getContainer()->get('nowo_sepa_payment.generator.credit_transfer_generator');
        $data = [
            'reference' => 'MSG-RESP',
            'initiatingPartyName' => 'Co',
            'paymentInfoId' => 'PMT-RESP',
            'debtorIban' => 'ES9121000418450200051332',
            'debtorName' => 'Creditor',
            'requestedExecutionDate' => '2024-01-20',
            'transactions' => [
                ['amount' => 1.00, 'creditorIban' => 'GB82WEST12345698765432', 'creditorName' => 'D', 'endToEndId' => 'E2E-R'],
            ],
        ];
        $xml = $generator->generateFromArray($data);
        $response = $generator->createResponse($xml, 'credit-transfer.xml');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function testDirectDebitGeneratorCreateResponse(): void
    {
        self::bootKernel();
        $generator = self::getContainer()->get('nowo_sepa_payment.generator.direct_debit_generator');
        $data = [
            'reference' => 'MSG-RESP',
            'bankAccountOwner' => 'Co',
            'paymentInfoId' => 'PMT-RESP',
            'dueDate' => '2024-01-20',
            'creditorName' => 'Creditor',
            'creditorIban' => 'ES9121000418450200051332',
            'seqType' => 'FRST',
            'creditorId' => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
            'transactions' => [
                ['amount' => 1.00, 'debtorIban' => 'GB82WEST12345698765432', 'debtorName' => 'D', 'endToEndId' => 'E2E-R', 'debtorMandate' => 'M1', 'debtorMandateSignDate' => '2023-01-01'],
            ],
        ];
        $xml = $generator->generateFromArray($data);
        $response = $generator->createResponse($xml, 'direct-debit.xml');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
    }

    public function testExportServiceImportExportRoundTrip(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $exporter = $container->has('nowo_sepa_payment.exporter.export_service')
            ? $container->get('nowo_sepa_payment.exporter.export_service')
            : $container->get(\Nowo\SepaPaymentBundle\Exporter\ExportService::class);
        $data = ['messageId' => 'M1', 'transactions' => []];
        $json = $exporter->exportCreditTransferToJson($data, false);
        $this->assertJson($json);
        $decoded = $exporter->importCreditTransferFromJson($json);
        $this->assertSame('M1', $decoded['messageId']);
    }

    public function testMandateServiceFindActiveAndExpiredMandates(): void
    {
        self::bootKernel();
        $service = self::getContainer()->get(\Nowo\SepaPaymentBundle\Service\MandateService::class);
        $service->createMandate('M-INT-1', new \DateTimeImmutable('2024-01-01'), 'ES9121000418450200051332', 'Debtor', 'CORE', 'FRST');
        $active = $service->findActiveMandates();
        $this->assertIsArray($active);
        $this->assertGreaterThanOrEqual(1, count($active));
        $expired = $service->findExpiredMandates();
        $this->assertIsArray($expired);
    }
}
