<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Generator;

use Nowo\SepaPaymentBundle\Generator\RemesaGenerator;
use Nowo\SepaPaymentBundle\Model\Remesa\RemesaData;
use Nowo\SepaPaymentBundle\Model\Remesa\Transaction;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for RemesaGenerator.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class RemesaGeneratorTest extends TestCase
{
    /**
     * Remesa generator instance.
     *
     * @var RemesaGenerator
     */
    private RemesaGenerator $generator;

    /**
     * Sets up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $ibanValidator = new IbanValidator();
        $this->generator = new RemesaGenerator($ibanValidator);
    }

    /**
     * Tests XML generation with valid data.
     *
     * @return void
     */
    public function testGenerateXml(): void
    {
        $remesaData = new RemesaData(
            'MSG-001',
            new \DateTime('2024-01-15 10:00:00'),
            'My Company',
            'PMT-001',
            'ES9121000418450200051332',
            'My Company Name',
            new \DateTime('2024-01-20')
        );

        $remesaData->setCreditorBic('CAIXESBBXXX');
        $remesaData->setBatchBooking(true);

        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'GB82WEST12345698765432',
            'John Doe'
        );

        $transaction->setDebtorBic('WESTGB22');
        $transaction->setRemittanceInformation('Invoice 12345');

        $remesaData->addTransaction($transaction);

        $xml = $this->generator->generate($remesaData);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
        $this->assertStringContainsString('MSG-001', $xml);
        $this->assertStringContainsString('PMT-001', $xml);
        $this->assertStringContainsString('ES9121000418450200051332', $xml);
        $this->assertStringContainsString('GB82WEST12345698765432', $xml);
        $this->assertStringContainsString('100.50', $xml);
        $this->assertStringContainsString('E2E-001', $xml);
        $this->assertStringContainsString('Invoice 12345', $xml);
    }

    /**
     * Tests XML generation with invalid creditor IBAN.
     *
     * @return void
     */
    public function testGenerateXmlWithInvalidCreditorIban(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid creditor IBAN');

        $remesaData = new RemesaData(
            'MSG-001',
            new \DateTime('2024-01-15 10:00:00'),
            'My Company',
            'PMT-001',
            'INVALID-IBAN',
            'My Company Name',
            new \DateTime('2024-01-20')
        );

        $this->generator->generate($remesaData);
    }

    /**
     * Tests XML generation with invalid debtor IBAN.
     *
     * @return void
     */
    public function testGenerateXmlWithInvalidDebtorIban(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid debtor IBAN');

        $remesaData = new RemesaData(
            'MSG-001',
            new \DateTime('2024-01-15 10:00:00'),
            'My Company',
            'PMT-001',
            'ES9121000418450200051332',
            'My Company Name',
            new \DateTime('2024-01-20')
        );

        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'INVALID-IBAN',
            'John Doe'
        );

        $remesaData->addTransaction($transaction);

        $this->generator->generate($remesaData);
    }

    /**
     * Tests XML generation with multiple transactions.
     *
     * @return void
     */
    public function testGenerateXmlWithMultipleTransactions(): void
    {
        $remesaData = new RemesaData(
            'MSG-001',
            new \DateTime('2024-01-15 10:00:00'),
            'My Company',
            'PMT-001',
            'ES9121000418450200051332',
            'My Company Name',
            new \DateTime('2024-01-20')
        );

        $remesaData->addTransaction(new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'GB82WEST12345698765432',
            'John Doe'
        ));

        $remesaData->addTransaction(new Transaction(
            'E2E-002',
            200.75,
            'EUR',
            'FR1420041010050500013M02606',
            'Jane Smith'
        ));

        $xml = $this->generator->generate($remesaData);

        $this->assertStringContainsString('NbOfTxs', $xml);
        $this->assertStringContainsString('2', $xml);
        $this->assertStringContainsString('301.25', $xml); // Total amount
        $this->assertStringContainsString('E2E-001', $xml);
        $this->assertStringContainsString('E2E-002', $xml);
    }

    /**
     * Tests XML generation without BIC.
     *
     * @return void
     */
    public function testGenerateXmlWithoutBic(): void
    {
        $remesaData = new RemesaData(
            'MSG-001',
            new \DateTime('2024-01-15 10:00:00'),
            'My Company',
            'PMT-001',
            'ES9121000418450200051332',
            'My Company Name',
            new \DateTime('2024-01-20')
        );

        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'GB82WEST12345698765432',
            'John Doe'
        );

        $remesaData->addTransaction($transaction);

        $xml = $this->generator->generate($remesaData);

        // XML should be valid and contain transaction data
        $this->assertStringContainsString('E2E-001', $xml);
        $this->assertStringContainsString('100.50', $xml);
    }

    /**
     * Tests XML generation with special characters in text fields.
     *
     * @return void
     */
    public function testGenerateXmlWithSpecialCharacters(): void
    {
        $remesaData = new RemesaData(
            'MSG-001',
            new \DateTime('2024-01-15 10:00:00'),
            'My Company & Co.',
            'PMT-001',
            'ES9121000418450200051332',
            'My Company Name <Test>',
            new \DateTime('2024-01-20')
        );

        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'GB82WEST12345698765432',
            'John "Doe"'
        );

        $transaction->setRemittanceInformation('Invoice & Payment <2024>');

        $remesaData->addTransaction($transaction);

        $xml = $this->generator->generate($remesaData);

        // Should properly escape special characters
        $this->assertStringNotContainsString('<Test>', $xml);
        $this->assertStringNotContainsString('& Co.', $xml);
        $this->assertStringContainsString('&amp;', $xml);
        $this->assertStringContainsString('&lt;', $xml);
        $this->assertStringContainsString('&gt;', $xml);
    }
}
