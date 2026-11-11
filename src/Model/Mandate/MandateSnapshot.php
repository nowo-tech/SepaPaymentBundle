<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\Mandate;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

use function is_array;
use function is_scalar;
use function sprintf;
use function trim;

/**
 * Immutable snapshot of every field of a {@see Mandate} (DDD path, since 2.3). A host repository stores it in its own
 * table (Doctrine XML mapping, columns, a JSON document…) and rebuilds the mandate with {@see Mandate::reconstitute()};
 * the bundle needs no entity and no Doctrine. {@see self::toArray()} / {@see self::fromArray()} give a plain,
 * JSON-safe form.
 *
 * Fields to map: mandateId (string 35, the id), signatureDate (date), debtorIban (string 34), debtorName (string 70),
 * type (CORE|B2B), sequenceType (FRST|RCUR|OOFF|FNAL), debtorBic (?string 11), status (MandateStatus value), active
 * (bool), expirationDate, lastCollectionDate, revocationDate (?datetime), revocationReason (?string), debtorAddress
 * (?json), ownerReference (?string 255), evidence (?json), amendment (?json).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final readonly class MandateSnapshot
{
    /**
     * Constructor.
     *
     * @param array{street: ?string, city: ?string, postalCode: ?string, country: ?string}|null $debtorAddress
     */
    public function __construct(
        private string $mandateId,
        private DateTimeImmutable $signatureDate,
        private string $debtorIban,
        private string $debtorName,
        private string $type,
        private string $sequenceType,
        private ?string $debtorBic,
        private MandateStatus $status,
        private bool $active,
        private ?DateTimeImmutable $expirationDate,
        private ?DateTimeImmutable $lastCollectionDate,
        private ?DateTimeImmutable $revocationDate,
        private ?string $revocationReason,
        private ?array $debtorAddress = null,
        private ?string $ownerReference = null,
        private ?MandateEvidence $evidence = null,
        private ?MandateAmendment $amendment = null,
    ) {
    }

    public function getMandateId(): string
    {
        return $this->mandateId;
    }

    public function getSignatureDate(): DateTimeImmutable
    {
        return $this->signatureDate;
    }

    public function getDebtorIban(): string
    {
        return $this->debtorIban;
    }

    public function getDebtorName(): string
    {
        return $this->debtorName;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSequenceType(): string
    {
        return $this->sequenceType;
    }

    public function getDebtorBic(): ?string
    {
        return $this->debtorBic;
    }

    public function getStatus(): MandateStatus
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getExpirationDate(): ?DateTimeImmutable
    {
        return $this->expirationDate;
    }

    public function getLastCollectionDate(): ?DateTimeImmutable
    {
        return $this->lastCollectionDate;
    }

    public function getRevocationDate(): ?DateTimeImmutable
    {
        return $this->revocationDate;
    }

    public function getRevocationReason(): ?string
    {
        return $this->revocationReason;
    }

    /**
     * @return array{street: ?string, city: ?string, postalCode: ?string, country: ?string}|null
     */
    public function getDebtorAddress(): ?array
    {
        return $this->debtorAddress;
    }

    public function getOwnerReference(): ?string
    {
        return $this->ownerReference;
    }

    public function getEvidence(): ?MandateEvidence
    {
        return $this->evidence;
    }

    public function getAmendment(): ?MandateAmendment
    {
        return $this->amendment;
    }

    /**
     * Plain, JSON-safe array (dates in ATOM format); see {@see self::fromArray()}.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $date = static fn (?DateTimeImmutable $value): ?string => $value?->format(DateTimeInterface::ATOM);

        return [
            'mandateId'          => $this->mandateId,
            'signatureDate'      => $date($this->signatureDate),
            'debtorIban'         => $this->debtorIban,
            'debtorName'         => $this->debtorName,
            'type'               => $this->type,
            'sequenceType'       => $this->sequenceType,
            'debtorBic'          => $this->debtorBic,
            'status'             => $this->status->value,
            'active'             => $this->active,
            'expirationDate'     => $date($this->expirationDate),
            'lastCollectionDate' => $date($this->lastCollectionDate),
            'revocationDate'     => $date($this->revocationDate),
            'revocationReason'   => $this->revocationReason,
            'debtorAddress'      => $this->debtorAddress,
            'ownerReference'     => $this->ownerReference,
            'evidence'           => $this->evidence?->toArray(),
            'amendment'          => $this->amendment?->toArray(),
        ];
    }

    /**
     * Rebuilds a snapshot from {@see self::toArray()}. Dates must be absolute (ATOM, "Y-m-d H:i:s" or "Y-m-d"): a
     * missing, empty or relative date ("now", "tomorrow", "@0") throws instead of becoming the current time.
     *
     * @param array<string, mixed> $data
     *
     * @throws InvalidArgumentException If mandateId, signatureDate, debtorIban or debtorName is missing or empty, or a
     *                                  date is not absolute
     */
    public static function fromArray(array $data): self
    {
        $date = static fn (string $field): ?DateTimeImmutable => SnapshotDate::optional($data[$field] ?? null, 'mandate snapshot ' . $field);

        return new self(
            self::requiredString($data, 'mandateId'),
            SnapshotDate::required($data['signatureDate'] ?? null, 'mandate snapshot signatureDate'),
            self::requiredString($data, 'debtorIban'),
            self::requiredString($data, 'debtorName'),
            (string) ($data['type'] ?? 'CORE'),
            (string) ($data['sequenceType'] ?? 'FRST'),
            isset($data['debtorBic']) ? (string) $data['debtorBic'] : null,
            MandateStatus::from((string) ($data['status'] ?? MandateStatus::ACTIVE->value)),
            (bool) ($data['active'] ?? true),
            $date('expirationDate'),
            $date('lastCollectionDate'),
            $date('revocationDate'),
            isset($data['revocationReason']) ? (string) $data['revocationReason'] : null,
            self::address($data['debtorAddress'] ?? null),
            isset($data['ownerReference']) ? (string) $data['ownerReference'] : null,
            is_array($data['evidence'] ?? null) ? MandateEvidence::fromArray([
                'acceptedTextHash' => self::nullableString($data['evidence']['acceptedTextHash'] ?? null) ?? '',
                'acceptedAt'       => self::nullableString($data['evidence']['acceptedAt'] ?? null),
                'ipAddress'        => self::nullableString($data['evidence']['ipAddress'] ?? null),
                'channel'          => self::nullableString($data['evidence']['channel'] ?? null),
                'externalId'       => self::nullableString($data['evidence']['externalId'] ?? null),
            ]) : null,
            is_array($data['amendment'] ?? null) ? MandateAmendment::fromArray($data['amendment']) : null,
        );
    }

    /**
     * @return array{street: ?string, city: ?string, postalCode: ?string, country: ?string}|null
     */
    private static function address(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        return [
            'street'     => self::nullableString($value['street'] ?? null),
            'city'       => self::nullableString($value['city'] ?? null),
            'postalCode' => self::nullableString($value['postalCode'] ?? null),
            'country'    => self::nullableString($value['country'] ?? null),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function requiredString(array $data, string $field): string
    {
        $value = self::nullableString($data[$field] ?? null);
        if ($value === null || trim($value) === '') {
            throw new InvalidArgumentException(sprintf('Invalid mandate snapshot: %s is missing or empty.', $field));
        }

        return $value;
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
