<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Parser;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use Exception;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * SEPA Credit Transfer XML parser.
 * Parses SEPA XML files to extract information.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class CreditTransferParser
{
    public const SERVICE_NAME = 'nowo_sepa_payment.parser.credit_transfer_parser';

    /**
     * Parses a SEPA Credit Transfer XML file.
     *
     * @param string $xml The XML content
     *
     * @throws InvalidArgumentException If the XML is invalid
     *
     * @return array<string, mixed> Parsed data
     */
    public function parseCreditTransfer(string $xml): array
    {
        $dom = new DOMDocument();
        if (!@$dom->loadXML($xml)) {
            throw new InvalidArgumentException('Invalid XML format');
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('sepa', 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03');

        $data = [];

        // Extract group header (PHPStan: query() returns DOMNodeList|false; use helper to avoid calling item() on false)
        $msgId = $this->getFirstNode($xpath, '//sepa:MsgId');
        if ($msgId instanceof DOMNode) {
            $data['messageId'] = $msgId->nodeValue;
        }

        $creDtTm = $this->getFirstNode($xpath, '//sepa:CreDtTm');
        if ($creDtTm instanceof DOMNode) {
            $data['creationDate'] = $creDtTm->nodeValue;
        }

        $initgPty = $this->getFirstNode($xpath, '//sepa:InitgPty/sepa:Nm');
        if ($initgPty instanceof DOMNode) {
            $data['initiatingPartyName'] = $initgPty->nodeValue;
        }

        $pmtInfId = $this->getFirstNode($xpath, '//sepa:PmtInfId');
        if ($pmtInfId instanceof DOMNode) {
            $data['paymentInfoId'] = $pmtInfId->nodeValue;
        }

        $nbOfTxs = $this->getFirstNode($xpath, '//sepa:NbOfTxs');
        if ($nbOfTxs instanceof DOMNode) {
            $data['numberOfTransactions'] = (int) $nbOfTxs->nodeValue;
        }

        $ctrlSum = $this->getFirstNode($xpath, '//sepa:CtrlSum');
        if ($ctrlSum instanceof DOMNode) {
            $data['controlSum'] = (float) $ctrlSum->nodeValue;
        }

        // Extract transactions (PHPStan: foreach over DOMNodeList|false; only iterate when it is DOMNodeList)
        $transactions = [];
        $txInfNodes   = $xpath->query('//sepa:CdtTrfTxInf');
        if ($txInfNodes instanceof DOMNodeList) {
            foreach ($txInfNodes as $txInf) {
                if (!$txInf instanceof DOMNode) {
                    continue;
                }
                $transaction = [];

                $endToEndId = $this->getFirstNode($xpath, './/sepa:EndToEndId', $txInf);
                if ($endToEndId instanceof DOMNode) {
                    $transaction['endToEndId'] = $endToEndId->nodeValue;
                }

                $instdAmt = $this->getFirstNode($xpath, './/sepa:InstdAmt', $txInf);
                if ($instdAmt instanceof DOMNode) {
                    $transaction['amount']   = (float) $instdAmt->nodeValue;
                    $transaction['currency'] = $instdAmt instanceof DOMElement ? $instdAmt->getAttribute('Ccy') : '';
                }

                $iban = $this->getFirstNode($xpath, './/sepa:IBAN', $txInf);
                if ($iban instanceof DOMNode) {
                    $transaction['iban'] = $iban->nodeValue;
                }

                $name = $this->getFirstNode($xpath, './/sepa:Nm', $txInf);
                if ($name instanceof DOMNode) {
                    $transaction['name'] = $name->nodeValue;
                }

                $rmtInf = $this->getFirstNode($xpath, './/sepa:Ustrd', $txInf);
                if ($rmtInf instanceof DOMNode) {
                    $transaction['remittanceInformation'] = $rmtInf->nodeValue;
                }

                $transactions[] = $transaction;
            }
        }

        $data['transactions'] = $transactions;

        return $data;
    }

    /**
     * Validates that an XML string is a valid SEPA Credit Transfer file.
     *
     * @param string $xml The XML content
     *
     * @return bool True if valid, false otherwise
     */
    public function isValidCreditTransfer(string $xml): bool
    {
        try {
            // Empty string check
            if (trim($xml) === '') {
                return false;
            }

            $dom = new DOMDocument();
            if (!@$dom->loadXML($xml)) {
                return false;
            }

            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('sepa', 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03');

            $msgId            = $this->getFirstNode($xpath, '//sepa:MsgId');
            $cstmrCdtTrfInitn = $this->getFirstNode($xpath, '//sepa:CstmrCdtTrfInitn');

            return $msgId instanceof DOMNode && $cstmrCdtTrfInitn instanceof DOMNode;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Returns the first node from an XPath query.
     * PHPStan: query() returns DOMNodeList|false and item(0) does not exist on false; this helper centralizes the check.
     *
     * @param DOMNode|null $context Context for relative query (optional)
     */
    private function getFirstNode(DOMXPath $xpath, string $expr, ?DOMNode $context = null): ?DOMNode
    {
        $list = $context instanceof DOMNode ? $xpath->query($expr, $context) : $xpath->query($expr);
        if (!$list instanceof DOMNodeList || $list->length === 0) {
            return null;
        }
        $node = $list->item(0);

        return $node instanceof DOMNode ? $node : null;
    }
}
