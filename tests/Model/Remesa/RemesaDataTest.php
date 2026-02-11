<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Model\Remesa;

use Nowo\SepaPaymentBundle\Model\Remesa\RemesaData;
use Nowo\SepaPaymentBundle\Model\Remesa\Transaction;
use PHPUnit\Framework\TestCase;

/**
 * Tests for RemesaData (deprecated API).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class RemesaDataTest extends TestCase
{
    public function testRemesaDataGettersAndSetters(): void
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

        $this->assertEquals('MSG-001', $remesaData->getMessageId());
        $this->assertEquals('My Company', $remesaData->getInitiatingPartyName());
        $this->assertEquals('PMT-001', $remesaData->getPaymentInfoId());
        $this->assertEquals('ES9121000418450200051332', $remesaData->getCreditorIban());
        $this->assertEquals('My Company Name', $remesaData->getCreditorName());

        $remesaData->setCreditorBic('CAIXESBBXXX');
        $this->assertEquals('CAIXESBBXXX', $remesaData->getCreditorBic());

        $remesaData->setBatchBooking(true);
        $this->assertTrue($remesaData->isBatchBooking());

        $remesaData->setCreditorAddressFromArray([
            'street' => 'Calle 1',
            'city' => 'Madrid',
            'postalCode' => '28001',
            'country' => 'ES',
        ]);
        $this->assertNotNull($remesaData->getCreditorAddress());
        $this->assertEquals('Madrid', $remesaData->getCreditorAddress()['city']);

        $remesaData->setCreditorAddress(['street' => 'Calle 2', 'city' => 'Barcelona', 'postalCode' => '08001', 'country' => 'ES']);
        $this->assertEquals('Barcelona', $remesaData->getCreditorAddress()['city']);
    }

    public function testRemesaDataAddTransactionAndGetTransactions(): void
    {
        $remesaData = new RemesaData(
            'MSG-001',
            new \DateTime('2024-01-15'),
            'Init',
            'PMT-001',
            'ES9121000418450200051332',
            'Creditor',
            new \DateTime('2024-01-20')
        );

        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'GB82WEST12345698765432',
            'John Doe'
        );
        $transaction->setDebtorBic('WESTGB22');
        $transaction->setRemittanceInformation('Ref');
        $transaction->setDebtorAddressFromArray(['street' => 'Street', 'city' => 'London', 'postalCode' => 'SW1', 'country' => 'GB']);

        $remesaData->addTransaction($transaction);

        $transactions = $remesaData->getTransactions();
        $this->assertCount(1, $transactions);
        $this->assertEquals('E2E-001', $transactions[0]->getEndToEndId());
        $this->assertEquals(100.50, $remesaData->getTotalAmount());
    }

    public function testGetCreditTransferData(): void
    {
        $remesaData = new RemesaData(
            'MSG-001',
            new \DateTime('2024-01-15'),
            'Init',
            'PMT-001',
            'ES9121000418450200051332',
            'Creditor',
            new \DateTime('2024-01-20')
        );
        $creditTransferData = $remesaData->getCreditTransferData();
        $this->assertNotNull($creditTransferData);
        $this->assertEquals('MSG-001', $creditTransferData->getMessageId());
    }
}
