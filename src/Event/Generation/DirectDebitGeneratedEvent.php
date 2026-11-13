<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Event\Generation;

use Nowo\SepaPaymentBundle\Event\AfterDirectDebitGenerationEvent;

/**
 * Framework-free twin of {@see AfterDirectDebitGenerationEvent} (since 2.4.0,
 * REQ-ARCH-001 rule 3): a plain object with ids and plain values, no Symfony base class.
 *
 * Dispatched by `DirectDebitGenerator::generate()` right after the legacy event, with the XML the legacy listeners
 * left; listeners may replace it, and `generate()` returns {@see self::getXml()}. The bundle never persists anything:
 * the event fires before the host stores or sends the file. Request scoped (FrankenPHP worker, kernel kept alive).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class DirectDebitGeneratedEvent
{
    /**
     * @param list<string> $paymentInformationIds The PmtInfId of every written block, in file order
     */
    public function __construct(
        private string $xml,
        private readonly string $messageId,
        private readonly array $paymentInformationIds,
        private readonly ?string $painVersion,
        private readonly int $numberOfTransactions,
        private readonly int $controlSumCents,
    ) {
    }

    public function getXml(): string
    {
        return $this->xml;
    }

    public function setXml(string $xml): void
    {
        // @igor-ignore - Request-scoped Event DTO; not a shared worker service.
        $this->xml = $xml;
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    /**
     * @return list<string>
     */
    public function getPaymentInformationIds(): array
    {
        return $this->paymentInformationIds;
    }

    public function getPainVersion(): ?string
    {
        return $this->painVersion;
    }

    public function getNumberOfTransactions(): int
    {
        return $this->numberOfTransactions;
    }

    public function getControlSumCents(): int
    {
        return $this->controlSumCents;
    }
}
