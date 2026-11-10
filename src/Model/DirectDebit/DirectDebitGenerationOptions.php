<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\DirectDebit;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Per-call options of the direct debit generator. Every null option falls back to the bundle configuration
 * (`nowo_sepa_payment.direct_debit.*`), so `new DirectDebitGenerationOptions()` changes nothing.
 *
 *     $generator->generate($data, new DirectDebitGenerationOptions(
 *         painVersion: Pain008Version::V08,
 *         sequenceTypeMode: SequenceTypeMode::OMIT,
 *     ));
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class DirectDebitGenerationOptions
{
    /**
     * The message version, or null for the configured default.
     */
    private readonly ?Pain008Version $painVersion;

    /**
     * The sequence type mode, or null for the configured default.
     */
    private readonly ?SequenceTypeMode $sequenceTypeMode;

    /**
     * The creation date and time, or null for "now".
     */
    private readonly ?DateTimeImmutable $creationDateTime;

    /**
     * Constructor.
     *
     * @param Pain008Version|string|null $painVersion Message version ("pain.008.001.02", ".08" or ".09"); null: config default
     * @param SequenceTypeMode|string|null $sequenceTypeMode "per_transaction", "always_rcur" or "omit"; null: config default
     * @param bool|null $validateXsd Validate the generated XML against the version's XSD; null: config default
     *                               (needs an XSD validator, which the bundle wires by default)
     * @param DateTimeInterface|null $creationDateTime Creation date and time (`GrpHdr/CreDtTm`); null: now
     *
     * @throws InvalidArgumentException If the version or the mode is not supported
     */
    public function __construct(
        Pain008Version|string|null $painVersion = null,
        SequenceTypeMode|string|null $sequenceTypeMode = null,
        private readonly ?bool $validateXsd = null,
        ?DateTimeInterface $creationDateTime = null,
    ) {
        $this->painVersion      = $painVersion === null ? null : Pain008Version::fromValue($painVersion);
        $this->sequenceTypeMode = $sequenceTypeMode === null ? null : SequenceTypeMode::fromValue($sequenceTypeMode);
        $this->creationDateTime = $creationDateTime instanceof DateTimeInterface ? DateTimeImmutable::createFromInterface($creationDateTime) : null;
    }

    /**
     * Gets the message version (null: config default).
     */
    public function getPainVersion(): ?Pain008Version
    {
        return $this->painVersion;
    }

    /**
     * Gets the sequence type mode (null: config default).
     */
    public function getSequenceTypeMode(): ?SequenceTypeMode
    {
        return $this->sequenceTypeMode;
    }

    /**
     * Whether to validate against the XSD (null: config default).
     */
    public function getValidateXsd(): ?bool
    {
        return $this->validateXsd;
    }

    /**
     * Gets the creation date and time (null: now).
     */
    public function getCreationDateTime(): ?DateTimeImmutable
    {
        return $this->creationDateTime;
    }
}
