<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\Mandate;

use DateTime;
use DateTimeInterface;
use RuntimeException;

/**
 * SEPA mandate entity.
 * Represents a SEPA Direct Debit mandate with all required information.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class Mandate
{
    /**
     * BIC of the debtor's bank (optional).
     */
    private ?string $debtorBic = null;

    /**
     * Whether the mandate is active.
     */
    private bool $active = true;

    /**
     * Mandate status.
     */
    private MandateStatus $status = MandateStatus::ACTIVE;

    /**
     * Expiration date (optional, defaults to 36 months after signature date).
     */
    private ?DateTimeInterface $expirationDate = null;

    /**
     * Revocation date (if revoked).
     */
    private ?DateTimeInterface $revocationDate = null;

    /**
     * Revocation reason (if revoked).
     */
    private ?string $revocationReason = null;

    /**
     * Constructor.
     *
     * @param string $mandateId Mandate identifier
     * @param DateTimeInterface $signatureDate Date when the mandate was signed
     * @param string $debtorIban IBAN of the debtor
     * @param string $debtorName Name of the debtor
     * @param string $type Type of mandate (CORE, B2B)
     * @param string $sequenceType Sequence type (FRST, RCUR, OOFF, FNAL)
     */
    public function __construct(
        private readonly string $mandateId,
        private readonly DateTimeInterface $signatureDate,
        private readonly string $debtorIban,
        private readonly string $debtorName,
        private readonly string $type = 'CORE',
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
     * @return DateTimeInterface The signature date
     */
    public function getSignatureDate(): DateTimeInterface
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

    /**
     * Gets the mandate status.
     *
     * @return MandateStatus The mandate status
     */
    public function getStatus(): MandateStatus
    {
        return $this->status;
    }

    /**
     * Sets the mandate status.
     *
     * @param MandateStatus $status The mandate status
     */
    public function setStatus(MandateStatus $status): self
    {
        $this->status = $status;
        $this->active = ($status === MandateStatus::ACTIVE);

        return $this;
    }

    /**
     * Gets the expiration date.
     *
     * @return DateTimeInterface|null The expiration date or null if not set
     */
    public function getExpirationDate(): ?DateTimeInterface
    {
        if (!$this->expirationDate instanceof DateTimeInterface) {
            // PHPStan: DateTimeInterface has no modify(); only DateTime/DateTimeImmutable do.
            // Fix: create DateTime instance from interface to call modify().
            $expirationDate = DateTime::createFromInterface($this->signatureDate);
            $expirationDate->modify('+36 months');

            return $expirationDate;
        }

        return $this->expirationDate;
    }

    /**
     * Sets the expiration date.
     *
     * @param DateTimeInterface|null $expirationDate The expiration date
     */
    public function setExpirationDate(?DateTimeInterface $expirationDate): self
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    /**
     * Checks if the mandate is expired.
     *
     * @param DateTimeInterface|null $checkDate Optional date to check against (defaults to now)
     *
     * @return bool True if expired, false otherwise
     */
    public function isExpired(?DateTimeInterface $checkDate = null): bool
    {
        $expirationDate = $this->getExpirationDate();
        if (!$expirationDate instanceof DateTimeInterface) {
            return false;
        }

        $checkDate ??= new DateTime();

        return $expirationDate < $checkDate;
    }

    /**
     * Gets the revocation date.
     *
     * @return DateTimeInterface|null The revocation date or null if not revoked
     */
    public function getRevocationDate(): ?DateTimeInterface
    {
        return $this->revocationDate;
    }

    /**
     * Gets the revocation reason.
     *
     * @return string|null The revocation reason or null if not revoked
     */
    public function getRevocationReason(): ?string
    {
        return $this->revocationReason;
    }

    /**
     * Revokes the mandate.
     *
     * @param string|null $reason Optional revocation reason
     */
    public function revoke(?string $reason = null): self
    {
        $this->status           = MandateStatus::REVOKED;
        $this->active           = false;
        $this->revocationDate   = new DateTime();
        $this->revocationReason = $reason;

        return $this;
    }

    /**
     * Suspends the mandate.
     */
    public function suspend(): self
    {
        $this->status = MandateStatus::SUSPENDED;
        $this->active = false;

        return $this;
    }

    /**
     * Reactivates the mandate.
     */
    public function reactivate(): self
    {
        if ($this->isExpired()) {
            throw new RuntimeException('Cannot reactivate an expired mandate');
        }

        $this->status           = MandateStatus::ACTIVE;
        $this->active           = true;
        $this->revocationDate   = null;
        $this->revocationReason = null;

        return $this;
    }
}
