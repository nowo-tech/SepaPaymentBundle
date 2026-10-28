<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Generator;

use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Validator\SepaStringSanitizer;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

use function sprintf;

/**
 * Identifier generator for SEPA operations.
 * Generates unique identifiers for messages, payments, and transactions.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
final class IdentifierGenerator
{
    public const SERVICE_NAME = 'nowo_sepa_payment.generator.identifier_generator';

    /**
     * Length of the generated suffix: "-" + YmdHis (14) + "-" + 8 hex characters.
     */
    private const SUFFIX_LENGTH = 24;

    /**
     * Maximum prefix length so that the identifier fits the SEPA Max35Text limit (35 - 24 = 11).
     */
    public const MAX_PREFIX_LENGTH = SepaStringSanitizer::MAX_IDENTIFIER_LENGTH - self::SUFFIX_LENGTH;

    /**
     * Generates a unique message identifier.
     * Format: MSG-{timestamp}-{random}.
     *
     * @param string|null $prefix Optional prefix (default: 'MSG')
     *
     * @return string The message identifier
     */
    public function generateMessageId(?string $prefix = null): string
    {
        $prefix ??= 'MSG';

        return $this->build($prefix);
    }

    /**
     * Generates a unique payment information identifier.
     * Format: PMT-{timestamp}-{random}.
     *
     * @param string|null $prefix Optional prefix (default: 'PMT')
     *
     * @return string The payment information identifier
     */
    public function generatePaymentInfoId(?string $prefix = null): string
    {
        $prefix ??= 'PMT';

        return $this->build($prefix);
    }

    /**
     * Generates a unique end-to-end identifier.
     * Format: E2E-{timestamp}-{random}.
     *
     * @param string|null $prefix Optional prefix (default: 'E2E')
     *
     * @return string The end-to-end identifier
     */
    public function generateEndToEndId(?string $prefix = null): string
    {
        $prefix ??= 'E2E';

        return $this->build($prefix);
    }

    /**
     * Generates a unique mandate identifier.
     * Format: MANDATE-{timestamp}-{random}.
     *
     * @param string|null $prefix Optional prefix (default: 'MANDATE')
     *
     * @return string The mandate identifier
     */
    public function generateMandateId(?string $prefix = null): string
    {
        $prefix ??= 'MANDATE';

        return $this->build($prefix);
    }

    /**
     * Generates a custom identifier with a prefix.
     *
     * @param string $prefix The prefix for the identifier (at most {@see MAX_PREFIX_LENGTH} characters)
     *
     * @throws InvalidArgumentException If the prefix would make the identifier exceed 35 characters
     *
     * @return string The generated identifier
     */
    public function generateCustomId(string $prefix): string
    {
        return $this->build($prefix);
    }

    /**
     * Builds an identifier "{prefix}-{YmdHis}-{8 hex}" and enforces the SEPA 35-character limit.
     *
     * @param string $prefix The identifier prefix
     *
     * @throws InvalidArgumentException If the prefix is longer than {@see MAX_PREFIX_LENGTH} characters
     *
     * @return string The identifier
     */
    private function build(string $prefix): string
    {
        if (mb_strlen($prefix) > self::MAX_PREFIX_LENGTH) {
            throw new InvalidArgumentException(sprintf('Identifier prefix "%s" is too long: at most %d characters are allowed so the identifier fits the SEPA limit of %d characters.', $prefix, self::MAX_PREFIX_LENGTH, SepaStringSanitizer::MAX_IDENTIFIER_LENGTH));
        }

        return sprintf('%s-%s-%s', $prefix, date('YmdHis'), bin2hex(random_bytes(4)));
    }
}
