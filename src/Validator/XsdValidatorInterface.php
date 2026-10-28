<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator;

use InvalidArgumentException;

/**
 * Validates SEPA XML (pain.001 / pain.008) against the ISO 20022 XSD schemas.
 *
 * Generators depend on this contract; {@see XsdValidator} is the default implementation (aliased to this interface).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
interface XsdValidatorInterface
{
    /**
     * @param string $schemaType `credit_transfer` or `direct_debit`
     *
     * @throws InvalidArgumentException If the XML is invalid
     */
    public function validate(string $xml, ?string $xsdPath = null, string $schemaType = 'credit_transfer'): bool;

    /**
     * @throws InvalidArgumentException If the XML is invalid
     */
    public function validateCreditTransfer(string $xml, ?string $xsdPath = null): bool;

    /**
     * @throws InvalidArgumentException If the XML is invalid
     */
    public function validateDirectDebit(string $xml, ?string $xsdPath = null): bool;

    /**
     * @throws InvalidArgumentException If the XML is invalid
     */
    public function validateAgainstSchemaString(string $xml, string $xsdContent): bool;
}
