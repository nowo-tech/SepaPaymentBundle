<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Model\CreditTransfer;

use Nowo\SepaPaymentBundle\Model\CreditTransfer\Transaction;
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

    /**
     * Tests setting debtor address.
     *
     * @return void
     */
    public function testSetDebtorAddress(): void
    {
        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'ES9121000418450200051332',
            'John Doe'
        );

        $this->assertNull($transaction->getDebtorAddress());

        $transaction->setDebtorAddress('123 Main St', 'London', 'SW1A 1AA', 'GB');
        $address = $transaction->getDebtorAddress();

        $this->assertNotNull($address);
        $this->assertEquals('123 Main St', $address['street']);
        $this->assertEquals('London', $address['city']);
        $this->assertEquals('SW1A 1AA', $address['postalCode']);
        $this->assertEquals('GB', $address['country']);
    }

    /**
     * Tests setting debtor address from array.
     *
     * @return void
     */
    public function testSetDebtorAddressFromArray(): void
    {
        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'ES9121000418450200051332',
            'John Doe'
        );

        $transaction->setDebtorAddressFromArray([
            'street' => '456 Oak Avenue',
            'city' => 'Madrid',
            'postalCode' => '28001',
            'country' => 'ES',
        ]);

        $address = $transaction->getDebtorAddress();
        $this->assertNotNull($address);
        $this->assertEquals('456 Oak Avenue', $address['street']);
        $this->assertEquals('Madrid', $address['city']);
        $this->assertEquals('28001', $address['postalCode']);
        $this->assertEquals('ES', $address['country']);
    }

    /**
     * Tests setting debtor address from array with snake_case keys.
     *
     * @return void
     */
    public function testSetDebtorAddressFromArraySnakeCase(): void
    {
        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'ES9121000418450200051332',
            'John Doe'
        );

        $transaction->setDebtorAddressFromArray([
            'address' => '789 Pine Street',
            'city' => 'Barcelona',
            'postal_code' => '08001',
            'country' => 'ES',
        ]);

        $address = $transaction->getDebtorAddress();
        $this->assertNotNull($address);
        $this->assertEquals('789 Pine Street', $address['street']);
        $this->assertEquals('Barcelona', $address['city']);
        $this->assertEquals('08001', $address['postalCode']);
        $this->assertEquals('ES', $address['country']);
    }
}
