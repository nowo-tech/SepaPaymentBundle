<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Model\CreditTransfer;

use Nowo\SepaPaymentBundle\Model\CreditTransfer\CreditTransferData;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\Transaction;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for CreditTransferData.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class CreditTransferDataTest extends TestCase
{
    /**
     * Tests credit transfer data creation.
     *
     * @return void
     */
    public function testCreditTransferDataCreation(): void
    {
        $creationDate = new \DateTime('2024-01-15 10:00:00');
        $executionDate = new \DateTime('2024-01-20');

        $creditTransferData = new CreditTransferData(
            'MSG-001',
            $creationDate,
            'My Company',
            'PMT-001',
            'ES9121000418450200051332',
            'My Company Name',
            $executionDate
        );

        $this->assertEquals('MSG-001', $creditTransferData->getMessageId());
        $this->assertEquals($creationDate, $creditTransferData->getCreationDate());
        $this->assertEquals('My Company', $creditTransferData->getInitiatingPartyName());
        $this->assertEquals('PMT-001', $creditTransferData->getPaymentInfoId());
        $this->assertEquals('ES9121000418450200051332', $creditTransferData->getCreditorIban());
        $this->assertEquals('My Company Name', $creditTransferData->getCreditorName());
        $this->assertEquals($executionDate, $creditTransferData->getRequestedExecutionDate());
        $this->assertFalse($creditTransferData->isBatchBooking());
    }

    /**
     * Tests setting creditor BIC.
     *
     * @return void
     */
    public function testSetCreditorBic(): void
    {
        $creditTransferData = $this->createCreditTransferData();

        $creditTransferData->setCreditorBic('CAIXESBBXXX');
        $this->assertEquals('CAIXESBBXXX', $creditTransferData->getCreditorBic());

        $creditTransferData->setCreditorBic(null);
        $this->assertNull($creditTransferData->getCreditorBic());
    }

    /**
     * Tests setting batch booking.
     *
     * @return void
     */
    public function testSetBatchBooking(): void
    {
        $creditTransferData = $this->createCreditTransferData();

        $creditTransferData->setBatchBooking(true);
        $this->assertTrue($creditTransferData->isBatchBooking());
    }

    /**
     * Tests adding transactions.
     *
     * @return void
     */
    public function testAddTransaction(): void
    {
        $creditTransferData = $this->createCreditTransferData();

        $transaction1 = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'ES9121000418450200051332',
            'John Doe'
        );

        $transaction2 = new Transaction(
            'E2E-002',
            200.75,
            'EUR',
            'GB82WEST12345698765432',
            'Jane Smith'
        );

        $creditTransferData->addTransaction($transaction1);
        $creditTransferData->addTransaction($transaction2);

        $transactions = $creditTransferData->getTransactions();
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
        $creditTransferData = $this->createCreditTransferData();

        $creditTransferData->addTransaction(new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'ES9121000418450200051332',
            'John Doe'
        ));

        $creditTransferData->addTransaction(new Transaction(
            'E2E-002',
            200.75,
            'EUR',
            'GB82WEST12345698765432',
            'Jane Smith'
        ));

        $this->assertEquals(301.25, $creditTransferData->getTotalAmount());
    }

    /**
     * Tests total amount with no transactions.
     *
     * @return void
     */
    public function testGetTotalAmountWithNoTransactions(): void
    {
        $creditTransferData = $this->createCreditTransferData();

        $this->assertEquals(0.0, $creditTransferData->getTotalAmount());
    }

    /**
     * Tests setting creditor address.
     *
     * @return void
     */
    public function testSetCreditorAddress(): void
    {
        $creditTransferData = $this->createCreditTransferData();

        $this->assertNull($creditTransferData->getCreditorAddress());

        $creditTransferData->setCreditorAddress('123 Business St', 'Madrid', '28001', 'ES');
        $address = $creditTransferData->getCreditorAddress();

        $this->assertNotNull($address);
        $this->assertEquals('123 Business St', $address['street']);
        $this->assertEquals('Madrid', $address['city']);
        $this->assertEquals('28001', $address['postalCode']);
        $this->assertEquals('ES', $address['country']);
    }

    /**
     * Tests setting creditor address from array.
     *
     * @return void
     */
    public function testSetCreditorAddressFromArray(): void
    {
        $creditTransferData = $this->createCreditTransferData();

        $creditTransferData->setCreditorAddressFromArray([
            'street' => '456 Corporate Avenue',
            'city' => 'Barcelona',
            'postalCode' => '08001',
            'country' => 'ES',
        ]);

        $address = $creditTransferData->getCreditorAddress();
        $this->assertNotNull($address);
        $this->assertEquals('456 Corporate Avenue', $address['street']);
        $this->assertEquals('Barcelona', $address['city']);
        $this->assertEquals('08001', $address['postalCode']);
        $this->assertEquals('ES', $address['country']);
    }

    /**
     * Tests setting creditor address from array with snake_case keys.
     *
     * @return void
     */
    public function testSetCreditorAddressFromArraySnakeCase(): void
    {
        $creditTransferData = $this->createCreditTransferData();

        $creditTransferData->setCreditorAddressFromArray([
            'address' => '789 Office Plaza',
            'city' => 'Valencia',
            'postal_code' => '46001',
            'country' => 'ES',
        ]);

        $address = $creditTransferData->getCreditorAddress();
        $this->assertNotNull($address);
        $this->assertEquals('789 Office Plaza', $address['street']);
        $this->assertEquals('Valencia', $address['city']);
        $this->assertEquals('46001', $address['postalCode']);
        $this->assertEquals('ES', $address['country']);
    }

    /**
     * Creates a credit transfer data instance for testing.
     *
     * @return CreditTransferData The credit transfer data instance
     */
    private function createCreditTransferData(): CreditTransferData
    {
        return new CreditTransferData(
            'MSG-001',
            new \DateTime('2024-01-15 10:00:00'),
            'My Company',
            'PMT-001',
            'ES9121000418450200051332',
            'My Company Name',
            new \DateTime('2024-01-20')
        );
    }
}
