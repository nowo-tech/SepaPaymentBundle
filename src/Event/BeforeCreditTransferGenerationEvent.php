<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Event;

use Nowo\SepaPaymentBundle\Model\CreditTransfer\CreditTransferData;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched before Credit Transfer XML generation.
 * Allows listeners to modify the data before generation.
 *
 * Still dispatched exactly as before; the framework-free twin {@see Generation\CreditTransferGeneratingEvent} follows it.
 *
 * @deprecated since 2.4.0, removed in 3.0: listen to {@see Generation\CreditTransferGeneratingEvent} (no Symfony base class)
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class BeforeCreditTransferGenerationEvent extends Event
{
    /**
     * Constructor.
     *
     * @param CreditTransferData $creditTransferData The credit transfer data
     */
    public function __construct(
        /**
         * Credit transfer data (can be modified by listeners).
         */
        private CreditTransferData $creditTransferData
    ) {
    }

    /**
     * Gets the credit transfer data.
     *
     * @return CreditTransferData The credit transfer data
     */
    public function getCreditTransferData(): CreditTransferData
    {
        return $this->creditTransferData;
    }

    /**
     * Sets the credit transfer data.
     *
     * @param CreditTransferData $creditTransferData The credit transfer data
     */
    public function setCreditTransferData(CreditTransferData $creditTransferData): void
    {
        // @igor-ignore - Request-scoped Event DTO; not a shared worker service.
        $this->creditTransferData = $creditTransferData;
    }

    /**
     * Gets the message id of the file (since 2.2).
     */
    public function getMessageId(): string
    {
        return $this->getCreditTransferData()->getMessageId();
    }
}
