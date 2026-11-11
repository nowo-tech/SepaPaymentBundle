<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\Report;

use DateTimeImmutable;

/**
 * Status of one original transaction in a payment status report (pain.002 `TxInfAndSts`). Match it to your
 * collection by {@see self::getOriginalEndToEndId()}; the bundle does no matching.
 *
 * `TxSts` is optional: a transaction without it takes the status of its block, else of the group (never PART, which
 * says nothing about a single transaction). {@see self::isRejected()} uses that effective status.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final readonly class TransactionStatus
{
    /**
     * Constructor.
     *
     * @param ?string $statusId The status id (`StsId`)
     * @param ?string $originalInstructionId The original instruction id (`OrgnlInstrId`)
     * @param ?string $originalEndToEndId The original end-to-end id (`OrgnlEndToEndId`)
     * @param ?string $status The transaction status (`TxSts`), e.g. "RJCT", "ACCP", "ACSC", "PDNG"
     * @param list<StatusReason> $reasons The status reasons (`StsRsnInf`)
     * @param ?int $amountCents The original amount in cents (`OrgnlTxRef/Amt/InstdAmt`)
     * @param ?string $currency The currency of the original amount
     * @param ?DateTimeImmutable $requestedCollectionDate The original requested collection date (`OrgnlTxRef/ReqdColltnDt`)
     * @param ?string $mandateId The original mandate id (`OrgnlTxRef/MndtRltdInf/MndtId`)
     * @param ?string $inheritedStatus The block or group status that applies when `TxSts` is absent (never "PART")
     */
    public function __construct(
        private ?string $statusId,
        private ?string $originalInstructionId,
        private ?string $originalEndToEndId,
        private ?string $status,
        private array $reasons,
        private ?int $amountCents,
        private ?string $currency,
        private ?DateTimeImmutable $requestedCollectionDate,
        private ?string $mandateId,
        private ?string $inheritedStatus = null,
    ) {
    }

    /**
     * The status id (`StsId`).
     */
    public function getStatusId(): ?string
    {
        return $this->statusId;
    }

    /**
     * The original instruction id (`OrgnlInstrId`).
     */
    public function getOriginalInstructionId(): ?string
    {
        return $this->originalInstructionId;
    }

    /**
     * The original end-to-end id (`OrgnlEndToEndId`).
     */
    public function getOriginalEndToEndId(): ?string
    {
        return $this->originalEndToEndId;
    }

    /**
     * The transaction status (`TxSts`), e.g. "RJCT", "ACCP", "ACSC", "PDNG".
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * The status reasons (`StsRsnInf`).
     *
     * @return list<StatusReason>
     */
    public function getReasons(): array
    {
        return $this->reasons;
    }

    /**
     * The original amount in cents (`OrgnlTxRef/Amt/InstdAmt`).
     */
    public function getAmountCents(): ?int
    {
        return $this->amountCents;
    }

    /**
     * The currency of the original amount.
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * The original requested collection date (`OrgnlTxRef/ReqdColltnDt`).
     */
    public function getRequestedCollectionDate(): ?DateTimeImmutable
    {
        return $this->requestedCollectionDate;
    }

    /**
     * The original mandate id (`OrgnlTxRef/MndtRltdInf/MndtId`).
     */
    public function getMandateId(): ?string
    {
        return $this->mandateId;
    }

    /**
     * The first reason, or null.
     */
    public function getReason(): ?StatusReason
    {
        return $this->reasons[0] ?? null;
    }

    /**
     * The status that applies to the transaction: `TxSts`, else the block status, else the group status (never "PART").
     */
    public function getEffectiveStatus(): ?string
    {
        return $this->status ?? ($this->inheritedStatus === 'PART' ? null : $this->inheritedStatus);
    }

    /**
     * Whether the effective status comes from the block or the group (the transaction has no `TxSts`).
     */
    public function isStatusInherited(): bool
    {
        return $this->status === null && $this->getEffectiveStatus() !== null;
    }

    /**
     * Whether the transaction was rejected: `TxSts` RJCT, or no `TxSts` in a rejected block or group.
     */
    public function isRejected(): bool
    {
        return $this->getEffectiveStatus() === 'RJCT';
    }
}
