<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Parser;

use InvalidArgumentException;

/**
 * Port: parses a SEPA credit transfer initiation (pain.001). {@see CreditTransferParser} is the default
 * implementation (aliased to this interface).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
interface CreditTransferParserInterface
{
    /**
     * Parses a credit transfer XML document.
     *
     * @param string $xml The XML content
     *
     * @throws InvalidArgumentException If the XML is invalid
     *
     * @return array<string, mixed> The parsed data
     */
    public function parseCreditTransfer(string $xml): array;

    /**
     * Whether the XML is a parseable credit transfer document.
     *
     * @param string $xml The XML content
     */
    public function isValidCreditTransfer(string $xml): bool;
}
