<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Generator;

use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Http\SepaXmlResponseFactory;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitData;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitGenerationOptions;

/**
 * Port: writes a SEPA direct debit initiation (pain.008) as an XML string. No HTTP types: wrap the string in a
 * response with {@see SepaXmlResponseFactory} (standard Symfony controllers) or store it
 * (DDD / hexagonal hosts). {@see DirectDebitGenerator} is the default implementation (aliased to this interface).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
interface DirectDebitGeneratorInterface
{
    /**
     * Generates the XML of a direct debit file.
     *
     * @param DirectDebitData $directDebitData The file (one or more payment-information blocks)
     * @param DirectDebitGenerationOptions|null $options Per-call options (version, sequence type mode, XSD, creation date);
     *                                                   null options fall back to the bundle configuration
     *
     * @throws InvalidArgumentException If the data is invalid
     *
     * @return string The XML content
     */
    public function generate(DirectDebitData $directDebitData, ?DirectDebitGenerationOptions $options = null): string;

    /**
     * Generates the XML of a direct debit file from array data (camelCase or snake_case keys, single block).
     *
     * @param array<string, mixed> $data The direct debit data
     * @param DirectDebitGenerationOptions|null $options Per-call options
     *
     * @throws InvalidArgumentException If the data is invalid
     *
     * @return string The XML content
     */
    public function generateFromArray(array $data, ?DirectDebitGenerationOptions $options = null): string;
}
