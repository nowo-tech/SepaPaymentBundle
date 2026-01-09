<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Model\Remesa;

use Nowo\SepaPaymentBundle\Model\Remesa\RemesaData;
use Nowo\SepaPaymentBundle\Model\Remesa\Transaction;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for RemesaData.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class RemesaDataTest extends TestCase
{
    /**
     * Tests remesa data creation.
     *
     * @return void
     */
    public function testRemesaDataCreation(): void
    {
        $creationDate = new \DateTime('2024-01-15 10:00:00');
        $executionDate = new \DateTime('2024-01-20');

        $remesaData = new RemesaData(
            'MSG-001',
            $creationDate,
            'My Company',
            'PMT-001',
            'ES9121000418450200051332',
            'My Company Name',
            $executionDate
        );

        $this->assertEquals('MSG-001', $remesaData->getMessageId());
        $this->assertEquals($creationDate, $remesaData->getCreationDate());
        $this->assertEquals('My Company', $remesaData->getInitiatingPartyName());
        $this->assertEquals('PMT-001', $remesaData->getPaymentInfoId());
        $this->assertEquals('ES9121000418450200051332', $remesaData->getCreditorIban());
        $this->assertEquals('My Company Name', $remesaData->getCreditorName());
        $this->assertEquals($executionDate, $remesaData->getRequestedExecutionDate());
        $this->assertFalse($remesaData->isBatchBooking());
    }

    /**
     * Tests setting creditor BIC.
     *
     * @return void
     */
    public function testSetCreditorBic(): void
    {
        $remesaData = $this->createRemesaData();

        $remesaData->setCreditorBic('CAIXESBBXXX');
        $this->assertEquals('CAIXESBBXXX', $remesaData->getCreditorBic());

        $remesaData->setCreditorBic(null);
        $this->assertNull($remesaData->getCreditorBic());
    }

    /**
     * Tests setting batch booking.
     *
     * @return void
     */
    public function testSetBatchBooking(): void
    {
        $remesaData = $this->createRemesaData();

        $remesaData->setBatchBooking(true);
        $this->assertTrue($remesaData->isBatchBooking());
    }

    /**
     * Tests adding transactions.
     *
     * @return void
     */
    public function testAddTransaction(): void
    {
        $remesaData = $this->createRemesaData();

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

        $remesaData->addTransaction($transaction1);
        $remesaData->addTransaction($transaction2);

        $transactions = $remesaData->getTransactions();
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
        $remesaData = $this->createRemesaData();

        $remesaData->addTransaction(new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'ES9121000418450200051332',
            'John Doe'
        ));

        $remesaData->addTransaction(new Transaction(
            'E2E-002',
            200.75,
            'EUR',
            'GB82WEST12345698765432',
            'Jane Smith'
        ));

        $this->assertEquals(301.25, $remesaData->getTotalAmount());
    }

    /**
     * Tests total amount with no transactions.
     *
     * @return void
     */
    public function testGetTotalAmountWithNoTransactions(): void
    {
        $remesaData = $this->createRemesaData();

        $this->assertEquals(0.0, $remesaData->getTotalAmount());
    }

    /**
     * Tests setting creditor address.
     *
     * @return void
     */
    public function testSetCreditorAddress(): void
    {
        $remesaData = $this->createRemesaData();

        $this->assertNull($remesaData->getCreditorAddress());

        $remesaData->setCreditorAddress('123 Business St', 'Madrid', '28001', 'ES');
        $address = $remesaData->getCreditorAddress();

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
        $remesaData = $this->createRemesaData();

        $remesaData->setCreditorAddressFromArray([
            'street' => '456 Corporate Avenue',
            'city' => 'Barcelona',
            'postalCode' => '08001',
            'country' => 'ES',
        ]);

        $address = $remesaData->getCreditorAddress();
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
        $remesaData = $this->createRemesaData();

        $remesaData->setCreditorAddressFromArray([
            'address' => '789 Office Plaza',
            'city' => 'Valencia',
            'postal_code' => '46001',
            'country' => 'ES',
        ]);

        $address = $remesaData->getCreditorAddress();
        $this->assertNotNull($address);
        $this->assertEquals('789 Office Plaza', $address['street']);
        $this->assertEquals('Valencia', $address['city']);
        $this->assertEquals('46001', $address['postalCode']);
        $this->assertEquals('ES', $address['country']);
    }

    /**
     * Creates a remesa data instance for testing.
     *
     * @return RemesaData The remesa data instance
     */
    private function createRemesaData(): RemesaData
    {
        return new RemesaData(
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
