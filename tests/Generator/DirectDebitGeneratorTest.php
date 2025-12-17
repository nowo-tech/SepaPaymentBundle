<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Generator;

use Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitData;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitTransaction;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for DirectDebitGenerator.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class DirectDebitGeneratorTest extends TestCase
{
    /**
     * Direct debit generator instance.
     *
     * @var DirectDebitGenerator
     */
    private DirectDebitGenerator $generator;

    /**
     * Sets up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $ibanValidator = new IbanValidator();
        $this->generator = new DirectDebitGenerator($ibanValidator);
    }

    /**
     * Tests XML generation with valid data.
     *
     * @return void
     */
    public function testGenerateXml(): void
    {
        $directDebitData = new DirectDebitData(
            'MSG-001',
            'My Company',
            'PMT-001',
            new \DateTime('2024-01-20'),
            'My Company Name',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE'
        );

        $directDebitData->setCreditorBic('CAIXESBBXXX');

        $transaction = new DirectDebitTransaction(
            100.50,
            'GB82WEST12345698765432',
            'John Doe',
            'MANDATE-001',
            new \DateTime('2023-12-01'),
            'E2E-001'
        );

        $transaction->setRemittanceInformation('Invoice 12345');

        $directDebitData->addTransaction($transaction);

        $xml = $this->generator->generate($directDebitData);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
        $this->assertStringContainsString('MSG-001', $xml);
        $this->assertStringContainsString('PMT-001', $xml);
        $this->assertStringContainsString('ES9121000418450200051332', $xml);
        $this->assertStringContainsString('GB82WEST12345698765432', $xml);
        $this->assertStringContainsString('100.50', $xml);
        $this->assertStringContainsString('E2E-001', $xml);
        $this->assertStringContainsString('MANDATE-001', $xml);
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

        $directDebitData = new DirectDebitData(
            'MSG-001',
            'My Company',
            'PMT-001',
            new \DateTime('2024-01-20'),
            'My Company Name',
            'INVALID-IBAN',
            'FRST',
            'ES1234567890123456789012',
            'CORE'
        );

        $this->generator->generate($directDebitData);
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

        $directDebitData = new DirectDebitData(
            'MSG-001',
            'My Company',
            'PMT-001',
            new \DateTime('2024-01-20'),
            'My Company Name',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE'
        );

        $transaction = new DirectDebitTransaction(
            100.50,
            'INVALID-IBAN',
            'John Doe',
            'MANDATE-001',
            new \DateTime('2023-12-01'),
            'E2E-001'
        );

        $directDebitData->addTransaction($transaction);

        $this->generator->generate($directDebitData);
    }

    /**
     * Tests XML generation with multiple transactions.
     *
     * @return void
     */
    public function testGenerateXmlWithMultipleTransactions(): void
    {
        $directDebitData = new DirectDebitData(
            'MSG-001',
            'My Company',
            'PMT-001',
            new \DateTime('2024-01-20'),
            'My Company Name',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE'
        );

        $directDebitData->addTransaction(new DirectDebitTransaction(
            100.50,
            'GB82WEST12345698765432',
            'John Doe',
            'MANDATE-001',
            new \DateTime('2023-12-01'),
            'E2E-001'
        ));

        $directDebitData->addTransaction(new DirectDebitTransaction(
            200.75,
            'FR1420041010050500013M02606',
            'Jane Smith',
            'MANDATE-002',
            new \DateTime('2023-12-01'),
            'E2E-002'
        ));

        $xml = $this->generator->generate($directDebitData);

        $this->assertStringContainsString('NbOfTxs', $xml);
        $this->assertStringContainsString('2', $xml);
        $this->assertStringContainsString('301.25', $xml); // Total amount
        $this->assertStringContainsString('E2E-001', $xml);
        $this->assertStringContainsString('E2E-002', $xml);
    }

    /**
     * Tests XML generation from array format.
     *
     * @return void
     */
    public function testGenerateFromArray(): void
    {
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
                    'amount' => 100.50,
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                    'debtorMandate' => 'MANDATE-001',
                    'debtorMandateSignDate' => '2023-12-01',
                    'endToEndId' => 'E2E-001',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
        $this->assertStringContainsString('MSG-001', $xml);
    }

    /**
     * Tests XML generation from array with DateTimeInterface dueDate.
     *
     * @return void
     */
    public function testGenerateFromArrayWithDateTimeInterface(): void
    {
        $data = [
            'reference' => 'MSG-001',
            'bankAccountOwner' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'dueDate' => new \DateTime('2024-01-20'),
            'creditorName' => 'My Company Name',
            'creditorIban' => 'ES9121000418450200051332',
            'seqType' => 'FRST',
            'creditorId' => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
            'transactions' => [
                [
                    'amount' => 100.50,
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                    'debtorMandate' => 'MANDATE-001',
                    'endToEndId' => 'E2E-001',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
    }

    /**
     * Tests XML generation from array with amount in cents (> 10000).
     *
     * @return void
     */
    public function testGenerateFromArrayWithAmountInCents(): void
    {
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
                    'amount' => 15000, // 150.00 in cents
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                    'debtorMandate' => 'MANDATE-001',
                    'endToEndId' => 'E2E-001',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('150.00', $xml);
    }

    /**
     * Tests XML generation from array without creditorBic.
     *
     * @return void
     */
    public function testGenerateFromArrayWithoutCreditorBic(): void
    {
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
                    'amount' => 100.50,
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                    'debtorMandate' => 'MANDATE-001',
                    'endToEndId' => 'E2E-001',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
    }

    /**
     * Tests XML generation from array without remittanceInformation.
     *
     * @return void
     */
    public function testGenerateFromArrayWithoutRemittanceInformation(): void
    {
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
                    'amount' => 100.50,
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                    'debtorMandate' => 'MANDATE-001',
                    'endToEndId' => 'E2E-001',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
    }

    /**
     * Tests XML generation from array without debtorMandateSignDate (uses default).
     *
     * @return void
     */
    public function testGenerateFromArrayWithoutMandateSignDate(): void
    {
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
                    'amount' => 100.50,
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                    'debtorMandate' => 'MANDATE-001',
                    'endToEndId' => 'E2E-001',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
    }

    /**
     * Tests XML generation from array with DateTimeInterface mandateSignDate.
     *
     * @return void
     */
    public function testGenerateFromArrayWithDateTimeInterfaceMandateSignDate(): void
    {
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
                    'amount' => 100.50,
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                    'debtorMandate' => 'MANDATE-001',
                    'debtorMandateSignDate' => new \DateTime('2023-12-01'),
                    'endToEndId' => 'E2E-001',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
    }

    /**
     * Tests XML generation from array without transactions.
     *
     * @return void
     */
    public function testGenerateFromArrayWithoutTransactions(): void
    {
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
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
    }

    /**
     * Tests XML generation from array with empty transactions.
     *
     * @return void
     */
    public function testGenerateFromArrayWithEmptyTransactions(): void
    {
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
            'transactions' => [],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
    }

    /**
     * Tests generateFromArray with missing required field: reference.
     *
     * @return void
     */
    public function testGenerateFromArrayMissingReference(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: reference');

        $data = [
            'bankAccountOwner' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'dueDate' => '2024-01-20',
            'creditorName' => 'My Company Name',
            'creditorIban' => 'ES9121000418450200051332',
            'seqType' => 'FRST',
            'creditorId' => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
        ];

        $this->generator->generateFromArray($data);
    }

    /**
     * Tests generateFromArray with missing required field: creditorIban.
     *
     * @return void
     */
    public function testGenerateFromArrayMissingCreditorIban(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: creditorIban');

        $data = [
            'reference' => 'MSG-001',
            'bankAccountOwner' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'dueDate' => '2024-01-20',
            'creditorName' => 'My Company Name',
            'seqType' => 'FRST',
            'creditorId' => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
        ];

        $this->generator->generateFromArray($data);
    }

    /**
     * Tests generateFromArray with invalid dueDate type.
     *
     * @return void
     */
    public function testGenerateFromArrayInvalidDueDateType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('dueDate must be a string or DateTimeInterface');

        $data = [
            'reference' => 'MSG-001',
            'bankAccountOwner' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'dueDate' => 12345, // Invalid type
            'creditorName' => 'My Company Name',
            'creditorIban' => 'ES9121000418450200051332',
            'seqType' => 'FRST',
            'creditorId' => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
        ];

        $this->generator->generateFromArray($data);
    }

    /**
     * Tests generateFromArray with missing required transaction field: amount.
     *
     * @return void
     */
    public function testGenerateFromArrayMissingTransactionAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required transaction field: amount');

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
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                    'debtorMandate' => 'MANDATE-001',
                    'endToEndId' => 'E2E-001',
                ],
            ],
        ];

        $this->generator->generateFromArray($data);
    }

    /**
     * Tests generateFromArray with missing required transaction field: debtorIban.
     *
     * @return void
     */
    public function testGenerateFromArrayMissingTransactionDebtorIban(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required transaction field: debtorIban');

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
                    'amount' => 100.50,
                    'debtorName' => 'John Doe',
                    'debtorMandate' => 'MANDATE-001',
                    'endToEndId' => 'E2E-001',
                ],
            ],
        ];

        $this->generator->generateFromArray($data);
    }

    /**
     * Tests generateFromArray with missing required transaction field: endToEndId.
     *
     * @return void
     */
    public function testGenerateFromArrayMissingTransactionEndToEndId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required transaction field: endToEndId');

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
                    'amount' => 100.50,
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                    'debtorMandate' => 'MANDATE-001',
                ],
            ],
        ];

        $this->generator->generateFromArray($data);
    }

    /**
     * Tests XML generation with debtor BIC.
     *
     * @return void
     */
    public function testGenerateXmlWithDebtorBic(): void
    {
        $directDebitData = new DirectDebitData(
            'MSG-001',
            'My Company',
            'PMT-001',
            new \DateTime('2024-01-20'),
            'My Company Name',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE'
        );

        $transaction = new DirectDebitTransaction(
            100.50,
            'GB82WEST12345698765432',
            'John Doe',
            'MANDATE-001',
            new \DateTime('2023-12-01'),
            'E2E-001'
        );

        $transaction->setDebtorBic('WESTGB22');
        $directDebitData->addTransaction($transaction);

        $xml = $this->generator->generate($directDebitData);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('WESTGB22', $xml);
        $this->assertStringContainsString('GB82WEST12345698765432', $xml);
    }

    /**
     * Tests generateFromArray with debtor BIC.
     *
     * @return void
     */
    public function testGenerateFromArrayWithDebtorBic(): void
    {
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
                    'amount' => 100.50,
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                    'debtorMandate' => 'MANDATE-001',
                    'debtorMandateSignDate' => '2023-12-01',
                    'endToEndId' => 'E2E-001',
                    'debtorBic' => 'WESTGB22',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
        $this->assertStringContainsString('WESTGB22', $xml);
    }

    /**
     * Tests generateFromArray with additional data fields.
     *
     * @return void
     */
    public function testGenerateFromArrayWithAdditionalData(): void
    {
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
                    'amount' => 100.50,
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                    'debtorMandate' => 'MANDATE-001',
                    'debtorMandateSignDate' => '2023-12-01',
                    'endToEndId' => 'E2E-001',
                    'internalReference' => 'INT-12345',
                    'customerId' => 'CUST-789',
                    'customField' => 'customValue',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        // XML should be generated successfully even with additional fields
        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
        // Additional fields should not appear in XML (they are stored internally only)
        $this->assertStringNotContainsString('INT-12345', $xml);
        $this->assertStringNotContainsString('CUST-789', $xml);
        $this->assertStringNotContainsString('customValue', $xml);
    }

    /**
     * Tests that additional data is stored but not included in XML.
     *
     * @return void
     */
    public function testAdditionalDataNotInXml(): void
    {
        $directDebitData = new DirectDebitData(
            'MSG-001',
            'My Company',
            'PMT-001',
            new \DateTime('2024-01-20'),
            'My Company Name',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE'
        );

        $transaction = new DirectDebitTransaction(
            100.50,
            'GB82WEST12345698765432',
            'John Doe',
            'MANDATE-001',
            new \DateTime('2023-12-01'),
            'E2E-001'
        );

        $transaction->setAdditionalData([
            'internalReference' => 'INT-12345',
            'customerId' => 'CUST-789',
            'sensitiveData' => 'should-not-appear-in-xml',
        ]);

        $directDebitData->addTransaction($transaction);

        $xml = $this->generator->generate($directDebitData);

        // Verify XML is valid
        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);

        // Verify additional data is NOT in XML
        $this->assertStringNotContainsString('INT-12345', $xml);
        $this->assertStringNotContainsString('CUST-789', $xml);
        $this->assertStringNotContainsString('should-not-appear-in-xml', $xml);
    }

    /**
     * Tests generateFromArray with both debtorBic and additional data.
     *
     * @return void
     */
    public function testGenerateFromArrayWithDebtorBicAndAdditionalData(): void
    {
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
                    'amount' => 100.50,
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                    'debtorMandate' => 'MANDATE-001',
                    'debtorMandateSignDate' => '2023-12-01',
                    'endToEndId' => 'E2E-001',
                    'debtorBic' => 'WESTGB22',
                    'remittanceInformation' => 'Invoice 12345',
                    'internalReference' => 'INT-12345',
                    'customField' => 'customValue',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
        // BIC should be in XML
        $this->assertStringContainsString('WESTGB22', $xml);
        // Remittance information should be in XML
        $this->assertStringContainsString('Invoice 12345', $xml);
        // Additional data should NOT be in XML
        $this->assertStringNotContainsString('INT-12345', $xml);
        $this->assertStringNotContainsString('customValue', $xml);
    }
}
