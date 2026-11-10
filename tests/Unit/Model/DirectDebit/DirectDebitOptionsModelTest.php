<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Model\DirectDebit;

use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitData;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitGenerationOptions;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitTransaction;
use Nowo\SepaPaymentBundle\Model\DirectDebit\Pain008Version;
use Nowo\SepaPaymentBundle\Model\DirectDebit\PaymentInformationData;
use Nowo\SepaPaymentBundle\Model\DirectDebit\SequenceType;
use Nowo\SepaPaymentBundle\Model\DirectDebit\SequenceTypeMode;
use PHPUnit\Framework\TestCase;

/**
 * Value objects added in 2.2: versions, modes, options, payment-information blocks.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class DirectDebitOptionsModelTest extends TestCase
{
    public function testVersions(): void
    {
        self::assertSame(['pain.008.001.02', 'pain.008.001.08', 'pain.008.001.09'], Pain008Version::values());
        self::assertSame(Pain008Version::V09, Pain008Version::DEFAULT);
        self::assertSame(Pain008Version::V08, Pain008Version::fromValue('pain.008.001.08'));
        self::assertSame(Pain008Version::V02, Pain008Version::fromValue(Pain008Version::V02));
        self::assertSame('urn:iso:std:iso:20022:tech:xsd:pain.008.001.08', Pain008Version::V08->getNamespace());

        $this->expectException(InvalidArgumentException::class);
        Pain008Version::fromValue('pain.008.001.10');
    }

    public function testModes(): void
    {
        self::assertSame(['per_transaction', 'always_rcur', 'omit'], SequenceTypeMode::values());
        self::assertSame(SequenceTypeMode::OMIT, SequenceTypeMode::fromValue('omit'));
        self::assertSame(SequenceTypeMode::ALWAYS_RCUR, SequenceTypeMode::fromValue(SequenceTypeMode::ALWAYS_RCUR));

        $this->expectExceptionMessage('Unsupported sequence type mode "never"');
        SequenceTypeMode::fromValue('never');
    }

    public function testOptions(): void
    {
        $empty = new DirectDebitGenerationOptions();
        self::assertNull($empty->getPainVersion());
        self::assertNull($empty->getSequenceTypeMode());
        self::assertNull($empty->getValidateXsd());
        self::assertNull($empty->getCreationDateTime());

        $date    = new DateTime('2026-11-10 10:00:00');
        $options = new DirectDebitGenerationOptions('pain.008.001.02', 'always_rcur', true, $date);
        $date->modify('+1 day');
        self::assertSame(Pain008Version::V02, $options->getPainVersion());
        self::assertSame(SequenceTypeMode::ALWAYS_RCUR, $options->getSequenceTypeMode());
        self::assertTrue($options->getValidateXsd());
        self::assertSame('2026-11-10', $options->getCreationDateTime()?->format('Y-m-d'), 'copied, not shared');
    }

    public function testSequenceTypeValidation(): void
    {
        self::assertSame('RCUR', SequenceType::assertValid('RCUR', 'x'));

        $this->expectExceptionMessage('Invalid sequence type "frst" for payment information "P"');
        new PaymentInformationData('P', new DateTimeImmutable(), 'frst');
    }

    public function testTransactionSequenceTypeValidation(): void
    {
        $transaction = DirectDebitTransaction::fromCents(1, 'GB82WEST12345698765432', 'A', 'M', new DateTimeImmutable(), 'E2E-T');
        self::assertNull($transaction->getSequenceType());
        self::assertSame('FRST', $transaction->setSequenceType('FRST')->getSequenceType());
        self::assertNull($transaction->setSequenceType(null)->getSequenceType());

        $this->expectExceptionMessage('transaction "E2E-T"');
        $transaction->setSequenceType('XXXX');
    }

    public function testPaymentInformationBlocks(): void
    {
        $tx    = DirectDebitTransaction::fromCents(250, 'GB82WEST12345698765432', 'A', 'M', new DateTimeImmutable(), 'E1');
        $block = new PaymentInformationData('P1', new DateTimeImmutable('2026-12-01'), 'RCUR', 'B2B', [$tx]);
        $block->addTransaction(DirectDebitTransaction::fromCents(50, 'GB82WEST12345698765432', 'B', 'M', new DateTimeImmutable(), 'E2'));
        self::assertSame('P1', $block->getPaymentInfoId());
        self::assertSame('RCUR', $block->getSequenceType());
        self::assertSame('B2B', $block->getLocalInstrumentCode());
        self::assertSame('2026-12-01', $block->getDueDate()->format('Y-m-d'));
        self::assertSame(300, $block->getTotalAmountCents());

        $data = DirectDebitData::fromPaymentInformation('M', 'I', 'C', 'ES9121000418450200051332', 'ES59ZZZX1234567', $block, new PaymentInformationData('P2', new DateTimeImmutable('2026-12-02')));
        self::assertSame('P1', $data->getPaymentInfoId());
        self::assertSame('B2B', $data->getLocalInstrumentCode());
        self::assertCount(2, $data->getPaymentInformation(), 'the constructor block has no transactions and is not written');
        self::assertCount(2, $data->getTransactions());
        self::assertSame(300, $data->getTotalAmountCents());

        $legacy = new DirectDebitData('M', 'I', 'P0', new DateTimeImmutable(), 'C', 'ES9121000418450200051332', 'lowercase-kept', 'ES59ZZZX1234567', 'CORE');
        self::assertCount(1, $legacy->getPaymentInformation(), 'an empty single block is still written');
        self::assertSame('lowercase-kept', $legacy->getPaymentInformation()[0]->getSequenceType(), '2.1 single-block values are not checked');
    }
}
