<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Event\Generation;

use Nowo\SepaPaymentBundle\Event\AfterCreditTransferGenerationEvent;

/**
 * Framework-free twin of {@see AfterCreditTransferGenerationEvent} (since 2.4.0,
 * REQ-ARCH-001 rule 3): a plain object, no Symfony base class.
 *
 * Dispatched by `CreditTransferGenerator::generate()` right after the legacy event, with the XML the legacy listeners
 * left; listeners may replace it, and `generate()` returns {@see self::getXml()}. Request scoped (FrankenPHP worker,
 * kernel kept alive).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class CreditTransferGeneratedEvent
{
    public function __construct(
        private string $xml,
        private readonly string $messageId,
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
}
