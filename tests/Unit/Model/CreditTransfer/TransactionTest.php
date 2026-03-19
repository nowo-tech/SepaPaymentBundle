<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Model\CreditTransfer;

use Nowo\SepaPaymentBundle\Model\CreditTransfer\Transaction;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for Transaction.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class TransactionTest extends TestCase
{
    /**
     * Tests transaction creation.
     */
    public function testTransactionCreation(): void
    {
        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'ES9121000418450200051332',
            'John Doe',
        );

        $this->assertEquals('E2E-001', $transaction->getEndToEndId());
        $this->assertEquals(100.50, $transaction->getAmount());
        $this->assertEquals('EUR', $transaction->getCurrency());
        $this->assertEquals('ES9121000418450200051332', $transaction->getCreditorIban());
        $this->assertEquals('John Doe', $transaction->getCreditorName());
        $this->assertNull($transaction->getCreditorBic());
        $this->assertNull($transaction->getRemittanceInformation());
    }

    /**
     * Tests setting creditor BIC.
     */
    public function testSetCreditorBic(): void
    {
        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'ES9121000418450200051332',
            'John Doe',
        );

        $transaction->setCreditorBic('CAIXESBBXXX');
        $this->assertEquals('CAIXESBBXXX', $transaction->getCreditorBic());

        $transaction->setCreditorBic(null);
        $this->assertNull($transaction->getCreditorBic());
    }

    /**
     * Tests setting remittance information.
     */
    public function testSetRemittanceInformation(): void
    {
        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'ES9121000418450200051332',
            'John Doe',
        );

        $transaction->setRemittanceInformation('Invoice 12345');
        $this->assertEquals('Invoice 12345', $transaction->getRemittanceInformation());

        $transaction->setRemittanceInformation(null);
        $this->assertNull($transaction->getRemittanceInformation());
    }

    /**
     * Tests setting creditor address.
     */
    public function testSetCreditorAddress(): void
    {
        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'ES9121000418450200051332',
            'John Doe',
        );

        $this->assertNull($transaction->getCreditorAddress());

        $transaction->setCreditorAddress('123 Main St', 'London', 'SW1A 1AA', 'GB');
        $address = $transaction->getCreditorAddress();

        $this->assertIsArray($address);
        $this->assertEquals('123 Main St', $address['street']);
        $this->assertEquals('London', $address['city']);
        $this->assertEquals('SW1A 1AA', $address['postalCode']);
        $this->assertEquals('GB', $address['country']);
    }

    /**
     * Tests setCreditorAddress when first argument is array (delegates to setCreditorAddressFromArray).
     */
    public function testSetCreditorAddressWithArray(): void
    {
        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'ES9121000418450200051332',
            'John Doe',
        );
        $transaction->setCreditorAddress([
            'street'     => 'Array Street',
            'city'       => 'Berlin',
            'postalCode' => '10115',
            'country'    => 'DE',
        ]);
        $address = $transaction->getCreditorAddress();
        $this->assertIsArray($address);
        $this->assertEquals('Array Street', $address['street']);
        $this->assertEquals('Berlin', $address['city']);
    }

    /**
     * Tests setting creditor address from array.
     */
    public function testSetCreditorAddressFromArray(): void
    {
        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'ES9121000418450200051332',
            'John Doe',
        );

        $transaction->setCreditorAddressFromArray([
            'street'     => '456 Oak Avenue',
            'city'       => 'Madrid',
            'postalCode' => '28001',
            'country'    => 'ES',
        ]);

        $address = $transaction->getCreditorAddress();
        $this->assertIsArray($address);
        $this->assertEquals('456 Oak Avenue', $address['street']);
        $this->assertEquals('Madrid', $address['city']);
        $this->assertEquals('28001', $address['postalCode']);
        $this->assertEquals('ES', $address['country']);
    }

    /**
     * Tests setting creditor address from array with snake_case keys.
     */
    public function testSetCreditorAddressFromArraySnakeCase(): void
    {
        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'ES9121000418450200051332',
            'John Doe',
        );

        $transaction->setCreditorAddressFromArray([
            'address'     => '789 Pine Street',
            'city'        => 'Barcelona',
            'postal_code' => '08001',
            'country'     => 'ES',
        ]);

        $address = $transaction->getCreditorAddress();
        $this->assertIsArray($address);
        $this->assertEquals('789 Pine Street', $address['street']);
        $this->assertEquals('Barcelona', $address['city']);
        $this->assertEquals('08001', $address['postalCode']);
        $this->assertEquals('ES', $address['country']);
    }
}
