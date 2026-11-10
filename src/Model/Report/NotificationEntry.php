<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\Report;

use DateTimeImmutable;

/**
 * One booked (or pending) entry of a camt.054 notification (`Ntry`). An entry may hold a batch of transactions.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final readonly class NotificationEntry
{
    /**
     * Constructor.
     *
     * @param ?string $entryReference The entry reference (`NtryRef`)
     * @param int $amountCents The entry amount in cents (`Amt`)
     * @param string $currency The entry currency
     * @param string $creditDebitIndicator CRDT or DBIT (`CdtDbtInd`)
     * @param bool $reversal Whether the entry is a reversal (`RvslInd`)
     * @param ?string $status The entry status (`Sts` or `Sts/Cd`), e.g. "BOOK", "PDNG"
     * @param ?DateTimeImmutable $bookingDate The booking date (`BookgDt/Dt` or `BookgDt/DtTm`)
     * @param ?DateTimeImmutable $valueDate The value date (`ValDt/Dt` or `ValDt/DtTm`)
     * @param ?string $accountServicerReference The bank's reference (`AcctSvcrRef`)
     * @param ?string $bankTransactionCode The bank transaction code as "Domain/Family/SubFamily" (e.g. "PMNT/RDDT/UPDD") or the proprietary code
     * @param list<NotificationTransaction> $transactions The transactions of the entry (`NtryDtls/TxDtls`)
     */
    public function __construct(
        private ?string $entryReference,
        private int $amountCents,
        private string $currency,
        private string $creditDebitIndicator,
        private bool $reversal,
        private ?string $status,
        private ?DateTimeImmutable $bookingDate,
        private ?DateTimeImmutable $valueDate,
        private ?string $accountServicerReference,
        private ?string $bankTransactionCode,
        private array $transactions,
    ) {
    }

    /**
     * The entry reference (`NtryRef`).
     */
    public function getEntryReference(): ?string
    {
        return $this->entryReference;
    }

    /**
     * The entry amount in cents (`Amt`).
     */
    public function getAmountCents(): int
    {
        return $this->amountCents;
    }

    /**
     * The entry currency.
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * CRDT or DBIT (`CdtDbtInd`).
     */
    public function getCreditDebitIndicator(): string
    {
        return $this->creditDebitIndicator;
    }

    /**
     * Whether the entry is a reversal (`RvslInd`).
     */
    public function isReversal(): bool
    {
        return $this->reversal;
    }

    /**
     * The entry status (`Sts` or `Sts/Cd`), e.g. "BOOK", "PDNG".
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * The booking date (`BookgDt/Dt` or `BookgDt/DtTm`).
     */
    public function getBookingDate(): ?DateTimeImmutable
    {
        return $this->bookingDate;
    }

    /**
     * The value date (`ValDt/Dt` or `ValDt/DtTm`).
     */
    public function getValueDate(): ?DateTimeImmutable
    {
        return $this->valueDate;
    }

    /**
     * The bank's reference (`AcctSvcrRef`).
     */
    public function getAccountServicerReference(): ?string
    {
        return $this->accountServicerReference;
    }

    /**
     * The bank transaction code as "Domain/Family/SubFamily" (e.g. "PMNT/RDDT/UPDD") or the proprietary code.
     */
    public function getBankTransactionCode(): ?string
    {
        return $this->bankTransactionCode;
    }

    /**
     * The transactions of the entry (`NtryDtls/TxDtls`).
     *
     * @return list<NotificationTransaction>
     */
    public function getTransactions(): array
    {
        return $this->transactions;
    }
}
