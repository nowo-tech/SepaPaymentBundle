<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Model\Remesa;

use Nowo\SepaPaymentBundle\Model\Remesa\Transaction;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Remesa\Transaction (deprecated API).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class TransactionTest extends TestCase
{
    public function testTransactionGettersAndSetters(): void
    {
        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'GB82WEST12345698765432',
            'John Doe',
        );

        $this->assertEquals('E2E-001', $transaction->getEndToEndId());
        $this->assertEquals(100.50, $transaction->getAmount());
        $this->assertEquals('EUR', $transaction->getCurrency());
        $this->assertEquals('GB82WEST12345698765432', $transaction->getDebtorIban());
        $this->assertEquals('John Doe', $transaction->getDebtorName());

        $transaction->setDebtorBic('WESTGB22');
        $this->assertEquals('WESTGB22', $transaction->getDebtorBic());

        $transaction->setRemittanceInformation('Invoice 123');
        $this->assertEquals('Invoice 123', $transaction->getRemittanceInformation());

        $transaction->setDebtorAddress('Street', 'London', 'SW1', 'GB');
        $addr = $transaction->getDebtorAddress();
        $this->assertNotNull($addr);
        $this->assertArrayHasKey('city', $addr);
        $this->assertEquals('London', $addr['city']);

        $transaction->setDebtorAddressFromArray(['street' => 'Ave', 'city' => 'Paris', 'postalCode' => '75001', 'country' => 'FR']);
        $addr2 = $transaction->getDebtorAddress();
        $this->assertNotNull($addr2);
        $this->assertArrayHasKey('city', $addr2);
        $this->assertEquals('Paris', $addr2['city']);
    }

    public function testGetCreditTransferTransaction(): void
    {
        $transaction   = new Transaction('E2E-001', 50.0, 'EUR', 'ES9121000418450200051332', 'Creditor');
        $ctTransaction = $transaction->getCreditTransferTransaction();
        $this->assertNotNull($ctTransaction);
        $this->assertEquals('E2E-001', $ctTransaction->getEndToEndId());
    }
}
