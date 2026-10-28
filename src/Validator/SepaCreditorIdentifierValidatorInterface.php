<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator;

/**
 * Sepa Creditor Identifier validation contract. {@see SepaCreditorIdentifierValidator} is the default implementation (aliased to this interface); constraint
 * validators depend on this interface.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
interface SepaCreditorIdentifierValidatorInterface
{
    /**
     * Validates a SEPA Creditor Identifier.
     *
     * @param string $creditorId The SEPA Creditor Identifier to validate
     *
     * @return bool True if valid, false otherwise
     */
    public function isValid(string $creditorId): bool;

    /**
     * Normalizes a SEPA Creditor Identifier by removing spaces and converting to uppercase.
     *
     * @param string $creditorId The identifier to normalize
     *
     * @return string The normalized identifier
     */
    public function normalize(string $creditorId): string;

    /**
     * Extracts the country code from a SEPA Creditor Identifier.
     *
     * @param string $creditorId The identifier
     *
     * @return string The country code (2 letters)
     */
    public function getCountryCode(string $creditorId): string;

    /**
     * Extracts the national identifier from a SEPA Creditor Identifier.
     *
     * @param string $creditorId The identifier
     *
     * @return string The national identifier
     */
    public function getNationalIdentifier(string $creditorId): string;

    /**
     * Validates Spanish NIF/CIF format (for use in Spanish SEPA Creditor Identifiers).
     *
     * Spanish NIF format: 1 letter + 8 digits (e.g., "M12345678")
     * Spanish CIF format: 1 letter + 7 digits + 1 letter/digit (e.g., "A12345674")
     *
     * This method validates basic format. Full NIF/CIF validation with check digit
     * would require additional logic specific to Spanish tax identifiers.
     *
     * @param string $nif The NIF/CIF to validate
     *
     * @return bool True if format is valid, false otherwise
     */
    public function isValidSpanishNifFormat(string $nif): bool;
}
