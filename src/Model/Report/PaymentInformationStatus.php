<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\Report;

/**
 * Status of one original payment-information block in a payment status report (pain.002 `OrgnlPmtInfAndSts`).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final readonly class PaymentInformationStatus
{
    /**
     * Constructor.
     *
     * @param string $originalPaymentInformationId The original PmtInfId (`OrgnlPmtInfId`)
     * @param ?string $status The block status (`PmtInfSts`), e.g. "RJCT", "PART", "ACCP"
     * @param list<StatusReason> $reasons The status reasons of the block (`StsRsnInf`)
     * @param list<TransactionStatus> $transactions The transaction statuses (`TxInfAndSts`)
     * @param ?string $groupStatus The group status (`GrpSts`), which applies when `PmtInfSts` is absent (never "PART")
     */
    public function __construct(
        private string $originalPaymentInformationId,
        private ?string $status,
        private array $reasons,
        private array $transactions,
        private ?string $groupStatus = null,
    ) {
    }

    /**
     * The original PmtInfId (`OrgnlPmtInfId`).
     */
    public function getOriginalPaymentInformationId(): string
    {
        return $this->originalPaymentInformationId;
    }

    /**
     * The block status (`PmtInfSts`), e.g. "RJCT", "PART", "ACCP".
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * The status reasons of the block (`StsRsnInf`).
     *
     * @return list<StatusReason>
     */
    public function getReasons(): array
    {
        return $this->reasons;
    }

    /**
     * The transaction statuses (`TxInfAndSts`).
     *
     * @return list<TransactionStatus>
     */
    public function getTransactions(): array
    {
        return $this->transactions;
    }

    /**
     * The status that applies to the block: `PmtInfSts`, else the group status (never an inherited "PART").
     */
    public function getEffectiveStatus(): ?string
    {
        return $this->status ?? ($this->groupStatus === 'PART' ? null : $this->groupStatus);
    }

    /**
     * Whether the whole block was rejected (`PmtInfSts` RJCT, or no `PmtInfSts` in a rejected group), with or
     * without transaction lines.
     */
    public function isRejected(): bool
    {
        return $this->getEffectiveStatus() === 'RJCT';
    }
}
