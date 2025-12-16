<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\DirectDebit;

/**
 * Direct Debit transaction data.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class DirectDebitTransaction
{
    /**
     * Remittance information (optional).
     *
     * @var string|null
     */
    private ?string $remittanceInformation = null;

    /**
     * Constructor.
     *
     * @param float              $amount                Amount to debit
     * @param string             $debtorIban            Debtor IBAN
     * @param string             $debtorName            Debtor name
     * @param string             $debtorMandate         Debtor mandate identifier
     * @param \DateTimeInterface $debtorMandateSignDate Debtor mandate sign date
     * @param string             $endToEndId            End-to-end identifier
     */
    public function __construct(
        private float $amount,
        private string $debtorIban,
        private string $debtorName,
        private string $debtorMandate,
        private \DateTimeInterface $debtorMandateSignDate,
        private string $endToEndId
    ) {
    }

    /**
     * Gets the amount.
     *
     * @return float The amount
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Gets the debtor IBAN.
     *
     * @return string The debtor IBAN
     */
    public function getDebtorIban(): string
    {
        return $this->debtorIban;
    }

    /**
     * Gets the debtor name.
     *
     * @return string The debtor name
     */
    public function getDebtorName(): string
    {
        return $this->debtorName;
    }

    /**
     * Gets the debtor mandate identifier.
     *
     * @return string The debtor mandate identifier
     */
    public function getDebtorMandate(): string
    {
        return $this->debtorMandate;
    }

    /**
     * Gets the debtor mandate sign date.
     *
     * @return \DateTimeInterface The debtor mandate sign date
     */
    public function getDebtorMandateSignDate(): \DateTimeInterface
    {
        return $this->debtorMandateSignDate;
    }

    /**
     * Gets the end-to-end identifier.
     *
     * @return string The end-to-end identifier
     */
    public function getEndToEndId(): string
    {
        return $this->endToEndId;
    }

    /**
     * Sets the remittance information.
     *
     * @param string|null $remittanceInformation The remittance information
     * @return self
     */
    public function setRemittanceInformation(?string $remittanceInformation): self
    {
        $this->remittanceInformation = $remittanceInformation;

        return $this;
    }

    /**
     * Gets the remittance information.
     *
     * @return string|null The remittance information
     */
    public function getRemittanceInformation(): ?string
    {
        return $this->remittanceInformation;
    }
}

