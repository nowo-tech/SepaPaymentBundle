<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\Mandate;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use RuntimeException;

use function mb_strlen;

/**
 * SEPA mandate entity.
 * Represents a SEPA Direct Debit mandate with all required information.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class Mandate
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
     * Debtor postal address (optional, since 2.3): keys street, city, postalCode, country.
     *
     * @var array{street: ?string, city: ?string, postalCode: ?string, country: ?string}|null
     */
    private ?array $debtorAddress = null;

    /**
     * The host's reference of the mandate owner (customer, account, tenant…; optional, since 2.3).
     */
    private ?string $ownerReference = null;

    /**
     * Evidence of the debtor's acceptance (optional, since 2.3).
     */
    private ?MandateEvidence $evidence = null;

    /**
     * Pending amendment since the last collection (optional, since 2.3).
     */
    private ?MandateAmendment $amendment = null;

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
     * @return DateTimeInterface The expiration date (never null: the inactivity rule always applies)
     */
    public function getExpirationDate(): DateTimeInterface
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
        $checkDate ??= new DateTime();

        return $this->getExpirationDate() < $checkDate;
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
     * @param DateTimeInterface|null $revokedAt Revocation time (null = now); services pass their clock's time
     */
    public function revoke(?string $reason = null, ?DateTimeInterface $revokedAt = null): self
    {
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
        $this->status = MandateStatus::REVOKED;
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
        $this->active = false;
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
        $this->revocationDate = $revokedAt ?? new DateTime();
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

    /**
     * Sets the debtor postal address (since 2.3).
     *
     * @param string|null $street Street and number
     * @param string|null $city Town
     * @param string|null $postalCode Postal code
     * @param string|null $country ISO 3166 alpha-2 country code
     */
    public function setDebtorAddress(?string $street, ?string $city = null, ?string $postalCode = null, ?string $country = null): self
    {
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
        $this->debtorAddress = ($street ?? $city ?? $postalCode ?? $country) === null ? null : [
            'street'     => $street,
            'city'       => $city,
            'postalCode' => $postalCode,
            'country'    => $country,
        ];

        return $this;
    }

    /**
     * Gets the debtor postal address (keys street, city, postalCode, country), or null.
     *
     * @return array{street: ?string, city: ?string, postalCode: ?string, country: ?string}|null
     */
    public function getDebtorAddress(): ?array
    {
        return $this->debtorAddress;
    }

    /**
     * Sets the host's reference of the mandate owner, e.g. a customer or tenant id (since 2.3; max. 255 characters).
     *
     * @throws InvalidArgumentException If the reference is empty or longer than 255 characters
     */
    public function setOwnerReference(?string $ownerReference): self
    {
        if ($ownerReference !== null && ($ownerReference === '' || mb_strlen($ownerReference, 'UTF-8') > 255)) {
            throw new InvalidArgumentException('Invalid mandate owner reference: use 1 to 255 characters.');
        }
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
        $this->ownerReference = $ownerReference;

        return $this;
    }

    /**
     * Gets the host's reference of the mandate owner.
     */
    public function getOwnerReference(): ?string
    {
        return $this->ownerReference;
    }

    /**
     * Sets the evidence of the debtor's acceptance (since 2.3).
     */
    public function setEvidence(?MandateEvidence $evidence): self
    {
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
        $this->evidence = $evidence;

        return $this;
    }

    /**
     * Gets the evidence of the debtor's acceptance.
     */
    public function getEvidence(): ?MandateEvidence
    {
        return $this->evidence;
    }

    /**
     * Sets the amendment to report with the next collection (since 2.3); clear it once collected.
     */
    public function setAmendment(?MandateAmendment $amendment): self
    {
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
        $this->amendment = $amendment;

        return $this;
    }

    /**
     * Gets the pending amendment.
     */
    public function getAmendment(): ?MandateAmendment
    {
        return $this->amendment;
    }

    /**
     * Every field as an immutable snapshot, for a host repository that stores mandates its own way (DDD path, since
     * 2.3). The explicit expiration date is kept as set (null when the inactivity rule applies).
     */
    public function toSnapshot(): MandateSnapshot
    {
        return new MandateSnapshot(
            $this->mandateId,
            DateTimeImmutable::createFromInterface($this->signatureDate),
            $this->debtorIban,
            $this->debtorName,
            $this->type,
            $this->sequenceType,
            $this->debtorBic,
            $this->status,
            $this->active,
            $this->expirationDate instanceof DateTimeInterface ? DateTimeImmutable::createFromInterface($this->expirationDate) : null,
            $this->lastCollectionDate instanceof DateTimeInterface ? DateTimeImmutable::createFromInterface($this->lastCollectionDate) : null,
            $this->revocationDate instanceof DateTimeInterface ? DateTimeImmutable::createFromInterface($this->revocationDate) : null,
            $this->revocationReason,
            $this->debtorAddress,
            $this->ownerReference,
            $this->evidence,
            $this->amendment,
        );
    }

    /**
     * Rebuilds a mandate from a snapshot, without running any lifecycle rule (no expiry check, no history, no event).
     */
    public static function reconstitute(MandateSnapshot $snapshot): self
    {
        $mandate = new self(
            $snapshot->getMandateId(),
            $snapshot->getSignatureDate(),
            $snapshot->getDebtorIban(),
            $snapshot->getDebtorName(),
            $snapshot->getType(),
            $snapshot->getSequenceType(),
        );
        // @igor-ignore - Fresh value object built here; not a shared service.
        $mandate->debtorBic          = $snapshot->getDebtorBic();
        $mandate->status             = $snapshot->getStatus();
        $mandate->active             = $snapshot->isActive();
        $mandate->expirationDate     = $snapshot->getExpirationDate();
        $mandate->lastCollectionDate = $snapshot->getLastCollectionDate();
        $mandate->revocationDate     = $snapshot->getRevocationDate();
        $mandate->revocationReason   = $snapshot->getRevocationReason();
        $mandate->debtorAddress      = $snapshot->getDebtorAddress();
        $mandate->ownerReference     = $snapshot->getOwnerReference();
        $mandate->evidence           = $snapshot->getEvidence();
        $mandate->amendment          = $snapshot->getAmendment();

        return $mandate;
    }
}
