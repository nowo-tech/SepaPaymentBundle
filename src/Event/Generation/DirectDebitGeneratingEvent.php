<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Event\Generation;

use Nowo\SepaPaymentBundle\Event\BeforeDirectDebitGenerationEvent;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitData;

/**
 * Framework-free twin of {@see BeforeDirectDebitGenerationEvent} (since 2.4.0,
 * REQ-ARCH-001 rule 3): a plain object, no Symfony base class.
 *
 * Dispatched by `DirectDebitGenerator::generate()` right after the legacy event, with the data the legacy listeners
 * left; listeners may replace it. The generator builds the file from {@see self::getDirectDebitData()} afterwards.
 * Request scoped: never keep it after the listener returns (FrankenPHP worker, kernel kept alive).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class DirectDebitGeneratingEvent
{
    public function __construct(
        private DirectDebitData $directDebitData,
    ) {
    }

    public function getDirectDebitData(): DirectDebitData
    {
        return $this->directDebitData;
    }

    public function setDirectDebitData(DirectDebitData $directDebitData): void
    {
        // @igor-ignore - Request-scoped Event DTO; not a shared worker service.
        $this->directDebitData = $directDebitData;
    }

    public function getMessageId(): string
    {
        return $this->directDebitData->getMessageId();
    }
}
