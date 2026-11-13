<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Event;

use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitData;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched before Direct Debit XML generation.
 * Allows listeners to modify the data before generation.
 *
 * Still dispatched exactly as before; the framework-free twin {@see Generation\DirectDebitGeneratingEvent} follows it.
 *
 * @deprecated since 2.4.0, removed in 3.0: listen to {@see Generation\DirectDebitGeneratingEvent} (no Symfony base class)
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class BeforeDirectDebitGenerationEvent extends Event
{
    /**
     * Constructor.
     *
     * @param DirectDebitData $directDebitData The direct debit data
     */
    public function __construct(
        /**
         * Direct debit data (can be modified by listeners).
         */
        private DirectDebitData $directDebitData
    ) {
    }

    /**
     * Gets the direct debit data.
     *
     * @return DirectDebitData The direct debit data
     */
    public function getDirectDebitData(): DirectDebitData
    {
        return $this->directDebitData;
    }

    /**
     * Sets the direct debit data.
     *
     * @param DirectDebitData $directDebitData The direct debit data
     */
    public function setDirectDebitData(DirectDebitData $directDebitData): void
    {
        // @igor-ignore - Request-scoped Event DTO; not a shared worker service.
        $this->directDebitData = $directDebitData;
    }

    /**
     * Gets the message id of the file (since 2.2).
     */
    public function getMessageId(): string
    {
        return $this->getDirectDebitData()->getMessageId();
    }
}
