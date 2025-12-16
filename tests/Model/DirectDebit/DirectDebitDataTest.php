<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Model\DirectDebit;

use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitData;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitTransaction;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for DirectDebitData.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class DirectDebitDataTest extends TestCase
{
    /**
     * Tests direct debit data creation.
     *
     * @return void
     */
    public function testDirectDebitDataCreation(): void
    {
        $dueDate = new \DateTime('2024-01-20');
        $mandateSignDate = new \DateTime('2023-12-01');

        $directDebitData = new DirectDebitData(
            'MSG-001',
            'My Company',
            'PMT-001',
            $dueDate,
            'My Company Name',
            'ES9121000418450200051332',
            'FRST',
            'ES1234567890123456789012',
            'CORE'
        );

        $this->assertEquals('MSG-001', $directDebitData->getMessageId());
        $this->assertEquals('My Company', $directDebitData->getInitiatingPartyName());
        $this->assertEquals('PMT-001', $directDebitData->getPaymentInfoId());
        $this->assertEquals($dueDate, $directDebitData->getDueDate());
        $this->assertEquals('My Company Name', $directDebitData->getCreditorName());
        $this->assertEquals('ES9121000418450200051332', $directDebitData->getCreditorIban());
        $this->assertEquals('FRST', $directDebitData->getSequenceType());
        $this->assertEquals('ES1234567890123456789012', $directDebitData->getCreditorId());
        $this->assertEquals('CORE', $directDebitData->getLocalInstrumentCode());
        $this->assertNull($directDebitData->getCreditorBic());
    }

    /**
     * Tests setting creditor BIC.
     *
     * @return void
     */
    public function testSetCreditorBic(): void
    {
        $directDebitData = $this->createDirectDebitData();

        $directDebitData->setCreditorBic('CAIXESBBXXX');
        $this->assertEquals('CAIXESBBXXX', $directDebitData->getCreditorBic());

        $directDebitData->setCreditorBic(null);
        $this->assertNull($directDebitData->getCreditorBic());
    }

    /**
     * Tests adding transactions.
     *
     * @return void
     */
    public function testAddTransaction(): void
    {
        $directDebitData = $this->createDirectDebitData();

        $transaction1 = new DirectDebitTransaction(
            100.50,
            'ES9121000418450200051332',
            'John Doe',
            'MANDATE-001',
            new \DateTime('2023-12-01'),
            'E2E-001'
        );

        $transaction2 = new DirectDebitTransaction(
            200.75,
            'GB82WEST12345698765432',
            'Jane Smith',
            'MANDATE-002',
            new \DateTime('2023-12-01'),
            'E2E-002'
        );

        $directDebitData->addTransaction($transaction1);
        $directDebitData->addTransaction($transaction2);

        $transactions = $directDebitData->getTransactions();
        $this->assertCount(2, $transactions);
        $this->assertEquals($transaction1, $transactions[0]);
        $this->assertEquals($transaction2, $transactions[1]);
    }

    /**
     * Tests total amount calculation.
     *
     * @return void
     */
    public function testGetTotalAmount(): void
    {
        $directDebitData = $this->createDirectDebitData();

        $directDebitData->addTransaction(new DirectDebitTransaction(
            100.50,
            'ES9121000418450200051332',
            'John Doe',
            'MANDATE-001',
            new \DateTime('2023-12-01'),
            'E2E-001'
        ));

        $directDebitData->addTransaction(new DirectDebitTransaction(
            200.75,
            'GB82WEST12345698765432',
            'Jane Smith',
            'MANDATE-002',
            new \DateTime('2023-12-01'),
            'E2E-002'
        ));

        $this->assertEquals(301.25, $directDebitData->getTotalAmount());
    }

    /**
     * Tests total amount with no transactions.
     *
     * @return void
     */
    public function testGetTotalAmountWithNoTransactions(): void
    {
        $directDebitData = $this->createDirectDebitData();

        $this->assertEquals(0.0, $directDebitData->getTotalAmount());
    }

    /**
     * Creates a direct debit data instance for testing.
     *
     * @return DirectDebitData The direct debit data instance
     */
    private function createDirectDebitData(): DirectDebitData
    {
        return new DirectDebitData(
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
    }
}
