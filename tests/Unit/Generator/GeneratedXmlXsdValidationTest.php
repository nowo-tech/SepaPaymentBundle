<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Generator;

use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator;
use Nowo\SepaPaymentBundle\Tests\Unit\Helper\TranslationHelper;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Nowo\SepaPaymentBundle\Validator\XsdValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * End-to-end checks: XML produced by the generators validates against the bundled ISO 20022 XSD schemas
 * (validateXsd: true), including structured postal addresses.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class GeneratedXmlXsdValidationTest extends TestCase
{
    private TranslatorInterface $translator;

    private XsdValidator $xsdValidator;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(TranslationHelper::createTranslatorCallback());
        $this->translator   = $translator;
        $this->xsdValidator = new XsdValidator($translator);
    }

    /**
     * Credit transfer with addresses on both parties passes XSD validation inside the generator.
     */
    public function testGeneratedCreditTransferValidatesAgainstBundledXsd(): void
    {
        $generator = new CreditTransferGenerator(new IbanValidator(), $this->translator, $this->xsdValidator, true);

        $xml = $generator->generateFromArray([
            'reference'              => 'MSG-001',
            'initiatingPartyName'    => 'My Company',
            'paymentInfoId'          => 'PMT-001',
            'requestedExecutionDate' => '2024-01-20',
            'debtorName'             => 'My Company Name',
            'debtorIban'             => 'ES9121000418450200051332',
            'debtorBic'              => 'CAIXESBBXXX',
            'debtorAddress'          => ['street' => 'Calle Mayor 1', 'city' => 'Madrid', 'postalCode' => '28001', 'country' => 'ES'],
            'transactions'           => [
                [
                    'amount'          => 15000.00,
                    'creditorIban'    => 'GB82WEST12345698765432',
                    'creditorName'    => 'John Doe',
                    'creditorBic'     => 'NWBKGB2LXXX',
                    'endToEndId'      => 'E2E-001',
                    'creditorAddress' => ['street' => 'High Street 2', 'city' => 'London', 'postalCode' => 'SW1A 1AA', 'country' => 'GB'],
                ],
            ],
        ]);

        $this->assertTrue($this->xsdValidator->validateCreditTransfer($xml));
        $this->assertMatchesRegularExpression('#<PstlAdr>\s*<StrtNm>High Street 2</StrtNm>\s*<PstCd>SW1A 1AA</PstCd>\s*<TwnNm>London</TwnNm>\s*<Ctry>GB</Ctry>\s*</PstlAdr>#', $xml);
        $this->assertStringContainsString('<InstdAmt Ccy="EUR">15000.00</InstdAmt>', $xml);
    }

    /**
     * Direct debit with addresses on both parties passes XSD validation inside the generator.
     */
    public function testGeneratedDirectDebitValidatesAgainstBundledXsd(): void
    {
        $generator = new DirectDebitGenerator(new IbanValidator(), $this->xsdValidator, true);

        $xml = $generator->generateFromArray([
            'reference'           => 'MSG-001',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'creditorBic'         => 'CAIXESBBXXX',
            'seqType'             => 'FRST',
            'creditorId'          => 'ES59ZZZX1234567',
            'localInstrumentCode' => 'CORE',
            'creditorAddress'     => ['street' => 'Calle Mayor 1', 'city' => 'Madrid', 'postalCode' => '28001', 'country' => 'ES'],
            'transactions'        => [
                [
                    'amountCents'           => 1500000,
                    'debtorIban'            => 'GB82WEST12345698765432',
                    'debtorBic'             => 'NWBKGB2LXXX',
                    'debtorName'            => 'John Doe',
                    'debtorMandate'         => 'MANDATE-001',
                    'debtorMandateSignDate' => '2023-12-01',
                    'endToEndId'            => 'E2E-001',
                    'debtorAddress'         => ['street' => 'High Street 2', 'city' => 'London', 'postalCode' => 'SW1A 1AA', 'country' => 'GB'],
                ],
            ],
        ]);

        $this->assertTrue($this->xsdValidator->validateDirectDebit($xml));
        $this->assertMatchesRegularExpression('#<PstlAdr>\s*<StrtNm>Calle Mayor 1</StrtNm>\s*<PstCd>28001</PstCd>\s*<TwnNm>Madrid</TwnNm>\s*<Ctry>ES</Ctry>\s*</PstlAdr>#', $xml);
        $this->assertStringContainsString('<InstdAmt Ccy="EUR">15000.00</InstdAmt>', $xml);
    }

    /**
     * Generated XML that is tampered with (required element removed) fails XSD validation.
     */
    public function testTamperedGeneratedXmlFailsXsdValidation(): void
    {
        $generator = new DirectDebitGenerator(new IbanValidator());
        $xml       = $generator->generateFromArray([
            'reference'           => 'MSG-001',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'creditorBic'         => 'CAIXESBBXXX',
            'seqType'             => 'FRST',
            'creditorId'          => 'ES59ZZZX1234567',
            'localInstrumentCode' => 'CORE',
            'transactions'        => [
                ['amount' => 10, 'debtorIban' => 'GB82WEST12345698765432', 'debtorBic' => 'NWBKGB2LXXX', 'debtorName' => 'John Doe', 'debtorMandate' => 'M-1', 'endToEndId' => 'E2E-1'],
            ],
        ]);
        $this->assertTrue($this->xsdValidator->validateDirectDebit($xml));

        $tampered = (string) preg_replace('#<MsgId>.*?</MsgId>#s', '', $xml);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^XSD validation failed/');
        $this->xsdValidator->validateDirectDebit($tampered);
    }
}
