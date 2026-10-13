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
     * Number of months without a collection after which a SEPA mandate lapses
     * (EPC SDD Core/B2B rulebooks: 36 months after the last collection presented).
     */
    public const INACTIVITY_EXPIRY_MONTHS = 36;

    /**
     * Explicit expiration date (optional). When null, the expiry is derived from the
     * last collection date (or the signature date if no collection was ever presented).
     */
    private ?DateTimeInterface $expirationDate = null;

    /**
     * Date of the last collection presented under this mandate (even if rejected, returned or refunded).
     */
    private ?DateTimeInterface $lastCollectionDate = null;

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
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
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
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
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
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
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
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
        $this->status = $status;
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
        $this->active = ($status === MandateStatus::ACTIVE);

        return $this;
    }

    /**
     * Gets the expiration date.
     *
     * Resolution order:
     *  1. the explicit expiration date set via {@see setExpirationDate()};
     *  2. last collection date + 36 months (EPC SDD rulebook: a mandate lapses when no
     *     collection has been presented for 36 months);
     *  3. signature date + 36 months when no collection has ever been presented.
     *
     * @return DateTimeInterface|null The expiration date
     */
    public function getExpirationDate(): ?DateTimeInterface
    {
        if ($this->expirationDate instanceof DateTimeInterface) {
            return $this->expirationDate;
        }

        return $this->getInactivityExpirationDate();
    }

    /**
     * Gets the date on which the mandate lapses for inactivity (36 months after the last
     * collection, or after the signature date when no collection was ever presented).
     * Ignores any explicit expiration date.
     *
     * @return DateTimeInterface The inactivity expiration date
     */
    public function getInactivityExpirationDate(): DateTimeInterface
    {
        // DateTimeInterface has no modify(); create a mutable copy to avoid touching the source.
        $expirationDate = DateTime::createFromInterface($this->lastCollectionDate ?? $this->signatureDate);
        $expirationDate->modify('+' . self::INACTIVITY_EXPIRY_MONTHS . ' months');

        return $expirationDate;
    }

    /**
     * Gets the date of the last collection presented under this mandate.
     *
     * @return DateTimeInterface|null The last collection date or null if none was presented
     */
    public function getLastCollectionDate(): ?DateTimeInterface
    {
        return $this->lastCollectionDate;
    }

    /**
     * Sets the date of the last collection presented under this mandate
     * (even if it was later rejected, returned or refunded).
     *
     * @param DateTimeInterface|null $lastCollectionDate The last collection date
     */
    public function setLastCollectionDate(?DateTimeInterface $lastCollectionDate): self
    {
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
        $this->lastCollectionDate = $lastCollectionDate;

        return $this;
    }

    /**
     * Records a collection presented under this mandate. Only moves the last collection
     * date forward (an older collection date never shortens the mandate validity).
     *
     * @param DateTimeInterface $collectionDate The collection (due) date
     */
    public function recordCollection(DateTimeInterface $collectionDate): self
    {
        if (!$this->lastCollectionDate instanceof DateTimeInterface || $collectionDate > $this->lastCollectionDate) {
            // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
            $this->lastCollectionDate = $collectionDate;
        }

        return $this;
    }

    /**
     * Sets the expiration date.
     *
     * @param DateTimeInterface|null $expirationDate The expiration date
     */
    public function setExpirationDate(?DateTimeInterface $expirationDate): self
    {
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
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
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
        $this->status = MandateStatus::REVOKED;
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
        $this->active = false;
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
        $this->revocationDate = new DateTime();
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
        $this->revocationReason = $reason;

        return $this;
    }

    /**
     * Suspends the mandate.
     */
    public function suspend(): self
    {
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
        $this->status = MandateStatus::SUSPENDED;
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
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

        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
        $this->status = MandateStatus::ACTIVE;
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
        $this->active = true;
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
        $this->revocationDate = null;
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
        $this->revocationReason = null;

        return $this;
    }
}
