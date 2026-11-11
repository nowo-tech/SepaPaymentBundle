<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Parser;

use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Model\Report\DebitCreditNotification;

/**
 * Port: parses a bank-to-customer debit / credit notification (camt.054.001.02 or .08) into value objects.
 * {@see DebitCreditNotificationParser} is the default implementation (aliased to this interface).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
interface DebitCreditNotificationParserInterface
{
    /**
     * Parses and validates a camt.054 document.
     *
     * @throws InvalidArgumentException If the document is too large, malformed, of another type or invalid against its XSD
     */
    public function parse(string $xml): DebitCreditNotification;
}
