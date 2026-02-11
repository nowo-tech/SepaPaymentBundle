<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Generator;

use Nowo\SepaPaymentBundle\Event\AfterCreditTransferGenerationEvent;
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Logger\SepaPaymentLogger;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\CreditTransferData;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\Transaction;
use Nowo\SepaPaymentBundle\Tests\Logger\TestLogger;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Translation\TranslatorInterface;

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
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            \Nowo\SepaPaymentBundle\Tests\Helper\TranslationHelper::createTranslatorCallback()
        );
        $this->generator = new CreditTransferGenerator($ibanValidator, $translator);
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
            'debtorName' => 'My Company Name',
            'debtorIban' => 'ES9121000418450200051332',
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
            'debtor_name' => 'My Company Name',
            'debtor_iban' => 'ES9121000418450200051332',
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
            'debtorName' => 'My Company Name',
            'debtorIban' => 'ES9121000418450200051332',
            'debtorAddress' => [
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
            'debtor_name' => 'My Company Name',
            'debtor_iban' => 'ES9121000418450200051332',
            'debtor_street' => '123 Business Street',
            'debtor_city' => 'Madrid',
            'debtor_postal_code' => '28001',
            'debtor_country' => 'ES',
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
            'debtorName' => 'My Company Name',
            'debtorIban' => 'ES9121000418450200051332',
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
            'debtorName' => 'My Company Name',
            'debtorIban' => 'ES9121000418450200051332',
            'debtorAddress' => [], // Empty array
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
            'debtorName' => 'My Company Name',
            'debtorIban' => 'ES9121000418450200051332',
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
            'debtorName' => 'My Company Name',
            'debtorIban' => 'ES9121000418450200051332',
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
            'debtorName' => 'My Company Name',
            'debtorIban' => 'ES9121000418450200051332',
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
            'debtorName' => 'My Company Name',
            'debtorIban' => 'ES9121000418450200051332',
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
            'debtorName' => 'My Company Name',
            'debtorIban' => 'ES9121000418450200051332',
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
            'debtorName' => 'My Company Name',
            'debtorIban' => 'ES9121000418450200051332',
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
            'debtorName' => 'My Company Name',
            'debtorIban' => 'ES9121000418450200051332',
            'debtorBic' => 'CAIXESBBXXX',
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
            'debtorName' => 'My Company Name',
            'debtorIban' => 'ES9121000418450200051332',
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
            'debtorName' => 'My Company Name',
            'debtorIban' => 'ES9121000418450200051332',
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
            'debtorName' => 'My Company Name',
            'debtorIban' => 'ES9121000418450200051332',
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
            'debtorName' => 'My Company Name',
            'debtorIban' => 'ES9121000418450200051332',
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
            'debtorName' => 'My Company Name',
            'debtorIban' => 'ES9121000418450200051332',
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
            'debtorName' => 'My Company Name',
            'debtorIban' => 'ES9121000418450200051332',
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
            'debtorName' => 'My Company Name',
            'debtorIban' => 'ES9121000418450200051332',
            'debtorAddress' => [
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
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(function ($id, $parameters = [], $domain = null) {
            return $id;
        });
        $generator = new CreditTransferGenerator(new IbanValidator(), $translator, null, false, null, $sepaLogger);

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
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            \Nowo\SepaPaymentBundle\Tests\Helper\TranslationHelper::createTranslatorCallback()
        );
        $generator = new CreditTransferGenerator(new IbanValidator(), $translator, null, false, null, $sepaLogger);

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
     * Tests generation with BIC lookup service (creditor BIC auto-filled).
     */
    public function testGenerateWithBicLookupService(): void
    {
        $bicLookup = new class () implements \Nowo\SepaPaymentBundle\Lookup\BicLookupServiceInterface {
            public function lookupBic(string $iban): ?string
            {
                return str_starts_with($iban, 'ES') ? 'CAIXESBBXXX' : null;
            }

            public function isAvailable(string $iban): bool
            {
                return str_starts_with($iban, 'ES');
            }
        };
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            \Nowo\SepaPaymentBundle\Tests\Helper\TranslationHelper::createTranslatorCallback()
        );
        $generator = new CreditTransferGenerator(
            new IbanValidator(),
            $translator,
            null,
            false,
            null,
            null,
            $bicLookup
        );

        $creditTransferData = new CreditTransferData(
            'MSG-BIC',
            new \DateTime(),
            'Test Company',
            'PMT-BIC',
            'ES9121000418450200051332',
            'Test Company Name',
            new \DateTime('tomorrow')
        );
        $creditTransferData->addTransaction(new Transaction(
            'E2E-BIC',
            50.00,
            'EUR',
            'GB82WEST12345698765432',
            'John Doe'
        ));

        $xml = $generator->generate($creditTransferData);
        $this->assertStringContainsString('CAIXESBBXXX', $xml);
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
     * Tests generateFromArray with invalid creditor keys at top level.
     * When creditor* keys are used at top level, they are not normalized,
     * so the validation fails because debtorIban is missing.
     *
     * @return void
     */
    public function testGenerateFromArrayWithInvalidCreditorKeysAtTopLevel(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: debtorIban');

        $data = [
            'reference' => 'MSG-001',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'requestedExecutionDate' => '2024-01-20',
            'creditorIban' => 'ES9121000418450200051332', // ❌ Should be debtorIban
            'creditorName' => 'My Company Name', // ❌ Should be debtorName
            'transactions' => [
                [
                    'amount' => 100.50,
                    'creditorIban' => 'GB82WEST12345698765432',
                    'creditorName' => 'John Doe',
                    'endToEndId' => 'E2E-001',
                ],
            ],
        ];

        $this->generator->generateFromArray($data);
    }

    /**
     * Tests generateFromArray with invalid debtor keys in transactions.
     * When debtor* keys are used in transactions, they are not normalized,
     * so the validation fails because creditorIban is missing.
     *
     * @return void
     */
    public function testGenerateFromArrayWithInvalidDebtorKeysInTransactions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required transaction field: creditorIban');

        $data = [
            'reference' => 'MSG-001',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            'requestedExecutionDate' => '2024-01-20',
            'debtorIban' => 'ES9121000418450200051332',
            'debtorName' => 'My Company Name',
            'transactions' => [
                [
                    'amount' => 100.50,
                    'debtorIban' => 'GB82WEST12345698765432', // ❌ Should be creditorIban
                    'debtorName' => 'John Doe', // ❌ Should be creditorName
                    'endToEndId' => 'E2E-001',
                ],
            ],
        ];

        $this->generator->generateFromArray($data);
    }

    /**
     * Tests generateFromArray with all optional fields.
     *
     * @return void
     */
    /**
     * Tests generation with event dispatcher (before event).
     */
    public function testGenerateWithEventDispatcher(): void
    {
        $dispatcher = new EventDispatcher();
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            \Nowo\SepaPaymentBundle\Tests\Helper\TranslationHelper::createTranslatorCallback()
        );
        $generator = new CreditTransferGenerator(new IbanValidator(), $translator, null, false, $dispatcher);

        $creditTransferData = new CreditTransferData(
            'MSG-EVT',
            new \DateTime(),
            'Test Company',
            'PMT-EVT',
            'ES9121000418450200051332',
            'Test Company Name',
            new \DateTime('tomorrow')
        );
        $creditTransferData->addTransaction(new Transaction(
            'E2E-EVT',
            50.00,
            'EUR',
            'GB82WEST12345698765432',
            'Jane Doe'
        ));

        $xml = $generator->generate($creditTransferData);
        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
        $this->assertStringContainsString('MSG-EVT', $xml);
    }

    /**
     * Tests generation with XSD validation enabled and validation failure.
     */
    public function testGenerateWithXsdValidationFailure(): void
    {
        $xsdValidator = $this->createMock(\Nowo\SepaPaymentBundle\Validator\XsdValidator::class);
        $xsdValidator->method('validateCreditTransfer')->willThrowException(new \InvalidArgumentException('XSD error'));
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturn('Generated XML failed XSD validation');
        $generator = new CreditTransferGenerator(new IbanValidator(), $translator, $xsdValidator, true);

        $creditTransferData = new CreditTransferData(
            'MSG-XSD',
            new \DateTime(),
            'Test Company',
            'PMT-XSD',
            'ES9121000418450200051332',
            'Test Company Name',
            new \DateTime('tomorrow')
        );
        $creditTransferData->addTransaction(new Transaction(
            'E2E-XSD',
            10.00,
            'EUR',
            'GB82WEST12345698765432',
            'John Doe'
        ));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('XSD');

        $generator->generate($creditTransferData);
    }

    /**
     * Tests that when a listener modifies the XML in AfterCreditTransferGenerationEvent, the generator returns the modified XML.
     */
    public function testGenerateWithAfterEventModifiesXml(): void
    {
        $dispatcher = new EventDispatcher();
        $modifiedXml = '<?xml version="1.0"?><modified-by-listener/>';
        $dispatcher->addListener(AfterCreditTransferGenerationEvent::class, function (AfterCreditTransferGenerationEvent $event) use ($modifiedXml): void {
            $event->setXml($modifiedXml);
        });

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            \Nowo\SepaPaymentBundle\Tests\Helper\TranslationHelper::createTranslatorCallback()
        );
        $generator = new CreditTransferGenerator(new IbanValidator(), $translator, null, false, $dispatcher);

        $creditTransferData = new CreditTransferData(
            'MSG-AFTER',
            new \DateTime(),
            'Test Company',
            'PMT-AFTER',
            'ES9121000418450200051332',
            'Test Company Name',
            new \DateTime('tomorrow')
        );
        $creditTransferData->addTransaction(new Transaction(
            'E2E-AFTER',
            25.00,
            'EUR',
            'GB82WEST12345698765432',
            'Jane Doe'
        ));

        $xml = $generator->generate($creditTransferData);
        $this->assertSame($modifiedXml, $xml);
        $this->assertStringContainsString('modified-by-listener', $xml);
    }

    public function testGenerateFromArrayWithAllOptionalFields(): void
    {
        $data = [
            'reference' => 'MSG-ALL-001',
            'creationDate' => new \DateTime(),
            'initiatingPartyName' => 'Test Company',
            'paymentInfoId' => 'PMT-ALL-001',
            'debtorIban' => 'ES9121000418450200051332',
            'debtorName' => 'Test Company Name',
            'requestedExecutionDate' => new \DateTime('tomorrow'),
            'debtorBic' => 'CAIXESBBXXX',
            'batchBooking' => true,
            'debtorAddress' => [
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

    /**
     * Tests that addAddressesToXml returns the original XML when the XML string is invalid (loadXML fails).
     *
     * @return void
     */
    public function testAddAddressesToXmlReturnsOriginalWhenXmlInvalid(): void
    {
        $invalidXml = '<?xml version="1.0"?><root><unclosed>';
        $creditTransferData = new CreditTransferData(
            'MSG-001',
            new \DateTime('2024-01-15'),
            'Company',
            'PMT-001',
            'ES9121000418450200051332',
            'Name',
            new \DateTime('2024-01-20')
        );
        $ref = new \ReflectionClass(CreditTransferGenerator::class);
        $method = $ref->getMethod('addAddressesToXml');
        $method->setAccessible(true);
        $result = $method->invoke($this->generator, $invalidXml, $creditTransferData);
        $this->assertSame($invalidXml, $result);
    }

    /**
     * Tests addAddressesToXml with XML without namespace so XPath fallback (without ns prefix) is used.
     *
     * @return void
     */
    public function testAddAddressesToXmlWithXmlWithoutNamespaceUsesXPathFallback(): void
    {
        $xmlNoNs = '<?xml version="1.0"?><Document><PmtInf><CdtTrfTxInf><Dbtr><Nm>Debtor</Nm></Dbtr><Cdtr><Nm>Creditor</Nm></Cdtr></CdtTrfTxInf></PmtInf></Document>';
        $creditTransferData = new CreditTransferData(
            'MSG-001',
            new \DateTime('2024-01-15'),
            'Company',
            'PMT-001',
            'ES9121000418450200051332',
            'Name',
            new \DateTime('2024-01-20')
        );
        $creditTransferData->setCreditorAddress(['street' => 'S1', 'city' => 'C1', 'postalCode' => 'P1', 'country' => 'ES']);
        $transaction = new Transaction('E2E', 10.00, 'EUR', 'ES9121000418450200051332', 'Cred');
        $transaction->setCreditorAddress(['street' => 'S2', 'city' => 'C2', 'postalCode' => 'P2', 'country' => 'ES']);
        $creditTransferData->addTransaction($transaction);
        $ref = new \ReflectionClass(CreditTransferGenerator::class);
        $method = $ref->getMethod('addAddressesToXml');
        $method->setAccessible(true);
        $result = $method->invoke($this->generator, $xmlNoNs, $creditTransferData);
        $this->assertStringContainsString('PstlAdr', $result);
        $this->assertStringContainsString('S1', $result);
        $this->assertStringContainsString('S2', $result);
    }

    /**
     * Tests addAddressesToXml when parent already has PstlAdr (removeChild) and Nm has nextSibling (insertBefore).
     * Uses namespace so getElementsByTagNameNS finds existing PstlAdr and removes it.
     *
     * @return void
     */
    public function testAddAddressesToXmlReplacesExistingPstlAdrAndInsertsBeforeSibling(): void
    {
        $ns = 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03';
        $xmlWithExisting = '<?xml version="1.0"?><Document xmlns="' . $ns . '"><PmtInf><CdtTrfTxInf><Dbtr><Nm>Debtor</Nm><PstlAdr><StrtNm>Old</StrtNm></PstlAdr><Id>id1</Id></Dbtr><Cdtr><Nm>C</Nm><Id>id2</Id></Cdtr></CdtTrfTxInf></PmtInf></Document>';
        $creditTransferData = new CreditTransferData(
            'MSG-001',
            new \DateTime('2024-01-15'),
            'Company',
            'PMT-001',
            'ES9121000418450200051332',
            'Name',
            new \DateTime('2024-01-20')
        );
        $creditTransferData->setCreditorAddress(['street' => 'NewStreet', 'country' => 'ES']);
        $ref = new \ReflectionClass(CreditTransferGenerator::class);
        $method = $ref->getMethod('addAddressesToXml');
        $method->setAccessible(true);
        $result = $method->invoke($this->generator, $xmlWithExisting, $creditTransferData);
        $this->assertStringContainsString('NewStreet', $result);
        $this->assertStringNotContainsString('Old', $result);
    }

    /**
     * Tests addAddressesToXml when there are more transactions with creditor address than Cdtr nodes (index out of range).
     *
     * @return void
     */
    public function testAddAddressesToXmlSkipsCreditorAddressWhenIndexOutOfRange(): void
    {
        $ns = 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03';
        $xmlOneCdtr = '<?xml version="1.0"?><Document xmlns="' . $ns . '"><PmtInf><CdtTrfTxInf><Dbtr><Nm>D</Nm></Dbtr><Cdtr><Nm>C1</Nm></Cdtr></CdtTrfTxInf></PmtInf></Document>';
        $creditTransferData = new CreditTransferData(
            'MSG-001',
            new \DateTime('2024-01-15'),
            'Company',
            'PMT-001',
            'ES9121000418450200051332',
            'Name',
            new \DateTime('2024-01-20')
        );
        $t1 = new Transaction('E1', 10.00, 'EUR', 'ES9121000418450200051332', 'C1');
        $t1->setCreditorAddress(['street' => 'First', 'country' => 'ES']);
        $t2 = new Transaction('E2', 20.00, 'EUR', 'ES9121000418450200051332', 'C2');
        $t2->setCreditorAddress(['street' => 'Second', 'country' => 'ES']);
        $creditTransferData->addTransaction($t1);
        $creditTransferData->addTransaction($t2);
        $ref = new \ReflectionClass(CreditTransferGenerator::class);
        $method = $ref->getMethod('addAddressesToXml');
        $method->setAccessible(true);
        $result = $method->invoke($this->generator, $xmlOneCdtr, $creditTransferData);
        $this->assertStringContainsString('First', $result);
        $this->assertStringNotContainsString('Second', $result);
    }

    /**
     * Tests addAddressesToXml with address that has all empty fields (createPostalAddressElement returns without adding).
     *
     * @return void
     */
    public function testAddAddressesToXmlWithAllEmptyAddressFieldsDoesNotAddPstlAdr(): void
    {
        $xmlNoNs = '<?xml version="1.0"?><Document><PmtInf><CdtTrfTxInf><Dbtr><Nm>D</Nm></Dbtr><Cdtr><Nm>C</Nm></Cdtr></CdtTrfTxInf></PmtInf></Document>';
        $creditTransferData = new CreditTransferData(
            'MSG-001',
            new \DateTime('2024-01-15'),
            'Company',
            'PMT-001',
            'ES9121000418450200051332',
            'Name',
            new \DateTime('2024-01-20')
        );
        $creditTransferData->setCreditorAddress(['street' => '', 'city' => '', 'postalCode' => '', 'country' => '']);
        $ref = new \ReflectionClass(CreditTransferGenerator::class);
        $method = $ref->getMethod('addAddressesToXml');
        $method->setAccessible(true);
        $result = $method->invoke($this->generator, $xmlNoNs, $creditTransferData);
        $this->assertStringNotContainsString('PstlAdr', $result);
    }

    /**
     * Tests addAddressesToXml when parent node has no Nm element (appendChild branch in createPostalAddressElement).
     *
     * @return void
     */
    public function testAddAddressesToXmlWhenParentHasNoNmUsesAppendChild(): void
    {
        $xmlNoNm = '<?xml version="1.0"?><Document><PmtInf><CdtTrfTxInf><Dbtr><Id>id1</Id></Dbtr><Cdtr><Id>id2</Id></Cdtr></CdtTrfTxInf></PmtInf></Document>';
        $creditTransferData = new CreditTransferData(
            'MSG-001',
            new \DateTime('2024-01-15'),
            'Company',
            'PMT-001',
            'ES9121000418450200051332',
            'Name',
            new \DateTime('2024-01-20')
        );
        $creditTransferData->setCreditorAddress(['street' => 'Street', 'country' => 'ES']);
        $ref = new \ReflectionClass(CreditTransferGenerator::class);
        $method = $ref->getMethod('addAddressesToXml');
        $method->setAccessible(true);
        $result = $method->invoke($this->generator, $xmlNoNm, $creditTransferData);
        $this->assertStringContainsString('PstlAdr', $result);
        $this->assertStringContainsString('Street', $result);
    }
}
