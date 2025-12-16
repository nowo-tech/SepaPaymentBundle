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
}

