<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Event;

use Nowo\SepaPaymentBundle\Model\CreditTransfer\CreditTransferData;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched before Credit Transfer XML generation.
 * Allows listeners to modify the data before generation.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class BeforeCreditTransferGenerationEvent extends Event
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
        $this->creditTransferData = $creditTransferData;
    }
}
