<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Generator;

use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Http\SepaXmlResponseFactory;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\CreditTransferData;

/**
 * Port: writes a SEPA credit transfer initiation (pain.001) as an XML string. No HTTP types: wrap the string in a
 * response with {@see SepaXmlResponseFactory} or store it.
 * {@see CreditTransferGenerator} is the default implementation (aliased to this interface).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
interface CreditTransferGeneratorInterface
{
    /**
     * Generates the XML of a credit transfer file.
     *
     * @throws InvalidArgumentException If the data is invalid
     */
    public function generate(CreditTransferData $creditTransferData): string;

    /**
     * Generates the XML of a credit transfer file from array data (camelCase or snake_case keys).
     *
     * @param array<string, mixed> $data The credit transfer data
     *
     * @throws InvalidArgumentException If the data is invalid
     */
    public function generateFromArray(array $data): string;
}
