<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Generator;

use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Logger\SepaPaymentLogger;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\CreditTransferData;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\Transaction;
use Nowo\SepaPaymentBundle\Tests\Logger\TestLogger;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for CreditTransferGenerator.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class CreditTransferGeneratorTest extends TestCase
{
    /**
     * Credit transfer generator instance.
     *
     * @var CreditTransferGenerator
     */
    private CreditTransferGenerator $generator;

    /**
     * Sets up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $ibanValidator = new IbanValidator();
        $this->generator = new CreditTransferGenerator($ibanValidator);
    }

    /**
     * Tests XML generation with valid data.
     *
     * @return void
     */
    public function testGenerateXml(): void
    {
        $creditTransferData = new CreditTransferData(
            'MSG-001',
            new \DateTime('2024-01-15 10:00:00'),
            'My Company',
            'PMT-001',
            'ES9121000418450200051332',
            'My Company Name',
            new \DateTime('2024-01-20')
        );

        $creditTransferData->setCreditorBic('CAIXESBBXXX');
        $creditTransferData->setBatchBooking(true);

        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'GB82WEST12345698765432',
            'John Doe'
        );

        $transaction->setCreditorBic('WESTGB22');
        $transaction->setRemittanceInformation('Invoice 12345');

        $creditTransferData->addTransaction($transaction);

        $xml = $this->generator->generate($creditTransferData);

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

        $creditTransferData = new CreditTransferData(
            'MSG-001',
            new \DateTime('2024-01-15 10:00:00'),
            'My Company',
            'PMT-001',
            'INVALID-IBAN',
            'My Company Name',
            new \DateTime('2024-01-20')
        );

        $this->generator->generate($creditTransferData);
    }

    /**
     * Tests XML generation with invalid transaction creditor IBAN.
     *
     * @return void
     */
    public function testGenerateXmlWithInvalidTransactionCreditorIban(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid creditor IBAN');

        $creditTransferData = new CreditTransferData(
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

        $creditTransferData->addTransaction($transaction);

        $this->generator->generate($creditTransferData);
    }

    /**
     * Tests XML generation with multiple transactions.
     *
     * @return void
     */
    public function testGenerateXmlWithMultipleTransactions(): void
    {
        $creditTransferData = new CreditTransferData(
            'MSG-001',
            new \DateTime('2024-01-15 10:00:00'),
            'My Company',
            'PMT-001',
            'ES9121000418450200051332',
            'My Company Name',
            new \DateTime('2024-01-20')
        );

        $creditTransferData->addTransaction(new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'GB82WEST12345698765432',
            'John Doe'
        ));

        $creditTransferData->addTransaction(new Transaction(
            'E2E-002',
            200.75,
            'EUR',
            'FR1420041010050500013M02606',
            'Jane Smith'
        ));

        $xml = $this->generator->generate($creditTransferData);

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
        $creditTransferData = new CreditTransferData(
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

        $creditTransferData->addTransaction($transaction);

        $xml = $this->generator->generate($creditTransferData);

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
        $creditTransferData = new CreditTransferData(
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

        $creditTransferData->addTransaction($transaction);

        $xml = $this->generator->generate($creditTransferData);

        // Should properly escape special characters in XML
        // The XML should not contain unescaped special characters that would break XML structure
        $this->assertStringNotContainsString('<Test>', $xml, 'XML should not contain unescaped < > tags');
        // Verify XML is well-formed by checking it's parseable
        $this->assertIsString($xml);
        $this->assertStringStartsWith('<?xml', $xml);
        // Verify XML can be parsed (if it contains unescaped characters, this will fail)
        $dom = new \DOMDocument();
        $this->assertTrue(@$dom->loadXML($xml), 'Generated XML should be well-formed');
    }

    /**
     * Tests generateFromArray with valid data.
     *
     * @return void
     */
    public function testGenerateFromArray(): void
    {
        $data = [
            'reference' => 'MSG-001',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'requestedExecutionDate' => '2024-01-20',
            'creditorName' => 'My Company Name',
            'creditorIban' => 'ES9121000418450200051332',
            'transactions' => [
                [
                    'amount' => 100.50,
                    'creditorIban' => 'GB82WEST12345698765432',
                    'creditorName' => 'John Doe',
                    'endToEndId' => 'E2E-001',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
        $this->assertStringContainsString('MSG-001', $xml);
        $this->assertStringContainsString('PMT-001', $xml);
        $this->assertStringContainsString('ES9121000418450200051332', $xml);
        $this->assertStringContainsString('GB82WEST12345698765432', $xml);
        $this->assertStringContainsString('100.50', $xml);
        $this->assertStringContainsString('E2E-001', $xml);
    }

    /**
     * Tests generateFromArray with snake_case field names.
     *
     * @return void
     */
    public function testGenerateFromArrayWithSnakeCase(): void
    {
        $data = [
            'message_id' => 'MSG-001',
            'initiating_party_name' => 'My Company',
            'payment_name' => 'PMT-001',
            'requested_execution_date' => '2024-01-20',
            'creditor_name' => 'My Company Name',
            'creditor_iban' => 'ES9121000418450200051332',
            'items' => [
                [
                    'instruction_id' => 'E2E-001',
                    'amount' => 100.50,
                    'creditor_iban' => 'GB82WEST12345698765432',
                    'creditor_name' => 'John Doe',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
        $this->assertStringContainsString('MSG-001', $xml);
        $this->assertStringContainsString('PMT-001', $xml);
        $this->assertStringContainsString('ES9121000418450200051332', $xml);
        $this->assertStringContainsString('GB82WEST12345698765432', $xml);
        $this->assertStringContainsString('100.50', $xml);
        $this->assertStringContainsString('E2E-001', $xml);
    }

    /**
     * Tests generateFromArray with creditor addresses.
     *
     * @return void
     */
    public function testGenerateFromArrayWithAddresses(): void
    {
        $data = [
            'reference' => 'MSG-001',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'requestedExecutionDate' => '2024-01-20',
            'creditorName' => 'My Company Name',
            'creditorIban' => 'ES9121000418450200051332',
            'creditorAddress' => [
                'street' => '123 Business Street',
                'city' => 'Madrid',
                'postalCode' => '28001',
                'country' => 'ES',
            ],
            'transactions' => [
                [
                    'amount' => 100.50,
                    'creditorIban' => 'GB82WEST12345698765432',
                    'creditorName' => 'John Doe',
                    'endToEndId' => 'E2E-001',
                    'creditorAddress' => [
                        'street' => '456 Customer Avenue',
                        'city' => 'London',
                        'postalCode' => 'SW1A 1AA',
                        'country' => 'GB',
                    ],
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        // XML should be generated successfully
        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
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
     *
     * @return void
     */
    public function testGenerateFromArrayWithAddressesSnakeCase(): void
    {
        $data = [
            'message_id' => 'MSG-001',
            'initiating_party_name' => 'My Company',
            'payment_name' => 'PMT-001',
            'requested_execution_date' => '2024-01-20',
            'creditor_name' => 'My Company Name',
            'creditor_iban' => 'ES9121000418450200051332',
            'creditor_street' => '123 Business Street',
            'creditor_city' => 'Madrid',
            'creditor_postal_code' => '28001',
            'creditor_country' => 'ES',
            'items' => [
                [
                    'instruction_id' => 'E2E-001',
                    'amount' => 100.50,
                    'creditor_iban' => 'GB82WEST12345698765432',
                    'creditor_name' => 'John Doe',
                    'creditor_street' => '456 Customer Avenue',
                    'creditor_city' => 'London',
                    'creditor_postal_code' => 'SW1A 1AA',
                    'creditor_country' => 'GB',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        // XML should be generated successfully
        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
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
     *
     * @return void
     */
    public function testGenerateXmlWithCreditorAddress(): void
    {
        $creditTransferData = new CreditTransferData(
            'MSG-001',
            new \DateTime('2024-01-15 10:00:00'),
            'My Company',
            'PMT-001',
            'ES9121000418450200051332',
            'My Company Name',
            new \DateTime('2024-01-20')
        );

        $creditTransferData->setCreditorAddress([
            'street' => '789 Business Road',
            'city' => 'Barcelona',
            'postalCode' => '08001',
            'country' => 'ES',
        ]);

        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'GB82WEST12345698765432',
            'John Doe'
        );

        $creditTransferData->addTransaction($transaction);

        $xml = $this->generator->generate($creditTransferData);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
        $this->assertStringContainsString('PstlAdr', $xml);
        $this->assertStringContainsString('789 Business Road', $xml);
        $this->assertStringContainsString('Barcelona', $xml);
        $this->assertStringContainsString('08001', $xml);
    }

    /**
     * Tests XML generation with transaction creditor address using object methods.
     *
     * @return void
     */
    public function testGenerateXmlWithTransactionCreditorAddress(): void
    {
        $creditTransferData = new CreditTransferData(
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

        $transaction->setCreditorAddress([
            'street' => '321 Customer Street',
            'city' => 'Manchester',
            'postalCode' => 'M1 1AA',
            'country' => 'GB',
        ]);

        $creditTransferData->addTransaction($transaction);

        $xml = $this->generator->generate($creditTransferData);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
        $this->assertStringContainsString('PstlAdr', $xml);
        $this->assertStringContainsString('321 Customer Street', $xml);
        $this->assertStringContainsString('Manchester', $xml);
        $this->assertStringContainsString('M1 1AA', $xml);
    }

    /**
     * Tests XML generation with both creditor and debtor addresses.
     *
     * @return void
     */
    public function testGenerateXmlWithBothAddresses(): void
    {
        $creditTransferData = new CreditTransferData(
            'MSG-001',
            new \DateTime('2024-01-15 10:00:00'),
            'My Company',
            'PMT-001',
            'ES9121000418450200051332',
            'My Company Name',
            new \DateTime('2024-01-20')
        );

        $creditTransferData->setCreditorAddress([
            'street' => '111 Creditor Ave',
            'city' => 'Valencia',
            'postalCode' => '46001',
            'country' => 'ES',
        ]);

        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'GB82WEST12345698765432',
            'John Doe'
        );

        $transaction->setCreditorAddress([
            'street' => '222 Debtor Blvd',
            'city' => 'Leeds',
            'postalCode' => 'LS1 1AA',
            'country' => 'GB',
        ]);

        $creditTransferData->addTransaction($transaction);

        $xml = $this->generator->generate($creditTransferData);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
        $this->assertStringContainsString('PstlAdr', $xml);
        // Creditor address (from CreditTransferData - represents debtor/company that pays)
        $this->assertStringContainsString('111 Creditor Ave', $xml);
        $this->assertStringContainsString('Valencia', $xml);
        // Creditor address (from Transaction - represents creditor/supplier that receives)
        $this->assertStringContainsString('222 Debtor Blvd', $xml);
        $this->assertStringContainsString('Leeds', $xml);
    }

    /**
     * Tests that addresses are optional and not included when not provided.
     *
     * @return void
     */
    public function testGenerateXmlWithoutAddresses(): void
    {
        $data = [
            'reference' => 'MSG-001',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'requestedExecutionDate' => '2024-01-20',
            'creditorName' => 'My Company Name',
            'creditorIban' => 'ES9121000418450200051332',
            'transactions' => [
                [
                    'amount' => 100.50,
                    'creditorIban' => 'GB82WEST12345698765432',
                    'creditorName' => 'John Doe',
                    'endToEndId' => 'E2E-001',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
        // Addresses should NOT appear when not provided
        $this->assertStringNotContainsString('PstlAdr', $xml);
    }

    /**
     * Tests that empty address arrays are not included.
     *
     * @return void
     */
    public function testGenerateXmlWithEmptyAddressArray(): void
    {
        $data = [
            'reference' => 'MSG-001',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'requestedExecutionDate' => '2024-01-20',
            'creditorName' => 'My Company Name',
            'creditorIban' => 'ES9121000418450200051332',
            'creditorAddress' => [], // Empty array
            'transactions' => [
                [
                    'amount' => 100.50,
                    'creditorIban' => 'GB82WEST12345698765432',
                    'creditorName' => 'John Doe',
                    'endToEndId' => 'E2E-001',
                    'creditorAddress' => [], // Empty array
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
        // Empty address arrays should NOT create PstlAdr elements
        $this->assertStringNotContainsString('PstlAdr', $xml);
    }

    /**
     * Tests generateFromArray with missing required field.
     *
     * @return void
     */
    public function testGenerateFromArrayMissingRequiredField(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: reference');

        $data = [
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'requestedExecutionDate' => '2024-01-20',
            'creditorName' => 'My Company Name',
            'creditorIban' => 'ES9121000418450200051332',
        ];

        $this->generator->generateFromArray($data);
    }

    /**
     * Tests generateFromArray with missing required transaction field.
     *
     * @return void
     */
    public function testGenerateFromArrayMissingTransactionField(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required transaction field: endToEndId');

        $data = [
            'reference' => 'MSG-001',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'requestedExecutionDate' => '2024-01-20',
            'creditorName' => 'My Company Name',
            'creditorIban' => 'ES9121000418450200051332',
            'transactions' => [
                [
                    'amount' => 100.50,
                    'creditorIban' => 'GB82WEST12345698765432',
                    'creditorName' => 'John Doe',
                ],
            ],
        ];

        $this->generator->generateFromArray($data);
    }

    /**
     * Tests generateFromArray with DateTimeInterface requestedExecutionDate.
     *
     * @return void
     */
    public function testGenerateFromArrayWithDateTimeInterface(): void
    {
        $data = [
            'reference' => 'MSG-001',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'requestedExecutionDate' => new \DateTime('2024-01-20'),
            'creditorName' => 'My Company Name',
            'creditorIban' => 'ES9121000418450200051332',
            'transactions' => [
                [
                    'amount' => 100.50,
                    'creditorIban' => 'GB82WEST12345698765432',
                    'creditorName' => 'John Doe',
                    'endToEndId' => 'E2E-001',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
    }

    /**
     * Tests generateFromArray with DateTimeInterface creationDate.
     *
     * @return void
     */
    public function testGenerateFromArrayWithDateTimeInterfaceCreationDate(): void
    {
        $data = [
            'reference' => 'MSG-001',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'creationDate' => new \DateTime('2024-01-15 10:00:00'),
            'requestedExecutionDate' => '2024-01-20',
            'creditorName' => 'My Company Name',
            'creditorIban' => 'ES9121000418450200051332',
            'transactions' => [
                [
                    'amount' => 100.50,
                    'creditorIban' => 'GB82WEST12345698765432',
                    'creditorName' => 'John Doe',
                    'endToEndId' => 'E2E-001',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
    }

    /**
     * Tests generateFromArray with amount in cents (> 10000).
     *
     * @return void
     */
    public function testGenerateFromArrayWithAmountInCents(): void
    {
        $data = [
            'reference' => 'MSG-001',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'requestedExecutionDate' => '2024-01-20',
            'creditorName' => 'My Company Name',
            'creditorIban' => 'ES9121000418450200051332',
            'transactions' => [
                [
                    'amount' => 15000, // 150.00 in cents
                    'creditorIban' => 'GB82WEST12345698765432',
                    'creditorName' => 'John Doe',
                    'endToEndId' => 'E2E-001',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('150.00', $xml);
    }

    /**
     * Tests generateFromArray without creditorBic.
     *
     * @return void
     */
    public function testGenerateFromArrayWithoutCreditorBic(): void
    {
        $data = [
            'reference' => 'MSG-001',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'requestedExecutionDate' => '2024-01-20',
            'creditorName' => 'My Company Name',
            'creditorIban' => 'ES9121000418450200051332',
            'transactions' => [
                [
                    'amount' => 100.50,
                    'creditorIban' => 'GB82WEST12345698765432',
                    'creditorName' => 'John Doe',
                    'endToEndId' => 'E2E-001',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
    }

    /**
     * Tests generateFromArray with creditorBic.
     *
     * @return void
     */
    public function testGenerateFromArrayWithCreditorBic(): void
    {
        $data = [
            'reference' => 'MSG-001',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'requestedExecutionDate' => '2024-01-20',
            'creditorName' => 'My Company Name',
            'creditorIban' => 'ES9121000418450200051332',
            'creditorBic' => 'CAIXESBBXXX',
            'transactions' => [
                [
                    'amount' => 100.50,
                    'creditorIban' => 'GB82WEST12345698765432',
                    'creditorName' => 'John Doe',
                    'endToEndId' => 'E2E-001',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
        $this->assertStringContainsString('CAIXESBBXXX', $xml);
    }

    /**
     * Tests generateFromArray with transaction creditorBic.
     *
     * @return void
     */
    public function testGenerateFromArrayWithTransactionCreditorBic(): void
    {
        $data = [
            'reference' => 'MSG-001',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'requestedExecutionDate' => '2024-01-20',
            'creditorName' => 'My Company Name',
            'creditorIban' => 'ES9121000418450200051332',
            'transactions' => [
                [
                    'amount' => 100.50,
                    'creditorIban' => 'GB82WEST12345698765432',
                    'creditorName' => 'John Doe',
                    'endToEndId' => 'E2E-001',
                    'creditorBic' => 'WESTGB22',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
        $this->assertStringContainsString('WESTGB22', $xml);
    }

    /**
     * Tests generateFromArray with remittanceInformation.
     *
     * @return void
     */
    public function testGenerateFromArrayWithRemittanceInformation(): void
    {
        $data = [
            'reference' => 'MSG-001',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'requestedExecutionDate' => '2024-01-20',
            'creditorName' => 'My Company Name',
            'creditorIban' => 'ES9121000418450200051332',
            'transactions' => [
                [
                    'amount' => 100.50,
                    'creditorIban' => 'GB82WEST12345698765432',
                    'creditorName' => 'John Doe',
                    'endToEndId' => 'E2E-001',
                    'remittanceInformation' => 'Invoice 12345',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
        $this->assertStringContainsString('Invoice 12345', $xml);
    }

    /**
     * Tests generateFromArray with currency.
     * Note: SEPA always uses EUR, but currency can be specified in Transaction.
     *
     * @return void
     */
    public function testGenerateFromArrayWithCurrency(): void
    {
        $data = [
            'reference' => 'MSG-001',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'requestedExecutionDate' => '2024-01-20',
            'creditorName' => 'My Company Name',
            'creditorIban' => 'ES9121000418450200051332',
            'transactions' => [
                [
                    'amount' => 100.50,
                    'currency' => 'USD',
                    'creditorIban' => 'GB82WEST12345698765432',
                    'creditorName' => 'John Doe',
                    'endToEndId' => 'E2E-001',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
        // SEPA always uses EUR in XML, even if currency is specified in Transaction
        $this->assertStringContainsString('EUR', $xml);
    }

    /**
     * Tests generateFromArray with batchBooking.
     *
     * @return void
     */
    public function testGenerateFromArrayWithBatchBooking(): void
    {
        $data = [
            'reference' => 'MSG-001',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'requestedExecutionDate' => '2024-01-20',
            'creditorName' => 'My Company Name',
            'creditorIban' => 'ES9121000418450200051332',
            'batchBooking' => true,
            'transactions' => [
                [
                    'amount' => 100.50,
                    'creditorIban' => 'GB82WEST12345698765432',
                    'creditorName' => 'John Doe',
                    'endToEndId' => 'E2E-001',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
    }

    /**
     * Tests generateFromArray with invalid requestedExecutionDate type.
     *
     * @return void
     */
    public function testGenerateFromArrayInvalidRequestedExecutionDateType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('requestedExecutionDate must be a string or DateTimeInterface');

        $data = [
            'reference' => 'MSG-001',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'requestedExecutionDate' => 12345, // Invalid type
            'creditorName' => 'My Company Name',
            'creditorIban' => 'ES9121000418450200051332',
        ];

        $this->generator->generateFromArray($data);
    }

    /**
     * Tests generateFromArray with invalid creationDate type.
     *
     * @return void
     */
    public function testGenerateFromArrayInvalidCreationDateType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('creationDate must be a string or DateTimeInterface');

        $data = [
            'reference' => 'MSG-001',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'creationDate' => 12345, // Invalid type
            'requestedExecutionDate' => '2024-01-20',
            'creditorName' => 'My Company Name',
            'creditorIban' => 'ES9121000418450200051332',
        ];

        $this->generator->generateFromArray($data);
    }

    /**
     * Tests generateFromArray with multiple transactions and addresses.
     *
     * @return void
     */
    public function testGenerateFromArrayWithMultipleTransactionsAndAddresses(): void
    {
        $data = [
            'reference' => 'MSG-001',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'requestedExecutionDate' => '2024-01-20',
            'creditorName' => 'My Company Name',
            'creditorIban' => 'ES9121000418450200051332',
            'creditorAddress' => [
                'street' => '123 Business Street',
                'city' => 'Madrid',
                'postalCode' => '28001',
                'country' => 'ES',
            ],
            'transactions' => [
                [
                    'amount' => 100.50,
                    'creditorIban' => 'GB82WEST12345698765432',
                    'creditorName' => 'John Doe',
                    'endToEndId' => 'E2E-001',
                    'creditorAddress' => [
                        'street' => '456 Customer Avenue',
                        'city' => 'London',
                        'postalCode' => 'SW1A 1AA',
                        'country' => 'GB',
                    ],
                ],
                [
                    'amount' => 200.75,
                    'creditorIban' => 'FR1420041010050500013M02606',
                    'creditorName' => 'Jane Smith',
                    'endToEndId' => 'E2E-002',
                    'creditorAddress' => [
                        'street' => '789 Paris Street',
                        'city' => 'Paris',
                        'postalCode' => '75001',
                        'country' => 'FR',
                    ],
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
        $this->assertStringContainsString('PstlAdr', $xml);
        $this->assertStringContainsString('123 Business Street', $xml);
        $this->assertStringContainsString('456 Customer Avenue', $xml);
        $this->assertStringContainsString('789 Paris Street', $xml);
        $this->assertStringContainsString('2', $xml); // Number of transactions
        $this->assertStringContainsString('301.25', $xml); // Total amount
    }

    /**
     * Tests createResponse method.
     *
     * @return void
     */
    public function testCreateResponse(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><test>XML Content</test>';
        $filename = 'test-credit-transfer.xml';

        $response = $this->generator->createResponse($xml, $filename);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($xml, $response->getContent());
        $this->assertEquals('application/xml', $response->headers->get('Content-Type'));
        $this->assertEquals('attachment; filename="test-credit-transfer.xml"', $response->headers->get('Content-Disposition'));
    }

    /**
     * Tests generation with logger integration.
     *
     * @return void
     */
    public function testGenerateWithLogger(): void
    {
        $testLogger = new TestLogger();
        $sepaLogger = new SepaPaymentLogger($testLogger);
        $generator = new CreditTransferGenerator(new IbanValidator(), null, false, null, $sepaLogger);

        $creditTransferData = new CreditTransferData(
            'MSG-LOG-001',
            new \DateTime(),
            'Test Company',
            'PMT-LOG-001',
            'ES9121000418450200051332',
            'Test Company Name',
            new \DateTime('tomorrow')
        );

        $transaction = new Transaction(
            'E2E-LOG-001',
            100.50,
            'EUR',
            'GB82WEST12345698765432',
            'John Doe'
        );
        $creditTransferData->addTransaction($transaction);

        $xml = $generator->generate($creditTransferData);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertCount(2, $testLogger->logs); // Start and success logs
        $this->assertEquals('SEPA Credit Transfer generation started', $testLogger->logs[0]['message']);
        $this->assertEquals('SEPA Credit Transfer generation completed successfully', $testLogger->logs[1]['message']);
        $this->assertEquals('MSG-LOG-001', $testLogger->logs[0]['context']['message_id']);
    }

    /**
     * Tests generation failure logging.
     *
     * @return void
     */
    public function testGenerateFailureWithLogger(): void
    {
        $testLogger = new TestLogger();
        $sepaLogger = new SepaPaymentLogger($testLogger);
        $generator = new CreditTransferGenerator(new IbanValidator(), null, false, null, $sepaLogger);

        $creditTransferData = new CreditTransferData(
            'MSG-LOG-002',
            new \DateTime(),
            'Test Company',
            'PMT-LOG-002',
            'INVALID-IBAN', // Invalid IBAN will cause validation failure
            'Test Company Name',
            new \DateTime('tomorrow')
        );

        try {
            $generator->generate($creditTransferData);
            $this->fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertCount(2, $testLogger->logs); // Start and failure logs
            $this->assertEquals('SEPA Credit Transfer generation started', $testLogger->logs[0]['message']);
            $this->assertEquals('SEPA Credit Transfer generation failed', $testLogger->logs[1]['message']);
            $this->assertEquals('error', $testLogger->logs[1]['level']);
        }
    }

    /**
     * Tests generation with empty transactions list.
     * Should throw an exception because at least one transaction is required.
     *
     * @return void
     */
    public function testGenerateWithEmptyTransactions(): void
    {
        $this->expectException(\Digitick\Sepa\Exception\InvalidTransferFileConfiguration::class);
        $this->expectExceptionMessage('PaymentInformation must at least contain one payment');

        $creditTransferData = new CreditTransferData(
            'MSG-EMPTY-001',
            new \DateTime(),
            'Test Company',
            'PMT-EMPTY-001',
            'ES9121000418450200051332',
            'Test Company Name',
            new \DateTime('tomorrow')
        );

        // No transactions added - this should cause an exception

        $this->generator->generate($creditTransferData);
    }

    /**
     * Tests generateFromArray with all optional fields.
     *
     * @return void
     */
    public function testGenerateFromArrayWithAllOptionalFields(): void
    {
        $data = [
            'reference' => 'MSG-ALL-001',
            'creationDate' => new \DateTime(),
            'initiatingPartyName' => 'Test Company',
            'paymentInfoId' => 'PMT-ALL-001',
            'creditorIban' => 'ES9121000418450200051332',
            'creditorName' => 'Test Company Name',
            'requestedExecutionDate' => new \DateTime('tomorrow'),
            'creditorBic' => 'CAIXESBBXXX',
            'batchBooking' => true,
            'creditorAddress' => [
                'street' => '123 Test Street',
                'city' => 'Madrid',
                'postalCode' => '28001',
                'country' => 'ES',
            ],
            'transactions' => [
                [
                    'amount' => 100.50,
                    'currency' => 'EUR',
                    'creditorIban' => 'GB82WEST12345698765432',
                    'creditorName' => 'John Doe',
                    'endToEndId' => 'E2E-ALL-001',
                    'creditorBic' => 'WESTGB22',
                    'remittanceInformation' => 'Test Invoice',
                    'creditorAddress' => [
                        'street' => '456 Test Avenue',
                        'city' => 'London',
                        'postalCode' => 'SW1A 1AA',
                        'country' => 'GB',
                    ],
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('MSG-ALL-001', $xml);
        $this->assertStringContainsString('123 Test Street', $xml);
        $this->assertStringContainsString('456 Test Avenue', $xml);
    }
}
