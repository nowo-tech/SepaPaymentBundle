<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\DirectDebit;

use DateTimeInterface;
use InvalidArgumentException;

use function sprintf;

/**
 * One payment-information block (`PmtInf`) of a direct debit file: its id, due date, local instrument, sequence type
 * and transactions. The creditor (name, IBAN, BIC, scheme id, address) stays on {@see DirectDebitData}.
 *
 * Add several blocks to one file with {@see DirectDebitData::addPaymentInformation()} or
 * {@see DirectDebitData::fromPaymentInformation()}, e.g. one block per sequence type, due date or local instrument.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class PaymentInformationData
{
    /**
     * Transactions of this block.
     *
     * @var list<DirectDebitTransaction>
     */
    private array $transactions = [];

    /**
     * Sequence type (FRST, RCUR, OOFF, FNAL) or null.
     */
    private ?string $sequenceType;

    /**
     * Constructor.
     *
     * @param string $paymentInfoId Payment information id (`PmtInfId`, max. 35 characters, unique in the file)
     * @param DateTimeInterface $dueDate Requested collection date (`ReqdColltnDt`)
     * @param string|null $sequenceType FRST, RCUR, OOFF or FNAL; null when every transaction carries its own sequence type
     *                                  (or when the generator runs with {@see SequenceTypeMode::ALWAYS_RCUR} / {@see SequenceTypeMode::OMIT})
     * @param string $localInstrumentCode Local instrument (`LclInstrm/Cd`): CORE or B2B
     * @param list<DirectDebitTransaction> $transactions Transactions of the block
     *
     * @throws InvalidArgumentException If the sequence type is not FRST, RCUR, OOFF or FNAL
     */
    public function __construct(
        private readonly string $paymentInfoId,
        private readonly DateTimeInterface $dueDate,
        ?string $sequenceType = null,
        private readonly string $localInstrumentCode = 'CORE',
        array $transactions = [],
    ) {
        if ($sequenceType !== null) {
            SequenceType::assertValid($sequenceType, sprintf('payment information "%s"', $paymentInfoId));
        }
        $this->sequenceType = $sequenceType;
        foreach ($transactions as $transaction) {
            $this->addTransaction($transaction);
        }
    }

    /**
     * The block of a {@see DirectDebitData} built with the single-block constructor (2.1 API). The sequence type is
     * not checked, so the output stays exactly as in 2.1.
     *
     * @param list<DirectDebitTransaction> $transactions
     *
     * @internal
     */
    public static function fromSingleBlock(string $paymentInfoId, DateTimeInterface $dueDate, string $sequenceType, string $localInstrumentCode, array $transactions): self
    {
        $block = new self($paymentInfoId, $dueDate, null, $localInstrumentCode, $transactions);
        // @igor-ignore - Fresh value object built here; not a shared service.
        $block->sequenceType = $sequenceType;

        return $block;
    }

    /**
     * Gets the payment information id.
     */
    public function getPaymentInfoId(): string
    {
        return $this->paymentInfoId;
    }

    /**
     * Gets the requested collection date.
     */
    public function getDueDate(): DateTimeInterface
    {
        return $this->dueDate;
    }

    /**
     * Gets the sequence type (null when the transactions carry their own).
     */
    public function getSequenceType(): ?string
    {
        return $this->sequenceType;
    }

    /**
     * Gets the local instrument code (CORE or B2B).
     */
    public function getLocalInstrumentCode(): string
    {
        return $this->localInstrumentCode;
    }

    /**
     * Adds a transaction to the block.
     *
     * @param DirectDebitTransaction $transaction The transaction
     */
    public function addTransaction(DirectDebitTransaction $transaction): self
    {
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
        $this->transactions[] = $transaction;

        return $this;
    }

    /**
     * Gets the transactions of the block.
     *
     * @return list<DirectDebitTransaction>
     */
    public function getTransactions(): array
    {
        return $this->transactions;
    }

    /**
     * Gets the total amount of the block in cents.
     */
    public function getTotalAmountCents(): int
    {
        $total = 0;
        foreach ($this->transactions as $transaction) {
            $total += $transaction->getAmountCents();
        }

        return $total;
    }
}
