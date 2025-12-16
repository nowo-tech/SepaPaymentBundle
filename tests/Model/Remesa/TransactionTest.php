<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Model\Remesa;

use Nowo\SepaPaymentBundle\Model\Remesa\Transaction;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for Transaction.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class TransactionTest extends TestCase
{
    /**
     * Tests transaction creation.
     *
     * @return void
     */
    public function testTransactionCreation(): void
    {
        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'ES9121000418450200051332',
            'John Doe'
        );

        $this->assertEquals('E2E-001', $transaction->getEndToEndId());
        $this->assertEquals(100.50, $transaction->getAmount());
        $this->assertEquals('EUR', $transaction->getCurrency());
        $this->assertEquals('ES9121000418450200051332', $transaction->getDebtorIban());
        $this->assertEquals('John Doe', $transaction->getDebtorName());
        $this->assertNull($transaction->getDebtorBic());
        $this->assertNull($transaction->getRemittanceInformation());
    }

    /**
     * Tests setting debtor BIC.
     *
     * @return void
     */
    public function testSetDebtorBic(): void
    {
        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'ES9121000418450200051332',
            'John Doe'
        );

        $transaction->setDebtorBic('CAIXESBBXXX');
        $this->assertEquals('CAIXESBBXXX', $transaction->getDebtorBic());

        $transaction->setDebtorBic(null);
        $this->assertNull($transaction->getDebtorBic());
    }

    /**
     * Tests setting remittance information.
     *
     * @return void
     */
    public function testSetRemittanceInformation(): void
    {
        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'ES9121000418450200051332',
            'John Doe'
        );

        $transaction->setRemittanceInformation('Invoice 12345');
        $this->assertEquals('Invoice 12345', $transaction->getRemittanceInformation());

        $transaction->setRemittanceInformation(null);
        $this->assertNull($transaction->getRemittanceInformation());
    }
}
