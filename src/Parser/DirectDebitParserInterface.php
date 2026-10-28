<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Parser;

use InvalidArgumentException;

/**
 * Parses SEPA Direct Debit (pain.008) XML.
 *
 * {@see DirectDebitParser} is the default implementation (aliased to this interface).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
interface DirectDebitParserInterface
{
    /**
     * @throws InvalidArgumentException If the XML is invalid
     *
     * @return array<string, mixed> Parsed data
     */
    public function parseDirectDebit(string $xml): array;

    public function isValidDirectDebit(string $xml): bool;
}
