<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Model\DirectDebit;

use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitTransaction;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for DirectDebitTransaction.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class DirectDebitTransactionTest extends TestCase
{
    /**
     * Tests transaction creation.
     *
     * @return void
     */
    public function testDirectDebitTransactionCreation(): void
    {
        $mandateSignDate = new \DateTime('2023-12-01');

        $transaction = new DirectDebitTransaction(
            100.50,
            'ES9121000418450200051332',
            'John Doe',
            'MANDATE-001',
            $mandateSignDate,
            'E2E-001'
        );

        $this->assertEquals(100.50, $transaction->getAmount());
        $this->assertEquals('ES9121000418450200051332', $transaction->getDebtorIban());
        $this->assertEquals('John Doe', $transaction->getDebtorName());
        $this->assertEquals('MANDATE-001', $transaction->getDebtorMandate());
        $this->assertEquals($mandateSignDate, $transaction->getDebtorMandateSignDate());
        $this->assertEquals('E2E-001', $transaction->getEndToEndId());
        $this->assertNull($transaction->getRemittanceInformation());
    }

    /**
     * Tests setting remittance information.
     *
     * @return void
     */
    public function testSetRemittanceInformation(): void
    {
        $transaction = new DirectDebitTransaction(
            100.50,
            'ES9121000418450200051332',
            'John Doe',
            'MANDATE-001',
            new \DateTime('2023-12-01'),
            'E2E-001'
        );

        $transaction->setRemittanceInformation('Invoice 12345');
        $this->assertEquals('Invoice 12345', $transaction->getRemittanceInformation());

        $transaction->setRemittanceInformation(null);
        $this->assertNull($transaction->getRemittanceInformation());
    }

    /**
     * Tests setting debtor BIC.
     *
     * @return void
     */
    public function testSetDebtorBic(): void
    {
        $transaction = new DirectDebitTransaction(
            100.50,
            'ES9121000418450200051332',
            'John Doe',
            'MANDATE-001',
            new \DateTime('2023-12-01'),
            'E2E-001'
        );

        $this->assertNull($transaction->getDebtorBic());

        $transaction->setDebtorBic('CAIXESBBXXX');
        $this->assertEquals('CAIXESBBXXX', $transaction->getDebtorBic());

        $transaction->setDebtorBic(null);
        $this->assertNull($transaction->getDebtorBic());
    }

    /**
     * Tests setting additional data.
     *
     * @return void
     */
    public function testSetAdditionalData(): void
    {
        $transaction = new DirectDebitTransaction(
            100.50,
            'ES9121000418450200051332',
            'John Doe',
            'MANDATE-001',
            new \DateTime('2023-12-01'),
            'E2E-001'
        );

        $this->assertEquals([], $transaction->getAdditionalData());

        $additionalData = [
            'internalReference' => 'INT-12345',
            'customerId' => 'CUST-789',
            'customField' => 'customValue',
        ];

        $transaction->setAdditionalData($additionalData);
        $this->assertEquals($additionalData, $transaction->getAdditionalData());
    }

    /**
     * Tests setting a specific additional field.
     *
     * @return void
     */
    public function testSetAdditionalField(): void
    {
        $transaction = new DirectDebitTransaction(
            100.50,
            'ES9121000418450200051332',
            'John Doe',
            'MANDATE-001',
            new \DateTime('2023-12-01'),
            'E2E-001'
        );

        $transaction->setAdditionalField('internalReference', 'INT-12345');
        $this->assertEquals('INT-12345', $transaction->getAdditionalField('internalReference'));

        $transaction->setAdditionalField('customerId', 'CUST-789');
        $this->assertEquals('CUST-789', $transaction->getAdditionalField('customerId'));

        $this->assertEquals([
            'internalReference' => 'INT-12345',
            'customerId' => 'CUST-789',
        ], $transaction->getAdditionalData());
    }

    /**
     * Tests getting additional field with default value.
     *
     * @return void
     */
    public function testGetAdditionalFieldWithDefault(): void
    {
        $transaction = new DirectDebitTransaction(
            100.50,
            'ES9121000418450200051332',
            'John Doe',
            'MANDATE-001',
            new \DateTime('2023-12-01'),
            'E2E-001'
        );

        $this->assertNull($transaction->getAdditionalField('nonExistent'));
        $this->assertEquals('default', $transaction->getAdditionalField('nonExistent', 'default'));
        $this->assertEquals(0, $transaction->getAdditionalField('nonExistent', 0));
    }

    /**
     * Tests that additional data can store various types.
     *
     * @return void
     */
    public function testAdditionalDataTypes(): void
    {
        $transaction = new DirectDebitTransaction(
            100.50,
            'ES9121000418450200051332',
            'John Doe',
            'MANDATE-001',
            new \DateTime('2023-12-01'),
            'E2E-001'
        );

        $transaction->setAdditionalField('stringValue', 'test');
        $transaction->setAdditionalField('intValue', 123);
        $transaction->setAdditionalField('floatValue', 45.67);
        $transaction->setAdditionalField('boolValue', true);
        $transaction->setAdditionalField('arrayValue', ['key' => 'value']);

        $this->assertEquals('test', $transaction->getAdditionalField('stringValue'));
        $this->assertEquals(123, $transaction->getAdditionalField('intValue'));
        $this->assertEquals(45.67, $transaction->getAdditionalField('floatValue'));
        $this->assertTrue($transaction->getAdditionalField('boolValue'));
        $this->assertEquals(['key' => 'value'], $transaction->getAdditionalField('arrayValue'));
    }
}
