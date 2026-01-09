<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Parser;

use Deprecated;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * SEPA Credit Transfer XML parser (deprecated).
 *
 * @deprecated Since 1.1.0, use CreditTransferParser instead. This class will be removed in 2.0.0.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class RemesaParser
{
    public const SERVICE_NAME = 'nowo_sepa_payment.parser.remesa_parser';

    /**
     * Credit transfer parser instance.
     *
     * @var CreditTransferParser
     */
    private CreditTransferParser $parser;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->parser = new CreditTransferParser();
    }

    /**
     * Parses a SEPA Credit Transfer XML file.
     *
     * @deprecated Since 1.1.0, use CreditTransferParser::parseCreditTransfer() instead
     *
     * @param string $xml The XML content
     *
     * @throws \InvalidArgumentException If the XML is invalid
     *
     * @return array<string, mixed> Parsed data
     */
    #[Deprecated(message: 'Use CreditTransferParser::parseCreditTransfer() instead', since: '1.1.0')]
    public function parseCreditTransfer(string $xml): array
    {
        @trigger_error('RemesaParser is deprecated since 1.1.0. Use CreditTransferParser instead.', \E_USER_DEPRECATED);

        return $this->parser->parseCreditTransfer($xml);
    }

    /**
     * Validates that an XML string is a valid SEPA Credit Transfer file.
     *
     * @deprecated Since 1.1.0, use CreditTransferParser::isValidCreditTransfer() instead
     *
     * @param string $xml The XML content
     *
     * @return bool True if valid, false otherwise
     */
    #[Deprecated(message: 'Use CreditTransferParser::isValidCreditTransfer() instead', since: '1.1.0')]
    public function isValidCreditTransfer(string $xml): bool
    {
        @trigger_error('RemesaParser is deprecated since 1.1.0. Use CreditTransferParser instead.', \E_USER_DEPRECATED);

        return $this->parser->isValidCreditTransfer($xml);
    }
}
