<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Generator;

use Nowo\SepaPaymentBundle\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Identifier generator for SEPA operations.
 * Generates unique identifiers for messages, payments, and transactions.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class IdentifierGenerator
{
    public const string SERVICE_NAME = Configuration::ALIAS. '.generator.identifier_generator';

    /**
     * Generates a unique message identifier.
     * Format: MSG-{timestamp}-{random}
     *
     * @param string|null $prefix Optional prefix (default: 'MSG')
     *
     * @return string The message identifier
     */
    public function generateMessageId(?string $prefix = null): string
    {
        $prefix ??= 'MSG';

        return sprintf('%s-%s-%s', $prefix, date('YmdHis'), bin2hex(random_bytes(4)));
    }

    /**
     * Generates a unique payment information identifier.
     * Format: PMT-{timestamp}-{random}
     *
     * @param string|null $prefix Optional prefix (default: 'PMT')
     *
     * @return string The payment information identifier
     */
    public function generatePaymentInfoId(?string $prefix = null): string
    {
        $prefix ??= 'PMT';

        return sprintf('%s-%s-%s', $prefix, date('YmdHis'), bin2hex(random_bytes(4)));
    }

    /**
     * Generates a unique end-to-end identifier.
     * Format: E2E-{timestamp}-{random}
     *
     * @param string|null $prefix Optional prefix (default: 'E2E')
     *
     * @return string The end-to-end identifier
     */
    public function generateEndToEndId(?string $prefix = null): string
    {
        $prefix ??= 'E2E';

        return sprintf('%s-%s-%s', $prefix, date('YmdHis'), bin2hex(random_bytes(4)));
    }

    /**
     * Generates a unique mandate identifier.
     * Format: MANDATE-{timestamp}-{random}
     *
     * @param string|null $prefix Optional prefix (default: 'MANDATE')
     *
     * @return string The mandate identifier
     */
    public function generateMandateId(?string $prefix = null): string
    {
        $prefix ??= 'MANDATE';

        return sprintf('%s-%s-%s', $prefix, date('YmdHis'), bin2hex(random_bytes(4)));
    }

    /**
     * Generates a custom identifier with a prefix.
     *
     * @param string $prefix The prefix for the identifier
     *
     * @return string The generated identifier
     */
    public function generateCustomId(string $prefix): string
    {
        return sprintf('%s-%s-%s', $prefix, date('YmdHis'), bin2hex(random_bytes(4)));
    }
}
