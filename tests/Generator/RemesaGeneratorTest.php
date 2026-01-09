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
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
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
                    'debtor_iban' => 'GB82WEST12345698765432',
                    'debtor_name' => 'John Doe',
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
     * Tests generateFromArray with creditor and debtor addresses.
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
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                    'endToEndId' => 'E2E-001',
                    'debtorAddress' => [
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
                    'debtor_iban' => 'GB82WEST12345698765432',
                    'debtor_name' => 'John Doe',
                    'debtor_street' => '456 Customer Avenue',
                    'debtor_city' => 'London',
                    'debtor_postal_code' => 'SW1A 1AA',
                    'debtor_country' => 'GB',
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
        $remesaData = new RemesaData(
            'MSG-001',
            new \DateTime('2024-01-15 10:00:00'),
            'My Company',
            'PMT-001',
            'ES9121000418450200051332',
            'My Company Name',
            new \DateTime('2024-01-20')
        );

        $remesaData->setCreditorAddress([
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

        $remesaData->addTransaction($transaction);

        $xml = $this->generator->generate($remesaData);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
        $this->assertStringContainsString('PstlAdr', $xml);
        $this->assertStringContainsString('789 Business Road', $xml);
        $this->assertStringContainsString('Barcelona', $xml);
        $this->assertStringContainsString('08001', $xml);
    }

    /**
     * Tests XML generation with debtor address using object methods.
     *
     * @return void
     */
    public function testGenerateXmlWithDebtorAddress(): void
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

        $transaction->setDebtorAddress([
            'street' => '321 Customer Street',
            'city' => 'Manchester',
            'postalCode' => 'M1 1AA',
            'country' => 'GB',
        ]);

        $remesaData->addTransaction($transaction);

        $xml = $this->generator->generate($remesaData);

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
        $remesaData = new RemesaData(
            'MSG-001',
            new \DateTime('2024-01-15 10:00:00'),
            'My Company',
            'PMT-001',
            'ES9121000418450200051332',
            'My Company Name',
            new \DateTime('2024-01-20')
        );

        $remesaData->setCreditorAddress([
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

        $transaction->setDebtorAddress([
            'street' => '222 Debtor Blvd',
            'city' => 'Leeds',
            'postalCode' => 'LS1 1AA',
            'country' => 'GB',
        ]);

        $remesaData->addTransaction($transaction);

        $xml = $this->generator->generate($remesaData);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
        $this->assertStringContainsString('PstlAdr', $xml);
        // Creditor address
        $this->assertStringContainsString('111 Creditor Ave', $xml);
        $this->assertStringContainsString('Valencia', $xml);
        // Debtor address
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
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
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
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                    'endToEndId' => 'E2E-001',
                    'debtorAddress' => [], // Empty array
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
                    'debtorIban' => 'GB82WEST12345698765432',
                    'debtorName' => 'John Doe',
                ],
            ],
        ];

        $this->generator->generateFromArray($data);
    }

    /**
     * Tests createResponse method.
     *
     * @return void
     */
    public function testCreateResponse(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><test>XML Content</test>';
        $filename = 'test-remesa-pago.xml';

        $response = $this->generator->createResponse($xml, $filename);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($xml, $response->getContent());
        $this->assertEquals('application/xml', $response->headers->get('Content-Type'));
        $this->assertEquals('attachment; filename="test-remesa-pago.xml"', $response->headers->get('Content-Disposition'));
    }
}
