<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Parser;

use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Model\Report\PaymentStatusReport;

/**
 * Port: parses a customer payment status report (pain.002.001.03 or .10) into value objects.
 * {@see PaymentStatusReportParser} is the default implementation (aliased to this interface).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
interface PaymentStatusReportParserInterface
{
    /**
     * Parses and validates a pain.002 document.
     *
     * @throws InvalidArgumentException If the document is too large, malformed, of another type or invalid against its XSD
     */
    public function parse(string $xml): PaymentStatusReport;
}
