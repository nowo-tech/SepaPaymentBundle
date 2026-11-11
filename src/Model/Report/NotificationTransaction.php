<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\Report;

/**
 * One transaction of a camt.054 entry (`NtryDtls/TxDtls`): references, amount, parties and, for an
 * R-transaction (return, refund, reject), the return reason. It also carries its entry's status, reversal flag and
 * credit / debit indicator: book a collection only from a booked ({@see self::isBooked()}), non-reversed entry.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final readonly class NotificationTransaction
{
    /**
     * Constructor.
     *
     * @param ?string $endToEndId The end-to-end id (`Refs/EndToEndId`)
     * @param ?string $instructionId The instruction id (`Refs/InstrId`)
     * @param ?string $paymentInformationId The payment information id (`Refs/PmtInfId`)
     * @param ?string $mandateId The mandate id (`Refs/MndtId`)
     * @param ?string $accountServicerReference The bank's reference (`Refs/AcctSvcrRef`)
     * @param ?int $amountCents The transaction amount in cents (`Amt` in .08, `AmtDtls/TxAmt/Amt` or `AmtDtls/InstdAmt/Amt`)
     * @param ?string $currency The currency of the amount
     * @param ?string $creditDebitIndicator CRDT or DBIT (`TxDtls/CdtDbtInd`, .08 only; null otherwise)
     * @param ?string $debtorName The debtor name (`RltdPties/Dbtr`)
     * @param ?string $debtorIban The debtor IBAN (`RltdPties/DbtrAcct/Id/IBAN`)
     * @param ?string $creditorName The creditor name (`RltdPties/Cdtr`)
     * @param ?string $creditorIban The creditor IBAN (`RltdPties/CdtrAcct/Id/IBAN`)
     * @param list<string> $remittanceInformation The unstructured remittance lines (`RmtInf/Ustrd`)
     * @param ?StatusReason $returnReason The return reason (`RtrInf`), for an R-transaction
     * @param ?string $entryStatus The status of the entry (`Ntry/Sts`), e.g. "BOOK", "PDNG", "INFO"
     * @param bool $entryReversal Whether the entry is a reversal (`Ntry/RvslInd`)
     * @param ?string $entryCreditDebitIndicator CRDT or DBIT of the entry (`Ntry/CdtDbtInd`)
     */
    public function __construct(
        private ?string $endToEndId,
        private ?string $instructionId,
        private ?string $paymentInformationId,
        private ?string $mandateId,
        private ?string $accountServicerReference,
        private ?int $amountCents,
        private ?string $currency,
        private ?string $creditDebitIndicator,
        private ?string $debtorName,
        private ?string $debtorIban,
        private ?string $creditorName,
        private ?string $creditorIban,
        private array $remittanceInformation,
        private ?StatusReason $returnReason,
        private ?string $entryStatus = null,
        private bool $entryReversal = false,
        private ?string $entryCreditDebitIndicator = null,
    ) {
    }

    /**
     * The end-to-end id (`Refs/EndToEndId`).
     */
    public function getEndToEndId(): ?string
    {
        return $this->endToEndId;
    }

    /**
     * The instruction id (`Refs/InstrId`).
     */
    public function getInstructionId(): ?string
    {
        return $this->instructionId;
    }

    /**
     * The payment information id (`Refs/PmtInfId`).
     */
    public function getPaymentInformationId(): ?string
    {
        return $this->paymentInformationId;
    }

    /**
     * The mandate id (`Refs/MndtId`).
     */
    public function getMandateId(): ?string
    {
        return $this->mandateId;
    }

    /**
     * The bank's reference (`Refs/AcctSvcrRef`).
     */
    public function getAccountServicerReference(): ?string
    {
        return $this->accountServicerReference;
    }

    /**
     * The transaction amount in cents (`Amt` in .08, `AmtDtls/TxAmt/Amt` or `AmtDtls/InstdAmt/Amt`).
     */
    public function getAmountCents(): ?int
    {
        return $this->amountCents;
    }

    /**
     * The currency of the amount.
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * CRDT or DBIT: the transaction's own `CdtDbtInd` (.08), else the entry's (always in .02, which has none per
     * transaction).
     */
    public function getCreditDebitIndicator(): ?string
    {
        return $this->creditDebitIndicator ?? $this->entryCreditDebitIndicator;
    }

    /**
     * The status of the entry (`Ntry/Sts`), e.g. "BOOK", "PDNG", "INFO".
     */
    public function getEntryStatus(): ?string
    {
        return $this->entryStatus;
    }

    /**
     * Whether the entry is booked (`Ntry/Sts` BOOK). Pending ("PDNG") and information ("INFO") entries move no money.
     */
    public function isBooked(): bool
    {
        return $this->entryStatus === 'BOOK';
    }

    /**
     * Whether the entry is a reversal (`Ntry/RvslInd`): it cancels an earlier entry, so treat it as the opposite movement.
     */
    public function isReversal(): bool
    {
        return $this->entryReversal;
    }

    /**
     * The debtor name (`RltdPties/Dbtr`).
     */
    public function getDebtorName(): ?string
    {
        return $this->debtorName;
    }

    /**
     * The debtor IBAN (`RltdPties/DbtrAcct/Id/IBAN`).
     */
    public function getDebtorIban(): ?string
    {
        return $this->debtorIban;
    }

    /**
     * The creditor name (`RltdPties/Cdtr`).
     */
    public function getCreditorName(): ?string
    {
        return $this->creditorName;
    }

    /**
     * The creditor IBAN (`RltdPties/CdtrAcct/Id/IBAN`).
     */
    public function getCreditorIban(): ?string
    {
        return $this->creditorIban;
    }

    /**
     * The unstructured remittance lines (`RmtInf/Ustrd`).
     *
     * @return list<string>
     */
    public function getRemittanceInformation(): array
    {
        return $this->remittanceInformation;
    }

    /**
     * The return reason (`RtrInf`), for an R-transaction.
     */
    public function getReturnReason(): ?StatusReason
    {
        return $this->returnReason;
    }

    /**
     * Whether the transaction carries a return reason (an R-transaction).
     */
    public function isReturn(): bool
    {
        return $this->returnReason instanceof StatusReason;
    }
}
