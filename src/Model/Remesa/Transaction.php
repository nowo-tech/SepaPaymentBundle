<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\Remesa;

/**
 * Transaction data for a SEPA Credit Transfer.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class Transaction
{
    /**
     * Debtor BIC (optional).
     *
     * @var string|null
     */
    private ?string $debtorBic = null;

    /**
     * Remittance information (optional).
     *
     * @var string|null
     */
    private ?string $remittanceInformation = null;

    /**
     * Constructor.
     *
     * @param string $endToEndId End-to-end identifier
     * @param float  $amount     Amount to transfer
     * @param string $currency   Currency code (ISO 4217)
     * @param string $debtorIban Debtor IBAN
     * @param string $debtorName Debtor name
     */
    public function __construct(
        private string $endToEndId,
        private float $amount,
        private string $currency,
        private string $debtorIban,
        private string $debtorName
    ) {
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
     * Gets the amount.
     *
     * @return float The amount
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Gets the currency code.
     *
     * @return string The currency code
     */
    public function getCurrency(): string
    {
        return $this->currency;
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
     * Sets the debtor BIC.
     *
     * @param string|null $debtorBic The debtor BIC
     *
     * @return self
     */
    public function setDebtorBic(?string $debtorBic): self
    {
        $this->debtorBic = $debtorBic;

        return $this;
    }

    /**
     * Gets the debtor BIC.
     *
     * @return string|null The debtor BIC
     */
    public function getDebtorBic(): ?string
    {
        return $this->debtorBic;
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
     * Sets the remittance information.
     *
     * @param string|null $remittanceInformation The remittance information
     *
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
