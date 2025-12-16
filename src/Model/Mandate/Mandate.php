<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\Mandate;

/**
 * SEPA mandate entity.
 * Represents a SEPA Direct Debit mandate with all required information.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class Mandate
{
    /**
     * BIC of the debtor's bank (optional).
     *
     * @var string|null
     */
    private ?string $debtorBic = null;

    /**
     * Whether the mandate is active.
     *
     * @var bool
     */
    private bool $active = true;

    /**
     * Constructor.
     *
     * @param string             $mandateId     Mandate identifier
     * @param \DateTimeInterface $signatureDate Date when the mandate was signed
     * @param string             $debtorIban    IBAN of the debtor
     * @param string             $debtorName    Name of the debtor
     * @param string             $type          Type of mandate (CORE, B2B)
     * @param string             $sequenceType  Sequence type (FRST, RCUR, OOFF, FNAL)
     */
    public function __construct(
        private string $mandateId,
        private \DateTimeInterface $signatureDate,
        private string $debtorIban,
        private string $debtorName,
        private string $type = 'CORE',
        private string $sequenceType = 'FRST'
    ) {
    }

    /**
     * Gets the mandate identifier.
     *
     * @return string The mandate identifier
     */
    public function getMandateId(): string
    {
        return $this->mandateId;
    }

    /**
     * Gets the signature date.
     *
     * @return \DateTimeInterface The signature date
     */
    public function getSignatureDate(): \DateTimeInterface
    {
        return $this->signatureDate;
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
     * Gets the mandate type.
     *
     * @return string The mandate type
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Sets the sequence type.
     *
     * @param string $sequenceType The sequence type (FRST, RCUR, OOFF, FNAL)
     *
     * @return self
     */
    public function setSequenceType(string $sequenceType): self
    {
        $this->sequenceType = $sequenceType;

        return $this;
    }

    /**
     * Gets the sequence type.
     *
     * @return string The sequence type
     */
    public function getSequenceType(): string
    {
        return $this->sequenceType;
    }

    /**
     * Sets whether the mandate is active.
     *
     * @param bool $active Whether the mandate is active
     *
     * @return self
     */
    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Checks if the mandate is active.
     *
     * @return bool True if active, false otherwise
     */
    public function isActive(): bool
    {
        return $this->active;
    }
}
