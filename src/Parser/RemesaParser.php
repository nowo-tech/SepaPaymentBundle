<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Parser;

/**
 * SEPA remesa XML parser.
 * Parses SEPA XML files to extract information.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class RemesaParser
{
    /**
     * Parses a SEPA Credit Transfer XML file.
     *
     * @param string $xml The XML content
     *
     * @throws \InvalidArgumentException If the XML is invalid
     *
     * @return array<string, mixed> Parsed data
     */
    public function parseCreditTransfer(string $xml): array
    {
        $dom = new \DOMDocument();
        if (!@$dom->loadXML($xml)) {
            throw new \InvalidArgumentException('Invalid XML format');
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('sepa', 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03');

        $data = [];

        // Extract group header
        $msgId = $xpath->query('//sepa:MsgId')->item(0);
        if ($msgId) {
            $data['messageId'] = $msgId->nodeValue;
        }

        $creDtTm = $xpath->query('//sepa:CreDtTm')->item(0);
        if ($creDtTm) {
            $data['creationDate'] = $creDtTm->nodeValue;
        }

        $initgPty = $xpath->query('//sepa:InitgPty/sepa:Nm')->item(0);
        if ($initgPty) {
            $data['initiatingPartyName'] = $initgPty->nodeValue;
        }

        // Extract payment information
        $pmtInfId = $xpath->query('//sepa:PmtInfId')->item(0);
        if ($pmtInfId) {
            $data['paymentInfoId'] = $pmtInfId->nodeValue;
        }

        $nbOfTxs = $xpath->query('//sepa:NbOfTxs')->item(0);
        if ($nbOfTxs) {
            $data['numberOfTransactions'] = (int) $nbOfTxs->nodeValue;
        }

        $ctrlSum = $xpath->query('//sepa:CtrlSum')->item(0);
        if ($ctrlSum) {
            $data['controlSum'] = (float) $ctrlSum->nodeValue;
        }

        // Extract transactions
        $transactions = [];
        $txInfNodes = $xpath->query('//sepa:CdtTrfTxInf');
        foreach ($txInfNodes as $txInf) {
            $transaction = [];

            $endToEndId = $xpath->query('.//sepa:EndToEndId', $txInf)->item(0);
            if ($endToEndId) {
                $transaction['endToEndId'] = $endToEndId->nodeValue;
            }

            $instdAmt = $xpath->query('.//sepa:InstdAmt', $txInf)->item(0);
            if ($instdAmt) {
                $transaction['amount'] = (float) $instdAmt->nodeValue;
                $transaction['currency'] = $instdAmt->getAttribute('Ccy');
            }

            $iban = $xpath->query('.//sepa:IBAN', $txInf)->item(0);
            if ($iban) {
                $transaction['iban'] = $iban->nodeValue;
            }

            $name = $xpath->query('.//sepa:Nm', $txInf)->item(0);
            if ($name) {
                $transaction['name'] = $name->nodeValue;
            }

            $rmtInf = $xpath->query('.//sepa:Ustrd', $txInf)->item(0);
            if ($rmtInf) {
                $transaction['remittanceInformation'] = $rmtInf->nodeValue;
            }

            $transactions[] = $transaction;
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
            $dom = new \DOMDocument();
            if (!@$dom->loadXML($xml)) {
                return false;
            }

            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('sepa', 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03');

            // Check for required elements
            $msgId = $xpath->query('//sepa:MsgId')->item(0);
            $cstmrCdtTrfInitn = $xpath->query('//sepa:CstmrCdtTrfInitn')->item(0);

            return null !== $msgId && null !== $cstmrCdtTrfInitn;
        } catch (\Exception $e) {
            return false;
        }
    }
}
