<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\Mandate;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

use function filter_var;
use function mb_strlen;
use function preg_match;
use function sprintf;

use const FILTER_VALIDATE_IP;

/**
 * Optional evidence of how the debtor accepted a mandate (e-mandate or online sign-up): the hash of the text the debtor
 * accepted, when, from which IP address and channel, and the host's own reference to the signing event. Plain values
 * only; the host keeps the accepted text and the full audit trail.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final readonly class MandateEvidence
{
    /** Longest channel or external id. */
    public const MAX_TEXT_LENGTH = 100;

    private DateTimeImmutable $acceptedAt;

    /**
     * Constructor.
     *
     * @param string $acceptedTextHash Hash of the mandate text the debtor accepted, hexadecimal (e.g. SHA-256, 64 characters; 32 to 128 accepted)
     * @param DateTimeInterface $acceptedAt When the debtor accepted it
     * @param string|null $ipAddress The debtor's IP address (IPv4 or IPv6)
     * @param string|null $channel How it was accepted, e.g. "web", "app", "paper", "phone" (1 to 100 characters)
     * @param string|null $externalId The host's reference of the signing event (1 to 100 characters)
     *
     * @throws InvalidArgumentException If a value is malformed
     */
    public function __construct(
        private string $acceptedTextHash,
        DateTimeInterface $acceptedAt,
        private ?string $ipAddress = null,
        private ?string $channel = null,
        private ?string $externalId = null,
    ) {
        if (preg_match('/^[0-9a-f]{32,128}$/D', $acceptedTextHash) !== 1) {
            throw new InvalidArgumentException('Invalid accepted text hash: use a lowercase hexadecimal digest of 32 to 128 characters (e.g. hash(\'sha256\', $text)).');
        }
        if ($ipAddress !== null && filter_var($ipAddress, FILTER_VALIDATE_IP) === false) {
            throw new InvalidArgumentException(sprintf('Invalid IP address "%s".', $ipAddress));
        }
        foreach (['channel' => $channel, 'external id' => $externalId] as $field => $value) {
            if ($value !== null && (preg_match('//u', $value) !== 1 || $value === '' || mb_strlen($value, 'UTF-8') > self::MAX_TEXT_LENGTH)) {
                throw new InvalidArgumentException(sprintf('Invalid mandate evidence %s: use 1 to %d characters of valid UTF-8.', $field, self::MAX_TEXT_LENGTH));
            }
        }
        $this->acceptedAt = DateTimeImmutable::createFromInterface($acceptedAt);
    }

    public function getAcceptedTextHash(): string
    {
        return $this->acceptedTextHash;
    }

    public function getAcceptedAt(): DateTimeImmutable
    {
        return $this->acceptedAt;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    /**
     * Plain array for storage (JSON column, document store); see {@see self::fromArray()}.
     *
     * @return array{acceptedTextHash: string, acceptedAt: string, ipAddress: ?string, channel: ?string, externalId: ?string}
     */
    public function toArray(): array
    {
        return [
            'acceptedTextHash' => $this->acceptedTextHash,
            'acceptedAt'       => $this->acceptedAt->format(DateTimeInterface::ATOM),
            'ipAddress'        => $this->ipAddress,
            'channel'          => $this->channel,
            'externalId'       => $this->externalId,
        ];
    }

    /**
     * Rebuilds the evidence from {@see self::toArray()}.
     *
     * @param array{acceptedTextHash: string, acceptedAt?: ?string, ipAddress?: ?string, channel?: ?string, externalId?: ?string} $data
     *
     * @throws InvalidArgumentException If acceptedAt is missing, empty or not an absolute date, or another value is malformed
     */
    public static function fromArray(array $data): self
    {
        // Strict: "", "now", "tomorrow" or "@0" never become a date (LF-L1)
        $acceptedAt = SnapshotDate::required($data['acceptedAt'] ?? null, 'mandate evidence acceptedAt');

        return new self($data['acceptedTextHash'], $acceptedAt, $data['ipAddress'] ?? null, $data['channel'] ?? null, $data['externalId'] ?? null);
    }
}
