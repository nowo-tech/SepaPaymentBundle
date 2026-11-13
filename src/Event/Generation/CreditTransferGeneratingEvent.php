<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Event\Generation;

use Nowo\SepaPaymentBundle\Event\BeforeCreditTransferGenerationEvent;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\CreditTransferData;

/**
 * Framework-free twin of {@see BeforeCreditTransferGenerationEvent} (since 2.4.0,
 * REQ-ARCH-001 rule 3): a plain object, no Symfony base class.
 *
 * Dispatched by `CreditTransferGenerator::generate()` right after the legacy event, with the data the legacy listeners
 * left; listeners may replace it. The generator builds the file from {@see self::getCreditTransferData()} afterwards.
 * Request scoped: never keep it after the listener returns (FrankenPHP worker, kernel kept alive).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class CreditTransferGeneratingEvent
{
    public function __construct(
        private CreditTransferData $creditTransferData,
    ) {
    }

    public function getCreditTransferData(): CreditTransferData
    {
        return $this->creditTransferData;
    }

    public function setCreditTransferData(CreditTransferData $creditTransferData): void
    {
        // @igor-ignore - Request-scoped Event DTO; not a shared worker service.
        $this->creditTransferData = $creditTransferData;
    }

    public function getMessageId(): string
    {
        return $this->creditTransferData->getMessageId();
    }
}
