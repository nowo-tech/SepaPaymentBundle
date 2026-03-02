<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Event;

use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitData;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched before Direct Debit XML generation.
 * Allows listeners to modify the data before generation.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class BeforeDirectDebitGenerationEvent extends Event
{
    /**
     * Direct debit data (can be modified by listeners).
     */
    private DirectDebitData $directDebitData;

    /**
     * Constructor.
     *
     * @param DirectDebitData $directDebitData The direct debit data
     */
    public function __construct(DirectDebitData $directDebitData)
    {
        $this->directDebitData = $directDebitData;
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
        $this->directDebitData = $directDebitData;
    }
}
