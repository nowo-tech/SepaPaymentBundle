<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Parser;

use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator;
use Nowo\SepaPaymentBundle\Parser\CreditTransferParser;
use Nowo\SepaPaymentBundle\Parser\DirectDebitParser;
use Nowo\SepaPaymentBundle\Tests\Unit\Helper\TranslationHelper;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Round trip: XML written by the generators (pain.001.001.09 / pain.008.001.09) is read back by the parsers,
 * and the parsers keep reading the 2009 versions (pain.001.001.03 / pain.008.001.02).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class GeneratorParserRoundTripTest extends TestCase
{
    /**
     * Regression: the credit transfer parser used to return no transactions for the generator's own output.
     */
    public function testCreditTransferGenerateParseRoundTrip(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(TranslationHelper::createTranslatorCallback());
        $generator = new CreditTransferGenerator(new IbanValidator(), $translator);

        $xml = $generator->generateFromArray([
            'reference'              => 'MSG-RT-001',
            'initiatingPartyName'    => 'My Company',
            'paymentInfoId'          => 'PMT-RT-001',
            'requestedExecutionDate' => '2024-01-20',
            'debtorName'             => 'My Company Name',
            'debtorIban'             => 'ES9121000418450200051332',
            'debtorBic'              => 'CAIXESBBXXX',
            'transactions'           => [
                ['amount' => 15000.00, 'creditorIban' => 'GB82WEST12345698765432', 'creditorName' => 'John Doe', 'creditorBic' => 'NWBKGB2LXXX', 'endToEndId' => 'E2E-1', 'remittanceInformation' => 'Invoice 1'],
                ['amountCents' => 1999, 'creditorIban' => 'DE89370400440532013000', 'creditorName' => 'Jane Roe', 'endToEndId' => 'E2E-2'],
            ],
        ]);

        $this->assertStringContainsString(CreditTransferParser::NAMESPACE_001_001_09, $xml);

        $parser = new CreditTransferParser();
        $this->assertTrue($parser->isValidCreditTransfer($xml));
        $parsed = $parser->parseCreditTransfer($xml);

        $this->assertSame('MSG-RT-001', $parsed['messageId']);
        $this->assertSame('My Company', $parsed['initiatingPartyName']);
        $this->assertSame('PMT-RT-001', $parsed['paymentInfoId']);
        $this->assertSame(2, $parsed['numberOfTransactions']);
        $this->assertSame(15019.99, $parsed['controlSum']);
        $this->assertSame([
            ['endToEndId' => 'E2E-1', 'amount' => 15000.0, 'currency' => 'EUR', 'iban' => 'GB82WEST12345698765432', 'name' => 'John Doe', 'remittanceInformation' => 'Invoice 1'],
            ['endToEndId' => 'E2E-2', 'amount' => 19.99, 'currency' => 'EUR', 'iban' => 'DE89370400440532013000', 'name' => 'Jane Roe'],
        ], $parsed['transactions']);
    }

    /**
     * Regression: the direct debit parser used to return no transactions for the generator's own output.
     */
    public function testDirectDebitGenerateParseRoundTrip(): void
    {
        $generator = new DirectDebitGenerator(new IbanValidator());

        $xml = $generator->generateFromArray([
            'reference'           => 'MSG-RT-002',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-RT-002',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'creditorBic'         => 'CAIXESBBXXX',
            'seqType'             => 'RCUR',
            'creditorId'          => 'ES59ZZZX1234567',
            'localInstrumentCode' => 'CORE',
            'creditorAddress'     => ['street' => 'Calle Mayor 1', 'city' => 'Madrid', 'postalCode' => '28001', 'country' => 'ES'],
            'transactions'        => [
                [
                    'amount'                => 15000.00,
                    'debtorIban'            => 'GB82WEST12345698765432',
                    'debtorBic'             => 'NWBKGB2LXXX',
                    'debtorName'            => 'John Doe',
                    'debtorMandate'         => 'MANDATE-001',
                    'debtorMandateSignDate' => '2023-12-01',
                    'endToEndId'            => 'E2E-1',
                    'remittanceInformation' => 'Invoice 1',
                    'debtorAddress'         => ['street' => 'High Street 2', 'city' => 'London', 'postalCode' => 'SW1A 1AA', 'country' => 'GB'],
                ],
                [
                    'amountCents'           => 2550,
                    'debtorIban'            => 'DE89370400440532013000',
                    'debtorName'            => 'Jane Roe',
                    'debtorMandate'         => 'MANDATE-002',
                    'debtorMandateSignDate' => '2023-11-15',
                    'endToEndId'            => 'E2E-2',
                ],
            ],
        ]);

        $this->assertStringContainsString(DirectDebitParser::NAMESPACE_008_001_09, $xml);

        $parser = new DirectDebitParser();
        $this->assertTrue($parser->isValidDirectDebit($xml));
        $parsed = $parser->parseDirectDebit($xml);

        $this->assertSame('MSG-RT-002', $parsed['messageId']);
        $this->assertSame(2, $parsed['numberOfTransactions']);
        $this->assertSame(15025.5, $parsed['controlSum']);
        $this->assertSame('RCUR', $parsed['sequenceType']);
        $this->assertSame('2024-01-20', $parsed['dueDate']);
        $this->assertSame('My Company Name', $parsed['creditorName']);
        $this->assertSame('ES9121000418450200051332', $parsed['creditorIban']);
        $this->assertSame('ES59ZZZX1234567', $parsed['creditorId']);
        $this->assertSame('CORE', $parsed['localInstrumentCode']);
        $this->assertSame(['street' => 'Calle Mayor 1', 'city' => 'Madrid', 'postalCode' => '28001', 'country' => 'ES'], $parsed['creditorAddress']);

        $this->assertCount(2, $parsed['transactions']);
        [$first, $second] = $parsed['transactions'];

        $this->assertSame('E2E-1', $first['endToEndId']);
        $this->assertSame(15000.0, $first['amount']);
        $this->assertSame('EUR', $first['currency']);
        $this->assertSame('MANDATE-001', $first['mandateId']);
        $this->assertSame('2023-12-01', $first['mandateSignDate']);
        $this->assertSame('John Doe', $first['debtorName']);
        $this->assertSame('GB82WEST12345698765432', $first['debtorIban']);
        $this->assertSame('NWBKGB2LXXX', $first['debtorBic']);
        $this->assertSame('Invoice 1', $first['remittanceInformation']);
        $this->assertSame(['street' => 'High Street 2', 'city' => 'London', 'postalCode' => 'SW1A 1AA', 'country' => 'GB'], $first['debtorAddress']);

        $this->assertSame('E2E-2', $second['endToEndId']);
        $this->assertSame(25.5, $second['amount']);
        $this->assertSame('DE89370400440532013000', $second['debtorIban']);
        $this->assertSame('MANDATE-002', $second['mandateId']);
    }

    /**
     * 2009 versions keep working (BIC element name differs from the 2019 BICFI).
     */
    public function testParsersStillReadPain00100103AndPain00800102(): void
    {
        $ct = '<?xml version="1.0" encoding="UTF-8"?><Document xmlns="' . CreditTransferParser::NAMESPACE_001_001_03 . '"><CstmrCdtTrfInitn>'
            . '<GrpHdr><MsgId>MSG-03</MsgId></GrpHdr><PmtInf><CdtTrfTxInf><PmtId><EndToEndId>E2E-03</EndToEndId></PmtId>'
            . '<Amt><InstdAmt Ccy="EUR">10.50</InstdAmt></Amt><Cdtr><Nm>John</Nm></Cdtr><CdtrAcct><Id><IBAN>GB82WEST12345698765432</IBAN></Id></CdtrAcct>'
            . '</CdtTrfTxInf></PmtInf></CstmrCdtTrfInitn></Document>';
        $ctParser = new CreditTransferParser();
        $this->assertTrue($ctParser->isValidCreditTransfer($ct));
        $this->assertSame(
            [['endToEndId' => 'E2E-03', 'amount' => 10.5, 'currency' => 'EUR', 'iban' => 'GB82WEST12345698765432', 'name' => 'John']],
            $ctParser->parseCreditTransfer($ct)['transactions'],
        );

        $dd = '<?xml version="1.0" encoding="UTF-8"?><Document xmlns="' . DirectDebitParser::NAMESPACE_008_001_02 . '"><CstmrDrctDbtInitn>'
            . '<GrpHdr><MsgId>MSG-02</MsgId></GrpHdr><PmtInf><DrctDbtTxInf><PmtId><EndToEndId>E2E-02</EndToEndId></PmtId>'
            . '<InstdAmt Ccy="EUR">20.00</InstdAmt><DbtrAgt><FinInstnId><BIC>NWBKGB2LXXX</BIC></FinInstnId></DbtrAgt>'
            . '<Dbtr><Nm>Jane</Nm></Dbtr><DbtrAcct><Id><IBAN>GB82WEST12345698765432</IBAN></Id></DbtrAcct>'
            . '</DrctDbtTxInf></PmtInf></CstmrDrctDbtInitn></Document>';
        $ddParser = new DirectDebitParser();
        $this->assertTrue($ddParser->isValidDirectDebit($dd));
        $transaction = $ddParser->parseDirectDebit($dd)['transactions'][0];
        $this->assertSame(20.0, $transaction['amount']);
        $this->assertSame('NWBKGB2LXXX', $transaction['debtorBic']);
        $this->assertSame('GB82WEST12345698765432', $transaction['debtorIban']);
    }

    /**
     * Documents in an unsupported namespace (e.g. pain.001.001.05, or a credit transfer given to the
     * direct debit parser) are not recognised.
     */
    public function testUnsupportedNamespacesAreNotRecognised(): void
    {
        $ct05 = '<Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.05"><CstmrCdtTrfInitn><GrpHdr><MsgId>M</MsgId></GrpHdr></CstmrCdtTrfInitn></Document>';
        $this->assertFalse((new CreditTransferParser())->isValidCreditTransfer($ct05));
        $this->assertSame(['transactions' => []], (new CreditTransferParser())->parseCreditTransfer($ct05));

        $ct09 = '<Document xmlns="' . CreditTransferParser::NAMESPACE_001_001_09 . '"><CstmrCdtTrfInitn><GrpHdr><MsgId>M</MsgId></GrpHdr></CstmrCdtTrfInitn></Document>';
        $this->assertFalse((new DirectDebitParser())->isValidDirectDebit($ct09));
    }
}
