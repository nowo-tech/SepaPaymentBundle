<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Generator;

use DateTime;
use DOMDocument;
use DOMXPath;
use Exception;
use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Event\AfterDirectDebitGenerationEvent;
use Nowo\SepaPaymentBundle\Event\BeforeDirectDebitGenerationEvent;
use Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator;
use Nowo\SepaPaymentBundle\Logger\SepaPaymentLogger;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitData;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitTransaction;
use Nowo\SepaPaymentBundle\Tests\Unit\Logger\TestLogger;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventDispatcher;

use function count;

require_once __DIR__ . '/XPathCoverageHelpers.php';

/**
 * Test cases for DirectDebitGenerator.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class DirectDebitGeneratorTest extends TestCase
{
    /**
     * Direct debit generator instance.
     */
    private DirectDebitGenerator $generator;

    /**
     * Sets up the test environment.
     */
    protected function setUp(): void
    {
        $ibanValidator   = new IbanValidator();
        $this->generator = new DirectDebitGenerator($ibanValidator);
    }

    /**
     * Tests XML generation with valid data (without addresses).
     */
    public function testGenerateXml(): void
    {
        $directDebitData = new DirectDebitData(
            'MSG-001',
            'My Company',
            'PMT-001',
            new DateTime('2024-01-20'),
            'My Company Name',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );

        $directDebitData->setCreditorBic('CAIXESBBXXX');

        $transaction = new DirectDebitTransaction(
            100.50,
            'GB82WEST12345698765432',
            'John Doe',
            'MANDATE-001',
            new DateTime('2023-12-01'),
            'E2E-001',
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
     */
    public function testGenerateXmlWithInvalidCreditorIban(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid creditor IBAN');

        $directDebitData = new DirectDebitData(
            'MSG-001',
            'My Company',
            'PMT-001',
            new DateTime('2024-01-20'),
            'My Company Name',
            'INVALID-IBAN',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );

        $this->generator->generate($directDebitData);
    }

    /**
     * Tests XML generation with invalid debtor IBAN.
     */
    public function testGenerateXmlWithInvalidDebtorIban(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid debtor IBAN');

        $directDebitData = new DirectDebitData(
            'MSG-001',
            'My Company',
            'PMT-001',
            new DateTime('2024-01-20'),
            'My Company Name',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );

        $transaction = new DirectDebitTransaction(
            100.50,
            'INVALID-IBAN',
            'John Doe',
            'MANDATE-001',
            new DateTime('2023-12-01'),
            'E2E-001',
        );

        $directDebitData->addTransaction($transaction);

        $this->generator->generate($directDebitData);
    }

    /**
     * Tests XML generation with multiple transactions.
     */
    public function testGenerateXmlWithMultipleTransactions(): void
    {
        $directDebitData = new DirectDebitData(
            'MSG-001',
            'My Company',
            'PMT-001',
            new DateTime('2024-01-20'),
            'My Company Name',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );

        $directDebitData->addTransaction(new DirectDebitTransaction(
            100.50,
            'GB82WEST12345698765432',
            'John Doe',
            'MANDATE-001',
            new DateTime('2023-12-01'),
            'E2E-001',
        ));

        $directDebitData->addTransaction(new DirectDebitTransaction(
            200.75,
            'FR1420041010050500013M02606',
            'Jane Smith',
            'MANDATE-002',
            new DateTime('2023-12-01'),
            'E2E-002',
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
     */
    public function testGenerateFromArray(): void
    {
        $data = [
            'reference'           => 'MSG-001',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'seqType'             => 'FRST',
            'creditorId'          => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
            'transactions'        => [
                [
                    'amount'                => 100.50,
                    'debtorIban'            => 'GB82WEST12345698765432',
                    'debtorName'            => 'John Doe',
                    'debtorMandate'         => 'MANDATE-001',
                    'debtorMandateSignDate' => '2023-12-01',
                    'endToEndId'            => 'E2E-001',
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
     */
    public function testGenerateFromArrayWithDateTimeInterface(): void
    {
        $data = [
            'reference'           => 'MSG-001',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'dueDate'             => new DateTime('2024-01-20'),
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'seqType'             => 'FRST',
            'creditorId'          => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
            'transactions'        => [
                [
                    'amount'        => 100.50,
                    'debtorIban'    => 'GB82WEST12345698765432',
                    'debtorName'    => 'John Doe',
                    'debtorMandate' => 'MANDATE-001',
                    'endToEndId'    => 'E2E-001',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
    }

    /**
     * Tests XML generation from array with amount in cents (> 10000).
     */
    public function testGenerateFromArrayWithAmountInCents(): void
    {
        $data = [
            'reference'           => 'MSG-001',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'seqType'             => 'FRST',
            'creditorId'          => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
            'transactions'        => [
                [
                    'amount'        => 15000, // 150.00 in cents
                    'debtorIban'    => 'GB82WEST12345698765432',
                    'debtorName'    => 'John Doe',
                    'debtorMandate' => 'MANDATE-001',
                    'endToEndId'    => 'E2E-001',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('150.00', $xml);
    }

    /**
     * Tests XML generation from array without creditorBic.
     */
    public function testGenerateFromArrayWithoutCreditorBic(): void
    {
        $data = [
            'reference'           => 'MSG-001',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'seqType'             => 'FRST',
            'creditorId'          => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
            'transactions'        => [
                [
                    'amount'        => 100.50,
                    'debtorIban'    => 'GB82WEST12345698765432',
                    'debtorName'    => 'John Doe',
                    'debtorMandate' => 'MANDATE-001',
                    'endToEndId'    => 'E2E-001',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
    }

    /**
     * Tests XML generation from array without remittanceInformation.
     */
    public function testGenerateFromArrayWithoutRemittanceInformation(): void
    {
        $data = [
            'reference'           => 'MSG-001',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'seqType'             => 'FRST',
            'creditorId'          => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
            'transactions'        => [
                [
                    'amount'        => 100.50,
                    'debtorIban'    => 'GB82WEST12345698765432',
                    'debtorName'    => 'John Doe',
                    'debtorMandate' => 'MANDATE-001',
                    'endToEndId'    => 'E2E-001',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
    }

    /**
     * Tests XML generation from array without debtorMandateSignDate (uses default).
     */
    public function testGenerateFromArrayWithoutMandateSignDate(): void
    {
        $data = [
            'reference'           => 'MSG-001',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'seqType'             => 'FRST',
            'creditorId'          => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
            'transactions'        => [
                [
                    'amount'        => 100.50,
                    'debtorIban'    => 'GB82WEST12345698765432',
                    'debtorName'    => 'John Doe',
                    'debtorMandate' => 'MANDATE-001',
                    'endToEndId'    => 'E2E-001',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
    }

    /**
     * Tests XML generation from array with DateTimeInterface mandateSignDate.
     */
    public function testGenerateFromArrayWithDateTimeInterfaceMandateSignDate(): void
    {
        $data = [
            'reference'           => 'MSG-001',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'seqType'             => 'FRST',
            'creditorId'          => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
            'transactions'        => [
                [
                    'amount'                => 100.50,
                    'debtorIban'            => 'GB82WEST12345698765432',
                    'debtorName'            => 'John Doe',
                    'debtorMandate'         => 'MANDATE-001',
                    'debtorMandateSignDate' => new DateTime('2023-12-01'),
                    'endToEndId'            => 'E2E-001',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
    }

    /**
     * Tests XML generation from array without transactions.
     */
    public function testGenerateFromArrayWithoutTransactions(): void
    {
        $data = [
            'reference'           => 'MSG-001',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'seqType'             => 'FRST',
            'creditorId'          => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
    }

    /**
     * Tests XML generation from array with empty transactions.
     */
    public function testGenerateFromArrayWithEmptyTransactions(): void
    {
        $data = [
            'reference'           => 'MSG-001',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'seqType'             => 'FRST',
            'creditorId'          => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
            'transactions'        => [],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
    }

    /**
     * Tests generateFromArray with missing required field: reference.
     */
    public function testGenerateFromArrayMissingReference(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: reference');

        $data = [
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'seqType'             => 'FRST',
            'creditorId'          => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
        ];

        $this->generator->generateFromArray($data);
    }

    /**
     * Tests generateFromArray with missing required field: creditorIban.
     */
    public function testGenerateFromArrayMissingCreditorIban(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: creditorIban');

        $data = [
            'reference'           => 'MSG-001',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company Name',
            'seqType'             => 'FRST',
            'creditorId'          => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
        ];

        $this->generator->generateFromArray($data);
    }

    /**
     * Tests generateFromArray with invalid dueDate type.
     */
    public function testGenerateFromArrayInvalidDueDateType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('dueDate must be a string or DateTimeInterface');

        $data = [
            'reference'           => 'MSG-001',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'dueDate'             => 12345, // Invalid type
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'seqType'             => 'FRST',
            'creditorId'          => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
        ];

        $this->generator->generateFromArray($data);
    }

    /**
     * Tests generateFromArray with missing required transaction field: amount.
     */
    public function testGenerateFromArrayMissingTransactionAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required transaction field: amount');

        $data = [
            'reference'           => 'MSG-001',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'seqType'             => 'FRST',
            'creditorId'          => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
            'transactions'        => [
                [
                    'debtorIban'    => 'GB82WEST12345698765432',
                    'debtorName'    => 'John Doe',
                    'debtorMandate' => 'MANDATE-001',
                    'endToEndId'    => 'E2E-001',
                ],
            ],
        ];

        $this->generator->generateFromArray($data);
    }

    /**
     * Tests generateFromArray with missing required transaction field: debtorIban.
     */
    public function testGenerateFromArrayMissingTransactionDebtorIban(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required transaction field: debtorIban');

        $data = [
            'reference'           => 'MSG-001',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'seqType'             => 'FRST',
            'creditorId'          => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
            'transactions'        => [
                [
                    'amount'        => 100.50,
                    'debtorName'    => 'John Doe',
                    'debtorMandate' => 'MANDATE-001',
                    'endToEndId'    => 'E2E-001',
                ],
            ],
        ];

        $this->generator->generateFromArray($data);
    }

    /**
     * Tests generateFromArray with missing required transaction field: endToEndId.
     */
    public function testGenerateFromArrayMissingTransactionEndToEndId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required transaction field: endToEndId');

        $data = [
            'reference'           => 'MSG-001',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'seqType'             => 'FRST',
            'creditorId'          => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
            'transactions'        => [
                [
                    'amount'        => 100.50,
                    'debtorIban'    => 'GB82WEST12345698765432',
                    'debtorName'    => 'John Doe',
                    'debtorMandate' => 'MANDATE-001',
                ],
            ],
        ];

        $this->generator->generateFromArray($data);
    }

    /**
     * Tests XML generation with debtor BIC.
     */
    public function testGenerateXmlWithDebtorBic(): void
    {
        $directDebitData = new DirectDebitData(
            'MSG-001',
            'My Company',
            'PMT-001',
            new DateTime('2024-01-20'),
            'My Company Name',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );

        $transaction = new DirectDebitTransaction(
            100.50,
            'GB82WEST12345698765432',
            'John Doe',
            'MANDATE-001',
            new DateTime('2023-12-01'),
            'E2E-001',
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
     */
    public function testGenerateFromArrayWithDebtorBic(): void
    {
        $data = [
            'reference'           => 'MSG-001',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'seqType'             => 'FRST',
            'creditorId'          => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
            'transactions'        => [
                [
                    'amount'                => 100.50,
                    'debtorIban'            => 'GB82WEST12345698765432',
                    'debtorName'            => 'John Doe',
                    'debtorMandate'         => 'MANDATE-001',
                    'debtorMandateSignDate' => '2023-12-01',
                    'endToEndId'            => 'E2E-001',
                    'debtorBic'             => 'WESTGB22',
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
     */
    public function testGenerateFromArrayWithAdditionalData(): void
    {
        $data = [
            'reference'           => 'MSG-001',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'seqType'             => 'FRST',
            'creditorId'          => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
            'transactions'        => [
                [
                    'amount'                => 100.50,
                    'debtorIban'            => 'GB82WEST12345698765432',
                    'debtorName'            => 'John Doe',
                    'debtorMandate'         => 'MANDATE-001',
                    'debtorMandateSignDate' => '2023-12-01',
                    'endToEndId'            => 'E2E-001',
                    'internalReference'     => 'INT-12345',
                    'customerId'            => 'CUST-789',
                    'customField'           => 'customValue',
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
     */
    public function testAdditionalDataNotInXml(): void
    {
        $directDebitData = new DirectDebitData(
            'MSG-001',
            'My Company',
            'PMT-001',
            new DateTime('2024-01-20'),
            'My Company Name',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );

        $transaction = new DirectDebitTransaction(
            100.50,
            'GB82WEST12345698765432',
            'John Doe',
            'MANDATE-001',
            new DateTime('2023-12-01'),
            'E2E-001',
        );

        $transaction->setAdditionalData([
            'internalReference' => 'INT-12345',
            'customerId'        => 'CUST-789',
            'sensitiveData'     => 'should-not-appear-in-xml',
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
     */
    public function testGenerateFromArrayWithDebtorBicAndAdditionalData(): void
    {
        $data = [
            'reference'           => 'MSG-001',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'seqType'             => 'FRST',
            'creditorId'          => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
            'transactions'        => [
                [
                    'amount'                => 100.50,
                    'debtorIban'            => 'GB82WEST12345698765432',
                    'debtorName'            => 'John Doe',
                    'debtorMandate'         => 'MANDATE-001',
                    'debtorMandateSignDate' => '2023-12-01',
                    'endToEndId'            => 'E2E-001',
                    'debtorBic'             => 'WESTGB22',
                    'remittanceInformation' => 'Invoice 12345',
                    'internalReference'     => 'INT-12345',
                    'customField'           => 'customValue',
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

    /**
     * Tests generateFromArray with snake_case field names.
     */
    public function testGenerateFromArrayWithSnakeCase(): void
    {
        $data = [
            'message_id'            => 'PRE2025121614020000001REM000001',
            'initiating_party_name' => 'dwdwdw',
            'payment_name'          => 'PMTINF-1',
            'due_date'              => '2025-12-18',
            'creditor_name'         => 'pepito',
            'creditor_iban'         => 'ES2931183364320522274646',
            'creditor_bic'          => 'BBVAESMM',
            'sequence_type'         => 'RCUR',
            'creditor_id'           => 'ES654646464646',
            'instrument_code'       => 'CORE',
            'items'                 => [
                [
                    'instruction_id'                => 'ES3330605615396412039906',
                    'amount'                        => 2500.0,
                    'debtor_iban'                   => 'ES3330605615396412039906',
                    'debtor_name'                   => 'grgrg',
                    'debtor_mandate'                => 'ES3330605615396412039906',
                    'debtor_mandate_signature_date' => new DateTime('2025-09-26'),
                    'information'                   => 'Periodo:26/09/2025 al 26/09/2025 N. Poliza: 2025-00000001-00003 Recibo Cia: rtrtt',
                    'id'                            => 'rtrtt',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
        $this->assertStringContainsString('PRE2025121614020000001REM000001', $xml);
        $this->assertStringContainsString('PMTINF-1', $xml);
        $this->assertStringContainsString('pepito', $xml);
        $this->assertStringContainsString('ES2931183364320522274646', $xml);
        $this->assertStringContainsString('ES3330605615396412039906', $xml);
        $this->assertStringContainsString('2500', $xml);
        $this->assertStringContainsString('Periodo:26/09/2025', $xml);
    }

    /**
     * Tests generateFromArray with snake_case and additional fields.
     */
    public function testGenerateFromArrayWithSnakeCaseAndAdditionalFields(): void
    {
        $data = [
            'message_id'            => 'MSG-001',
            'initiating_party_name' => 'My Company',
            'payment_name'          => 'PMT-001',
            'due_date'              => '2024-01-20',
            'creditor_name'         => 'My Company Name',
            'creditor_iban'         => 'ES9121000418450200051332',
            'creditor_bic'          => 'CAIXESBBXXX',
            'sequence_type'         => 'FRST',
            'creditor_id'           => 'ES1234567890123456789012',
            'instrument_code'       => 'CORE',
            'items'                 => [
                [
                    'instruction_id'                => 'E2E-001',
                    'amount'                        => 100.50,
                    'debtor_iban'                   => 'GB82WEST12345698765432',
                    'debtor_name'                   => 'John Doe',
                    'debtor_mandate'                => 'MANDATE-001',
                    'debtor_mandate_signature_date' => '2023-12-01',
                    'information'                   => 'Invoice 12345',
                    'custom_field'                  => 'customValue',
                    'internal_id'                   => 'INT-12345',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
        $this->assertStringContainsString('Invoice 12345', $xml);
        // Additional fields should not appear in XML
        $this->assertStringNotContainsString('customValue', $xml);
        $this->assertStringNotContainsString('INT-12345', $xml);
    }

    /**
     * Tests generateFromArray with creditor and debtor addresses.
     * Addresses are attempted to be included in XML if the library supports it.
     */
    public function testGenerateFromArrayWithAddresses(): void
    {
        $data = [
            'reference'           => 'MSG-001',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'seqType'             => 'FRST',
            'creditorId'          => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
            'creditorAddress'     => [
                'street'     => '123 Business Street',
                'city'       => 'Madrid',
                'postalCode' => '28001',
                'country'    => 'ES',
            ],
            'transactions' => [
                [
                    'amount'                => 100.50,
                    'debtorIban'            => 'GB82WEST12345698765432',
                    'debtorName'            => 'John Doe',
                    'debtorMandate'         => 'MANDATE-001',
                    'debtorMandateSignDate' => '2024-01-15',
                    'endToEndId'            => 'E2E-001',
                    'debtorAddress'         => [
                        'street'     => '456 Customer Avenue',
                        'city'       => 'London',
                        'postalCode' => 'SW1A 1AA',
                        'country'    => 'GB',
                    ],
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        // XML should be generated successfully
        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
        // Addresses should appear in XML
        $this->assertStringContainsString('PstlAdr', $xml);
        $this->assertStringContainsString('123 Business Street', $xml);
        $this->assertStringContainsString('Madrid', $xml);
        $this->assertStringContainsString('28001', $xml);
        $this->assertStringContainsString('ES', $xml);
        $this->assertStringContainsString('456 Customer Avenue', $xml);
        $this->assertStringContainsString('London', $xml);
        $this->assertStringContainsString('SW1A 1AA', $xml);
        $this->assertStringContainsString('GB', $xml);
    }

    /**
     * Tests generateFromArray with addresses using snake_case field names.
     */
    public function testGenerateFromArrayWithAddressesSnakeCase(): void
    {
        $data = [
            'message_id'            => 'MSG-001',
            'initiating_party_name' => 'My Company',
            'payment_name'          => 'PMT-001',
            'due_date'              => '2024-01-20',
            'creditor_name'         => 'My Company Name',
            'creditor_iban'         => 'ES9121000418450200051332',
            'sequence_type'         => 'FRST',
            'creditor_id'           => 'ES1234567890123456789012',
            'instrument_code'       => 'CORE',
            'creditor_street'       => '123 Business Street',
            'creditor_city'         => 'Madrid',
            'creditor_postal_code'  => '28001',
            'creditor_country'      => 'ES',
            'items'                 => [
                [
                    'instruction_id'                => 'E2E-001',
                    'amount'                        => 100.50,
                    'debtor_iban'                   => 'GB82WEST12345698765432',
                    'debtor_name'                   => 'John Doe',
                    'debtor_mandate'                => 'MANDATE-001',
                    'debtor_mandate_signature_date' => '2024-01-15',
                    'debtor_street'                 => '456 Customer Avenue',
                    'debtor_city'                   => 'London',
                    'debtor_postal_code'            => 'SW1A 1AA',
                    'debtor_country'                => 'GB',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        // XML should be generated successfully
        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
        // Addresses should appear in XML
        $this->assertStringContainsString('PstlAdr', $xml);
        $this->assertStringContainsString('123 Business Street', $xml);
        $this->assertStringContainsString('Madrid', $xml);
        $this->assertStringContainsString('28001', $xml);
        $this->assertStringContainsString('456 Customer Avenue', $xml);
        $this->assertStringContainsString('London', $xml);
        $this->assertStringContainsString('SW1A 1AA', $xml);
    }

    /**
     * Tests XML generation with creditor address using object methods.
     */
    public function testGenerateXmlWithCreditorAddress(): void
    {
        $directDebitData = new DirectDebitData(
            'MSG-001',
            'My Company',
            'PMT-001',
            new DateTime('2024-01-20'),
            'My Company Name',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );

        $directDebitData->setCreditorAddress([
            'street'     => '789 Business Road',
            'city'       => 'Barcelona',
            'postalCode' => '08001',
            'country'    => 'ES',
        ]);

        $transaction = new DirectDebitTransaction(
            100.50,
            'GB82WEST12345698765432',
            'John Doe',
            'MANDATE-001',
            new DateTime('2023-12-01'),
            'E2E-001',
        );

        $directDebitData->addTransaction($transaction);

        $xml = $this->generator->generate($directDebitData);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
        $this->assertStringContainsString('PstlAdr', $xml);
        $this->assertStringContainsString('789 Business Road', $xml);
        $this->assertStringContainsString('Barcelona', $xml);
        $this->assertStringContainsString('08001', $xml);
    }

    /**
     * Tests XML generation with debtor address using object methods.
     */
    public function testGenerateXmlWithDebtorAddress(): void
    {
        $directDebitData = new DirectDebitData(
            'MSG-001',
            'My Company',
            'PMT-001',
            new DateTime('2024-01-20'),
            'My Company Name',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );

        $transaction = new DirectDebitTransaction(
            100.50,
            'GB82WEST12345698765432',
            'John Doe',
            'MANDATE-001',
            new DateTime('2023-12-01'),
            'E2E-001',
        );

        $transaction->setDebtorAddress([
            'street'     => '321 Customer Street',
            'city'       => 'Manchester',
            'postalCode' => 'M1 1AA',
            'country'    => 'GB',
        ]);

        $directDebitData->addTransaction($transaction);

        $xml = $this->generator->generate($directDebitData);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
        $this->assertStringContainsString('PstlAdr', $xml);
        $this->assertStringContainsString('321 Customer Street', $xml);
        $this->assertStringContainsString('Manchester', $xml);
        $this->assertStringContainsString('M1 1AA', $xml);
    }

    /**
     * Tests XML generation with both creditor and debtor addresses.
     */
    public function testGenerateXmlWithBothAddresses(): void
    {
        $directDebitData = new DirectDebitData(
            'MSG-001',
            'My Company',
            'PMT-001',
            new DateTime('2024-01-20'),
            'My Company Name',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );

        $directDebitData->setCreditorAddress([
            'street'     => '111 Creditor Ave',
            'city'       => 'Valencia',
            'postalCode' => '46001',
            'country'    => 'ES',
        ]);

        $transaction = new DirectDebitTransaction(
            100.50,
            'GB82WEST12345698765432',
            'John Doe',
            'MANDATE-001',
            new DateTime('2023-12-01'),
            'E2E-001',
        );

        $transaction->setDebtorAddress([
            'street'     => '222 Debtor Blvd',
            'city'       => 'Leeds',
            'postalCode' => 'LS1 1AA',
            'country'    => 'GB',
        ]);

        $directDebitData->addTransaction($transaction);

        $xml = $this->generator->generate($directDebitData);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
        $this->assertStringContainsString('PstlAdr', $xml);
        // Creditor address
        $this->assertStringContainsString('111 Creditor Ave', $xml);
        $this->assertStringContainsString('Valencia', $xml);
        // Debtor address
        $this->assertStringContainsString('222 Debtor Blvd', $xml);
        $this->assertStringContainsString('Leeds', $xml);
    }

    /**
     * Tests generateFromArray with creditor_address in snake_case.
     */
    public function testGenerateFromArrayWithCreditorAddressSnakeCase(): void
    {
        $data = [
            'message_id'            => 'MSG-001',
            'initiating_party_name' => 'My Company',
            'payment_name'          => 'PMT-001',
            'due_date'              => '2024-01-20',
            'creditor_name'         => 'My Company Name',
            'creditor_iban'         => 'ES9121000418450200051332',
            'sequence_type'         => 'FRST',
            'creditor_id'           => 'ES1234567890123456789012',
            'instrument_code'       => 'CORE',
            'creditor_address'      => [
                'street'      => '333 Snake Street',
                'city'        => 'Seville',
                'postal_code' => '41001',
                'country'     => 'ES',
            ],
            'items' => [
                [
                    'instruction_id'                => 'E2E-001',
                    'amount'                        => 100.50,
                    'debtor_iban'                   => 'GB82WEST12345698765432',
                    'debtor_name'                   => 'John Doe',
                    'debtor_mandate'                => 'MANDATE-001',
                    'debtor_mandate_signature_date' => '2024-01-15',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
        $this->assertStringContainsString('PstlAdr', $xml);
        $this->assertStringContainsString('333 Snake Street', $xml);
        $this->assertStringContainsString('Seville', $xml);
        $this->assertStringContainsString('41001', $xml);
    }

    /**
     * Tests that addresses are optional and not included when not provided.
     */
    public function testGenerateXmlWithoutAddresses(): void
    {
        $data = [
            'reference'           => 'MSG-001',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'seqType'             => 'FRST',
            'creditorId'          => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
            'transactions'        => [
                [
                    'amount'                => 100.50,
                    'debtorIban'            => 'GB82WEST12345698765432',
                    'debtorName'            => 'John Doe',
                    'debtorMandate'         => 'MANDATE-001',
                    'debtorMandateSignDate' => '2024-01-15',
                    'endToEndId'            => 'E2E-001',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
        // Addresses should NOT appear when not provided
        $this->assertStringNotContainsString('PstlAdr', $xml);
    }

    /**
     * Tests that empty address arrays are not included.
     */
    public function testGenerateXmlWithEmptyAddressArray(): void
    {
        $data = [
            'reference'           => 'MSG-001',
            'bankAccountOwner'    => 'My Company',
            'paymentInfoId'       => 'PMT-001',
            'dueDate'             => '2024-01-20',
            'creditorName'        => 'My Company Name',
            'creditorIban'        => 'ES9121000418450200051332',
            'seqType'             => 'FRST',
            'creditorId'          => 'ES1234567890123456789012',
            'localInstrumentCode' => 'CORE',
            'creditorAddress'     => [], // Empty array
            'transactions'        => [
                [
                    'amount'                => 100.50,
                    'debtorIban'            => 'GB82WEST12345698765432',
                    'debtorName'            => 'John Doe',
                    'debtorMandate'         => 'MANDATE-001',
                    'debtorMandateSignDate' => '2024-01-15',
                    'endToEndId'            => 'E2E-001',
                    'debtorAddress'         => [], // Empty array
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
        // Empty address arrays should NOT create PstlAdr elements
        $this->assertStringNotContainsString('PstlAdr', $xml);
    }

    /**
     * Tests createResponse method.
     */
    public function testCreateResponse(): void
    {
        $xml      = '<?xml version="1.0" encoding="UTF-8"?><test>XML Content</test>';
        $filename = 'test-direct-debit.xml';

        $response = $this->generator->createResponse($xml, $filename);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($xml, $response->getContent());
        $this->assertEquals('application/xml', $response->headers->get('Content-Type'));
        $this->assertEquals('attachment; filename="test-direct-debit.xml"', $response->headers->get('Content-Disposition'));
    }

    /**
     * Tests generation with logger integration.
     */
    public function testGenerateWithLogger(): void
    {
        $testLogger = new TestLogger();
        $sepaLogger = new SepaPaymentLogger($testLogger);
        $generator  = new DirectDebitGenerator(new IbanValidator(), null, false, null, $sepaLogger);

        $data = new DirectDebitData(
            'MSG-LOG',
            'My Company',
            'PMT-001',
            new DateTime('2024-01-20'),
            'Creditor Name',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );
        $data->addTransaction(new DirectDebitTransaction(
            100.50,
            'GB82WEST12345698765432',
            'John Doe',
            'MANDATE-001',
            new DateTime('2024-01-01'),
            'E2E-001',
        ));

        $generator->generate($data);

        $this->assertGreaterThanOrEqual(2, count($testLogger->logs));
        $this->assertEquals('SEPA Direct Debit generation started', $testLogger->logs[0]['message']);
        $this->assertEquals('SEPA Direct Debit generation completed successfully', $testLogger->logs[1]['message']);
    }

    /**
     * Tests generation with event dispatcher (before event).
     */
    public function testGenerateWithEventDispatcher(): void
    {
        $dispatcher = new EventDispatcher();
        $generator  = new DirectDebitGenerator(new IbanValidator(), null, false, $dispatcher);

        $data = new DirectDebitData(
            'MSG-EVT',
            'My Company',
            'PMT-001',
            new DateTime('2024-01-20'),
            'Creditor Name',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );
        $data->addTransaction(new DirectDebitTransaction(
            50.00,
            'GB82WEST12345698765432',
            'Jane Doe',
            'MANDATE-002',
            new DateTime('2024-01-01'),
            'E2E-EVT',
        ));

        $xml = $generator->generate($data);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
        $this->assertStringContainsString('MSG-EVT', $xml);
    }

    /**
     * Tests that when a listener modifies the data in BeforeDirectDebitGenerationEvent, the generator uses the modified data.
     */
    public function testGenerateWithBeforeEventModifiesData(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(BeforeDirectDebitGenerationEvent::class, static function (BeforeDirectDebitGenerationEvent $event): void {
            $original = $event->getDirectDebitData();
            $modified = new DirectDebitData(
                'MODIFIED-BY-BEFORE-LISTENER',
                $original->getInitiatingPartyName(),
                $original->getPaymentInfoId(),
                $original->getDueDate(),
                $original->getCreditorName(),
                $original->getCreditorIban(),
                $original->getSequenceType(),
                $original->getCreditorId(),
                $original->getLocalInstrumentCode(),
            );
            foreach ($original->getTransactions() as $tx) {
                $modified->addTransaction($tx);
            }
            $event->setDirectDebitData($modified);
        });

        $generator = new DirectDebitGenerator(new IbanValidator(), null, false, $dispatcher);
        $data      = new DirectDebitData(
            'MSG-ORIGINAL',
            'My Company',
            'PMT-001',
            new DateTime('2024-01-20'),
            'Creditor Name',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );
        $data->addTransaction(new DirectDebitTransaction(
            25.00,
            'GB82WEST12345698765432',
            'John Doe',
            'MANDATE-001',
            new DateTime('2024-01-01'),
            'E2E-001',
        ));

        $xml = $generator->generate($data);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
        $this->assertStringContainsString('MODIFIED-BY-BEFORE-LISTENER', $xml);
        $this->assertStringNotContainsString('MSG-ORIGINAL', $xml);
    }

    /**
     * Tests generation with BIC lookup service (creditor BIC auto-filled).
     */
    public function testGenerateWithBicLookupService(): void
    {
        $bicLookup = new class implements \Nowo\SepaPaymentBundle\Lookup\BicLookupServiceInterface {
            public function lookupBic(string $iban): ?string
            {
                return str_starts_with($iban, 'ES') ? 'CAIXESBBXXX' : null;
            }

            public function isAvailable(string $iban): bool
            {
                return str_starts_with($iban, 'ES');
            }
        };
        $generator = new DirectDebitGenerator(new IbanValidator(), null, false, null, null, $bicLookup);

        $data = new DirectDebitData(
            'MSG-BIC',
            'My Company',
            'PMT-001',
            new DateTime('2024-01-20'),
            'Creditor Name',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );
        $data->addTransaction(new DirectDebitTransaction(
            25.00,
            'GB82WEST12345698765432',
            'Bob',
            'MANDATE-003',
            new DateTime('2024-01-01'),
            'E2E-BIC',
        ));

        $xml = $generator->generate($data);
        $this->assertStringContainsString('CAIXESBBXXX', $xml);
    }

    /**
     * Tests generation with BIC lookup for transaction debtor (debtor BIC auto-filled when null).
     */
    public function testGenerateWithBicLookupForTransactionDebtor(): void
    {
        $bicLookup = new class implements \Nowo\SepaPaymentBundle\Lookup\BicLookupServiceInterface {
            public function lookupBic(string $iban): ?string
            {
                return str_starts_with($iban, 'ES') ? 'CAIXESBBXXX' : (str_starts_with($iban, 'GB') ? 'WESTGB22' : null);
            }

            public function isAvailable(string $iban): bool
            {
                return str_starts_with($iban, 'ES') || str_starts_with($iban, 'GB');
            }
        };
        $generator = new DirectDebitGenerator(new IbanValidator(), null, false, null, null, $bicLookup);

        $data = new DirectDebitData(
            'MSG-BIC-TX',
            'My Company',
            'PMT-001',
            new DateTime('2024-01-20'),
            'Creditor Name',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );
        $tx = new DirectDebitTransaction(
            10.00,
            'GB82WEST12345698765432',
            'Debtor Name',
            'MANDATE-X',
            new DateTime('2024-01-01'),
            'E2E-BIC-TX',
        );
        $tx->setDebtorBic(null);
        $data->addTransaction($tx);

        $xml = $generator->generate($data);
        $this->assertStringContainsString('WESTGB22', $xml);
    }

    /**
     * Tests generation with creditor address (object API).
     */
    public function testGenerateWithCreditorAddressObject(): void
    {
        $data = new DirectDebitData(
            'MSG-ADDR',
            'My Company',
            'PMT-001',
            new DateTime('2024-01-20'),
            'Creditor Name',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );
        $data->setCreditorAddressFromArray([
            'street'     => 'Calle Principal 1',
            'city'       => 'Madrid',
            'postalCode' => '28001',
            'country'    => 'ES',
        ]);
        $data->addTransaction(new DirectDebitTransaction(
            10.00,
            'GB82WEST12345698765432',
            'Debtor',
            'MANDATE-004',
            new DateTime('2024-01-01'),
            'E2E-ADDR',
        ));

        $xml = $this->generator->generate($data);
        $this->assertStringContainsString('PstlAdr', $xml);
        $this->assertStringContainsString('Madrid', $xml);
    }

    /**
     * Tests generation with XSD validation enabled and validation failure.
     */
    public function testGenerateWithXsdValidationFailure(): void
    {
        $xsdValidator = $this->createMock(\Nowo\SepaPaymentBundle\Validator\XsdValidator::class);
        $xsdValidator->method('validateDirectDebit')->willThrowException(new InvalidArgumentException('XSD error'));
        $generator = new DirectDebitGenerator(new IbanValidator(), $xsdValidator, true);

        $data = new DirectDebitData(
            'MSG-XSD',
            'My Company',
            'PMT-001',
            new DateTime('2024-01-20'),
            'Creditor Name',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );
        $data->addTransaction(new DirectDebitTransaction(
            1.00,
            'GB82WEST12345698765432',
            'X',
            'MANDATE-X',
            new DateTime('2024-01-01'),
            'E2E-X',
        ));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Generated XML failed XSD validation');
        $generator->generate($data);
    }

    /**
     * Tests that when generation fails, the logger receives the failure log (covers catch block with logger).
     */
    public function testGenerateFailureWithLogger(): void
    {
        $testLogger   = new TestLogger();
        $sepaLogger   = new SepaPaymentLogger($testLogger);
        $xsdValidator = $this->createMock(\Nowo\SepaPaymentBundle\Validator\XsdValidator::class);
        $xsdValidator->method('validateDirectDebit')->willThrowException(new InvalidArgumentException('XSD validation failed'));
        $generator = new DirectDebitGenerator(new IbanValidator(), $xsdValidator, true, null, $sepaLogger);

        $data = new DirectDebitData(
            'MSG-LOG-FAIL',
            'My Company',
            'PMT-001',
            new DateTime('2024-01-20'),
            'Creditor Name',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );
        $data->addTransaction(new DirectDebitTransaction(
            1.00,
            'GB82WEST12345698765432',
            'X',
            'MANDATE-X',
            new DateTime('2024-01-01'),
            'E2E-X',
        ));

        try {
            $generator->generate($data);
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException) {
            $this->assertCount(2, $testLogger->logs);
            $this->assertEquals('SEPA Direct Debit generation started', $testLogger->logs[0]['message']);
            $this->assertEquals('SEPA Direct Debit generation failed', $testLogger->logs[1]['message']);
            $this->assertEquals('MSG-LOG-FAIL', $testLogger->logs[1]['context']['message_id']);
            $this->assertStringContainsString('XSD', $testLogger->logs[1]['context']['error']);
        }
    }

    /**
     * Tests that when a listener modifies the XML in AfterDirectDebitGenerationEvent, the generator returns the modified XML.
     */
    public function testGenerateWithAfterEventModifiesXml(): void
    {
        $dispatcher  = new EventDispatcher();
        $modifiedXml = '<?xml version="1.0"?><direct-debit-modified/>';
        $dispatcher->addListener(AfterDirectDebitGenerationEvent::class, static function (AfterDirectDebitGenerationEvent $event) use ($modifiedXml): void {
            $event->setXml($modifiedXml);
        });

        $generator = new DirectDebitGenerator(new IbanValidator(), null, false, $dispatcher);
        $data      = new DirectDebitData(
            'MSG-AFTER',
            'My Company',
            'PMT-001',
            new DateTime('2024-01-20'),
            'Creditor Name',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );
        $data->addTransaction(new DirectDebitTransaction(
            10.00,
            'GB82WEST12345698765432',
            'John Doe',
            'MANDATE-AFTER',
            new DateTime('2024-01-01'),
            'E2E-AFTER',
        ));

        $xml = $generator->generate($data);
        $this->assertSame($modifiedXml, $xml);
        $this->assertStringContainsString('direct-debit-modified', $xml);
    }

    /**
     * Tests that addAddressesToXml returns the original XML when the XML string is invalid (loadXML fails).
     */
    public function testAddAddressesToXmlReturnsOriginalWhenXmlInvalid(): void
    {
        $invalidXml = '<?xml version="1.0"?><root><unclosed>';
        $data       = new DirectDebitData(
            'MSG-001',
            'Company',
            'PMT-001',
            new DateTime('2024-01-20'),
            'Creditor',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );
        $ref    = new ReflectionClass(DirectDebitGenerator::class);
        $method = $ref->getMethod('addAddressesToXml');
        $result = $method->invoke($this->generator, $invalidXml, $data);
        $this->assertSame($invalidXml, $result);
    }

    /**
     * Tests addAddressesToXml with XML without namespace so XPath fallback (without ns prefix) is used.
     */
    public function testAddAddressesToXmlWithXmlWithoutNamespaceUsesXPathFallback(): void
    {
        $xmlNoNs = '<?xml version="1.0"?><Document><PmtInf><DrctDbtTxInf><Dbtr><Nm>Debtor</Nm></Dbtr><Cdtr><Nm>Creditor</Nm></Cdtr></DrctDbtTxInf></PmtInf></Document>';
        $data    = new DirectDebitData(
            'MSG-001',
            'Company',
            'PMT-001',
            new DateTime('2024-01-20'),
            'Creditor',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );
        $data->setCreditorAddress(['street' => 'S1', 'city' => 'C1', 'postalCode' => 'P1', 'country' => 'ES']);
        $data->addTransaction(new DirectDebitTransaction(
            10.00,
            'ES9121000418450200051332',
            'Debtor',
            'MANDATE-01',
            new DateTime('2024-01-01'),
            'E2E-01',
        ));
        $data->getTransactions()[0]->setDebtorAddress(['street' => 'S2', 'city' => 'C2', 'postalCode' => 'P2', 'country' => 'ES']);
        $ref    = new ReflectionClass(DirectDebitGenerator::class);
        $method = $ref->getMethod('addAddressesToXml');
        $result = $method->invoke($this->generator, $xmlNoNs, $data);
        $this->assertStringContainsString('PstlAdr', $result);
        $this->assertStringContainsString('S1', $result);
        $this->assertStringContainsString('S2', $result);
    }

    /**
     * Tests addAddressesToXml when parent already has PstlAdr (removeChild); uses namespace so it is found and replaced.
     */
    public function testAddAddressesToXmlReplacesExistingPstlAdr(): void
    {
        $ns              = 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02';
        $xmlWithExisting = '<?xml version="1.0"?><Document xmlns="' . $ns . '"><PmtInf><DrctDbtTxInf><Cdtr><Nm>Creditor</Nm><PstlAdr><StrtNm>Old</StrtNm></PstlAdr></Cdtr><Dbtr><Nm>Debtor</Nm></Dbtr></DrctDbtTxInf></PmtInf></Document>';
        $data            = new DirectDebitData(
            'MSG-001',
            'Company',
            'PMT-001',
            new DateTime('2024-01-20'),
            'Creditor',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );
        $data->setCreditorAddress(['street' => 'NewStreet', 'country' => 'ES']);
        $ref    = new ReflectionClass(DirectDebitGenerator::class);
        $method = $ref->getMethod('addAddressesToXml');
        $result = $method->invoke($this->generator, $xmlWithExisting, $data);
        $this->assertStringContainsString('NewStreet', $result);
        $this->assertStringNotContainsString('Old', $result);
    }

    /**
     * Tests addAddressesToXml when there are more transactions with debtor address than Dbtr nodes (index out of range).
     */
    public function testAddAddressesToXmlSkipsDebtorAddressWhenIndexOutOfRange(): void
    {
        $ns         = 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02';
        $xmlOneDbtr = '<?xml version="1.0"?><Document xmlns="' . $ns . '"><PmtInf><DrctDbtTxInf><Cdtr><Nm>C</Nm></Cdtr><Dbtr><Nm>D1</Nm></Dbtr></DrctDbtTxInf></PmtInf></Document>';
        $data       = new DirectDebitData(
            'MSG-001',
            'Company',
            'PMT-001',
            new DateTime('2024-01-20'),
            'Creditor',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );
        $t1 = new DirectDebitTransaction(10.00, 'ES9121000418450200051332', 'D1', 'M1', new DateTime('2024-01-01'), 'E1');
        $t1->setDebtorAddress(['street' => 'First', 'country' => 'ES']);
        $t2 = new DirectDebitTransaction(20.00, 'ES9121000418450200051332', 'D2', 'M2', new DateTime('2024-01-01'), 'E2');
        $t2->setDebtorAddress(['street' => 'Second', 'country' => 'ES']);
        $data->addTransaction($t1);
        $data->addTransaction($t2);
        $ref    = new ReflectionClass(DirectDebitGenerator::class);
        $method = $ref->getMethod('addAddressesToXml');
        $result = $method->invoke($this->generator, $xmlOneDbtr, $data);
        $this->assertStringContainsString('First', $result);
        $this->assertStringNotContainsString('Second', $result);
    }

    /**
     * Tests addAddressesToXml with address that has all empty fields (createPostalAddressElement returns without adding).
     */
    public function testAddAddressesToXmlWithAllEmptyAddressFieldsDoesNotAddPstlAdr(): void
    {
        $xmlNoNs = '<?xml version="1.0"?><Document><PmtInf><DrctDbtTxInf><Cdtr><Nm>C</Nm></Cdtr><Dbtr><Nm>D</Nm></Dbtr></DrctDbtTxInf></PmtInf></Document>';
        $data    = new DirectDebitData(
            'MSG-001',
            'Company',
            'PMT-001',
            new DateTime('2024-01-20'),
            'Creditor',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );
        $data->setCreditorAddress(['street' => '', 'city' => '', 'postalCode' => '', 'country' => '']);
        $ref    = new ReflectionClass(DirectDebitGenerator::class);
        $method = $ref->getMethod('addAddressesToXml');
        $result = $method->invoke($this->generator, $xmlNoNs, $data);
        $this->assertStringNotContainsString('PstlAdr', $result);
    }

    /**
     * Tests setCreditorPostalAddress uses setCreditorPostalAddress when available (first branch).
     */
    public function testSetCreditorPostalAddressUsesSetCreditorPostalAddressWhenAvailable(): void
    {
        $called = false;
        $mock   = new class($called) {
            /** @var bool */
            public $called;

            public function __construct(bool &$called)
            {
                $this->called = &$called;
            }

            public function setCreditorPostalAddress(string $street, string $city, string $postalCode, string $country): void
            {
                $this->called = true;
            }
        };
        $ref    = new ReflectionClass(DirectDebitGenerator::class);
        $method = $ref->getMethod('setCreditorPostalAddress');
        $method->invoke($this->generator, $mock, [
            'street'     => 'Calle',
            'city'       => 'Madrid',
            'postalCode' => '28001',
            'country'    => 'ES',
        ]);
        $this->assertTrue($mock->called);
    }

    /**
     * Tests setCreditorPostalAddress uses setPostalAddress when setCreditorPostalAddress is not available.
     */
    public function testSetCreditorPostalAddressUsesSetPostalAddressWhenSetCreditorPostalAddressNotAvailable(): void
    {
        $called = false;
        $mock   = new class($called) {
            /** @var bool */
            public $called;

            public function __construct(bool &$called)
            {
                $this->called = &$called;
            }

            public function setPostalAddress(string $street, string $city, string $postalCode, string $country): void
            {
                $this->called = true;
            }
        };
        $ref    = new ReflectionClass(DirectDebitGenerator::class);
        $method = $ref->getMethod('setCreditorPostalAddress');
        $method->invoke($this->generator, $mock, [
            'street'     => 'Calle',
            'city'       => 'Madrid',
            'postalCode' => '28001',
            'country'    => 'ES',
        ]);
        $this->assertTrue($mock->called);
    }

    /**
     * Tests setCreditorPostalAddress uses setAddress when setCreditorPostalAddress and setPostalAddress are not available.
     */
    public function testSetCreditorPostalAddressUsesSetAddressWhenOnlySetAddressAvailable(): void
    {
        $called = false;
        $mock   = new class($called) {
            /** @var bool */
            public $called;

            public function __construct(bool &$called)
            {
                $this->called = &$called;
            }

            public function setAddress(string $street, string $city, string $postalCode, string $country): void
            {
                $this->called = true;
            }
        };
        $ref    = new ReflectionClass(DirectDebitGenerator::class);
        $method = $ref->getMethod('setCreditorPostalAddress');
        $method->invoke($this->generator, $mock, [
            'street'     => 'Calle',
            'city'       => 'Madrid',
            'postalCode' => '28001',
            'country'    => 'ES',
        ]);
        $this->assertTrue($mock->called);
    }

    /**
     * Tests setPostalAddress (transfer info) uses setDebtorPostalAddress when available.
     */
    public function testSetPostalAddressUsesSetDebtorPostalAddressWhenAvailable(): void
    {
        $called = false;
        $mock   = new class($called) {
            /** @var bool */
            public $called;

            public function __construct(bool &$called)
            {
                $this->called = &$called;
            }

            public function setPostalAddress(string $street): void
            {
            }

            public function setDebtorPostalAddress(string $street, string $city, string $postalCode, string $country): void
            {
                $this->called = true;
            }
        };
        $ref    = new ReflectionClass(DirectDebitGenerator::class);
        $method = $ref->getMethod('setPostalAddress');
        $method->invoke($this->generator, $mock, [
            'street'     => 'Calle',
            'city'       => 'Madrid',
            'postalCode' => '28001',
            'country'    => 'ES',
        ]);
        $this->assertTrue($mock->called);
    }

    /**
     * Tests setPostalAddress (transfer info) uses setAddress when setDebtorPostalAddress is not available.
     */
    public function testSetPostalAddressUsesSetAddressWhenSetDebtorPostalAddressNotAvailable(): void
    {
        $setAddressCalled = false;
        $mock             = new class($setAddressCalled) {
            /** @var bool */
            public $setAddressCalled;

            public function __construct(bool &$setAddressCalled)
            {
                $this->setAddressCalled = &$setAddressCalled;
            }

            public function setPostalAddress(string $street): void
            {
                // Required so first method_exists is true; we want to hit the elseif setAddress branch
            }

            public function setAddress(string $street, string $city, string $postalCode, string $country): void
            {
                $this->setAddressCalled = true;
            }
        };
        $ref    = new ReflectionClass(DirectDebitGenerator::class);
        $method = $ref->getMethod('setPostalAddress');
        $method->invoke($this->generator, $mock, [
            'street'     => 'Calle',
            'city'       => 'Madrid',
            'postalCode' => '28001',
            'country'    => 'ES',
        ]);
        $this->assertTrue($mock->setAddressCalled);
    }

    /**
     * Tests addAddressesToXml uses insertBefore when parent has Nm with nextSibling (covers createPostalAddressElement insertBefore branch).
     */
    public function testAddAddressesToXmlInsertBeforeWhenNmHasNextSibling(): void
    {
        $ns  = 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02';
        $xml = '<?xml version="1.0"?>
<Document xmlns="' . $ns . '">
  <CstmrDrctDbtInitn>
    <GrpHdr><MsgId>M1</MsgId><NbOfTxs>1</NbOfTxs><CreDtTm>2024-01-15T10:00:00</CreDtTm><InitgPty><Nm>Init</Nm></InitgPty></GrpHdr>
    <PmtInf>
      <Cdtr><Nm>Creditor</Nm><Id><OrgId><Othr><Id>ID1</Id></Othr></OrgId></Id></Cdtr>
      <DrctDbtTxInf><Dbtr><Nm>Debtor</Nm></Dbtr></DrctDbtTxInf>
    </PmtInf>
  </CstmrDrctDbtInitn>
</Document>';
        $data = new DirectDebitData(
            'REF',
            'Name',
            'PMT-001',
            new DateTime('2024-01-20'),
            'Creditor',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );
        $data->setCreditorAddressFromArray([
            'street'     => 'Calle',
            'city'       => 'Madrid',
            'postalCode' => '28001',
            'country'    => 'ES',
        ]);
        $ref    = new ReflectionClass(DirectDebitGenerator::class);
        $method = $ref->getMethod('addAddressesToXml');
        $result = $method->invoke($this->generator, $xml, $data);
        $this->assertStringContainsString('PstlAdr', $result);
        $this->assertStringContainsString('Calle', $result);
    }

    /**
     * Tests addAddressesToXml catches Throwable during DOM manipulation and returns original XML.
     */
    public function testAddAddressesToXmlCatchesThrowableAndReturnsOriginalXml(): void
    {
        $validXml = $this->generator->generate(
            (new DirectDebitData(
                'REF',
                'Name',
                'PMT-001',
                new DateTime('2024-01-20'),
                'Creditor',
                'ES9121000418450200051332',
                'FRST',
                'ES1234567890123456789012',
                'CORE',
            ))->addTransaction(
                new DirectDebitTransaction(10.00, 'ES9121000418450200051332', 'Debtor', 'M-001', new DateTime('2024-01-01'), 'E2E-1'),
            ),
        );
        $data = new DirectDebitData(
            'REF',
            'Name',
            'PMT-001',
            new DateTime('2024-01-20'),
            'Creditor',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE',
        );
        $refData = new ReflectionClass(DirectDebitData::class);
        $prop    = $refData->getProperty('creditorAddress');
        $prop->setValue($data, [
            'street' => new class {
                public function __toString(): string
                {
                    throw new Exception('invalid');
                }
            },
            'city'       => null,
            'postalCode' => null,
            'country'    => null,
        ]);
        $ref    = new ReflectionClass(DirectDebitGenerator::class);
        $method = $ref->getMethod('addAddressesToXml');
        $result = $method->invoke($this->generator, $validXml, $data);
        $this->assertSame($validXml, $result);
    }

    /**
     * Covers addCreditorAddressToDom (private) via reflection with valid DOM containing Cdtr.
     */
    public function testAddCreditorAddressToDomViaReflectionAddsAddress(): void
    {
        $ns  = 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02';
        $xml = '<?xml version="1.0"?><Document xmlns="' . $ns . '"><CstmrDrctDbtInitn><PmtInf><Cdtr><Nm>Creditor</Nm></Cdtr></PmtInf></CstmrDrctDbtInitn></Document>';
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('ns', $ns);
        $ref    = new ReflectionClass(DirectDebitGenerator::class);
        $method = $ref->getMethod('addCreditorAddressToDom');
        $method->invoke($this->generator, $dom, $xpath, [
            'street'     => 'Calle',
            'city'       => 'Madrid',
            'postalCode' => '28001',
            'country'    => 'ES',
        ], $ns);
        $xmlOutput = $dom->saveXML();
        $this->assertIsString($xmlOutput);
        $this->assertStringContainsString('PstlAdr', $xmlOutput);
        $this->assertStringContainsString('Calle', $xmlOutput);
    }

    /**
     * Covers addDebtorAddressToDom (private) via reflection with valid DOM containing Dbtr.
     */
    public function testAddDebtorAddressToDomViaReflectionAddsAddress(): void
    {
        $ns  = 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02';
        $xml = '<?xml version="1.0"?><Document xmlns="' . $ns . '"><CstmrDrctDbtInitn><PmtInf><DrctDbtTxInf><Dbtr><Nm>Debtor</Nm></Dbtr></DrctDbtTxInf></PmtInf></CstmrDrctDbtInitn></Document>';
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('ns', $ns);
        $ref    = new ReflectionClass(DirectDebitGenerator::class);
        $method = $ref->getMethod('addDebtorAddressToDom');
        $method->invoke($this->generator, $dom, $xpath, [
            'street'     => 'Avenida',
            'city'       => 'Barcelona',
            'postalCode' => '08001',
            'country'    => 'ES',
        ], 0, $ns);
        $xmlOutput = $dom->saveXML();
        $this->assertIsString($xmlOutput);
        $this->assertStringContainsString('PstlAdr', $xmlOutput);
        $this->assertStringContainsString('Avenida', $xmlOutput);
    }

    /**
     * Covers addCreditorAddressToDom defensive return when item(0) is not DOMElement (line 669).
     */
    public function testAddCreditorAddressToDomReturnsEarlyWhenNodeIsNotDomElement(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML('<?xml version="1.0"?><r>x</r>');
        $xpath  = new XPathReturningTextNodeList($dom);
        $ref    = new ReflectionClass(DirectDebitGenerator::class);
        $method = $ref->getMethod('addCreditorAddressToDom');
        $method->invoke($this->generator, $dom, $xpath, [
            'street' => 'Calle', 'city' => 'Madrid', 'postalCode' => '28001', 'country' => 'ES',
        ], 'urn:test');
        $xmlOutput = $dom->saveXML();
        $this->assertIsString($xmlOutput);
        $this->assertStringNotContainsString('PstlAdr', $xmlOutput);
    }

    /**
     * Covers addCreditorAddressToDom defensive return when no Cdtr nodes (line 663).
     */
    public function testAddCreditorAddressToDomReturnsEarlyWhenNoNodes(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML('<?xml version="1.0"?><r/>');
        $xpath  = new XPathReturningEmptyNodeList($dom);
        $ref    = new ReflectionClass(DirectDebitGenerator::class);
        $method = $ref->getMethod('addCreditorAddressToDom');
        $method->invoke($this->generator, $dom, $xpath, [
            'street' => 'Calle', 'city' => 'Madrid', 'postalCode' => '28001', 'country' => 'ES',
        ], 'urn:test');
        $xmlOutput = $dom->saveXML();
        $this->assertIsString($xmlOutput);
        $this->assertStringNotContainsString('PstlAdr', $xmlOutput);
    }

    /**
     * Covers addDebtorAddressToDom defensive return when item(index) is not DOMElement (line 699).
     */
    public function testAddDebtorAddressToDomReturnsEarlyWhenNodeIsNotDomElement(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML('<?xml version="1.0"?><r>x</r>');
        $xpath  = new XPathReturningTextNodeList($dom);
        $ref    = new ReflectionClass(DirectDebitGenerator::class);
        $method = $ref->getMethod('addDebtorAddressToDom');
        $method->invoke($this->generator, $dom, $xpath, [
            'street' => 'Calle', 'city' => 'Madrid', 'postalCode' => '28001', 'country' => 'ES',
        ], 0, 'urn:test');
        $xmlOutput = $dom->saveXML();
        $this->assertIsString($xmlOutput);
        $this->assertStringNotContainsString('PstlAdr', $xmlOutput);
    }

    /**
     * Covers addDebtorAddressToDom defensive return when length <= index (line 689).
     */
    public function testAddDebtorAddressToDomReturnsEarlyWhenLengthLteIndex(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML('<?xml version="1.0"?><r/>');
        $xpath  = new XPathReturningEmptyNodeList($dom);
        $ref    = new ReflectionClass(DirectDebitGenerator::class);
        $method = $ref->getMethod('addDebtorAddressToDom');
        $method->invoke($this->generator, $dom, $xpath, [
            'street' => 'Calle', 'city' => 'Madrid', 'postalCode' => '28001', 'country' => 'ES',
        ], 0, 'urn:test');
        $xmlOutput = $dom->saveXML();
        $this->assertIsString($xmlOutput);
        $this->assertStringNotContainsString('PstlAdr', $xmlOutput);
    }
}
