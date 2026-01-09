<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Parser;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * SEPA Direct Debit XML parser.
 * Parses SEPA Direct Debit XML files to extract information.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class DirectDebitParser
{
    public const SERVICE_NAME = 'nowo_sepa_payment.parser.direct_debit_parser';

    /**
     * Parses a SEPA Direct Debit XML file.
     *
     * @param string $xml The XML content
     *
     * @throws \InvalidArgumentException If the XML is invalid
     *
     * @return array<string, mixed> Parsed data
     */
    public function parseDirectDebit(string $xml): array
    {
        $dom = new \DOMDocument();
        if (!@$dom->loadXML($xml)) {
            throw new \InvalidArgumentException('Invalid XML format');
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('sepa', 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02');

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

        $pmtMtd = $xpath->query('//sepa:PmtMtd')->item(0);
        if ($pmtMtd) {
            $data['paymentMethod'] = $pmtMtd->nodeValue;
        }

        $nbOfTxs = $xpath->query('//sepa:NbOfTxs')->item(0);
        if ($nbOfTxs) {
            $data['numberOfTransactions'] = (int) $nbOfTxs->nodeValue;
        }

        $ctrlSum = $xpath->query('//sepa:CtrlSum')->item(0);
        if ($ctrlSum) {
            $data['controlSum'] = (float) $ctrlSum->nodeValue;
        }

        $pmtInf = $xpath->query('//sepa:PmtInf')->item(0);
        if ($pmtInf) {
            // Extract sequence type
            $seqTp = $xpath->query('.//sepa:SeqTp', $pmtInf)->item(0);
            if ($seqTp) {
                $data['sequenceType'] = $seqTp->nodeValue;
            }

            // Extract due date
            $reqdColltnDt = $xpath->query('.//sepa:ReqdColltnDt', $pmtInf)->item(0);
            if ($reqdColltnDt) {
                $data['dueDate'] = $reqdColltnDt->nodeValue;
            }

            // Extract creditor information
            $cdtr = $xpath->query('.//sepa:Cdtr', $pmtInf)->item(0);
            if ($cdtr) {
                $cdtrNm = $xpath->query('.//sepa:Nm', $cdtr)->item(0);
                if ($cdtrNm) {
                    $data['creditorName'] = $cdtrNm->nodeValue;
                }

                // Extract creditor address
                $creditorAddress = $this->extractAddress($xpath, $cdtr);
                if (!empty($creditorAddress)) {
                    $data['creditorAddress'] = $creditorAddress;
                }
            }

            // Extract creditor account
            $cdtrAcct = $xpath->query('.//sepa:CdtrAcct', $pmtInf)->item(0);
            if ($cdtrAcct) {
                $iban = $xpath->query('.//sepa:IBAN', $cdtrAcct)->item(0);
                if ($iban) {
                    $data['creditorIban'] = $iban->nodeValue;
                }
            }

            // Extract creditor identification
            $cdtrSchmeId = $xpath->query('.//sepa:CdtrSchmeId', $pmtInf)->item(0);
            if ($cdtrSchmeId) {
                $id = $xpath->query('.//sepa:Id/sepa:PrvtId/sepa:Othr/sepa:Id', $cdtrSchmeId)->item(0);
                if ($id) {
                    $data['creditorId'] = $id->nodeValue;
                }
            }

            // Extract local instrument code
            $lclInstrm = $xpath->query('.//sepa:LclInstrm', $pmtInf)->item(0);
            if ($lclInstrm) {
                $cd = $xpath->query('.//sepa:Cd', $lclInstrm)->item(0);
                if ($cd) {
                    $data['localInstrumentCode'] = $cd->nodeValue;
                }
            }
        }

        // Extract transactions
        $transactions = [];
        $drctDbtTxInfNodes = $xpath->query('//sepa:DrctDbtTxInf');
        foreach ($drctDbtTxInfNodes as $txInf) {
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

            // Extract mandate related information
            $mndtRltdInf = $xpath->query('.//sepa:MndtRltdInf', $txInf)->item(0);
            if ($mndtRltdInf) {
                $mndtId = $xpath->query('.//sepa:MndtId', $mndtRltdInf)->item(0);
                if ($mndtId) {
                    $transaction['mandateId'] = $mndtId->nodeValue;
                }

                $dtOfSgntr = $xpath->query('.//sepa:DtOfSgntr', $mndtRltdInf)->item(0);
                if ($dtOfSgntr) {
                    $transaction['mandateSignDate'] = $dtOfSgntr->nodeValue;
                }
            }

            // Extract debtor information
            $dbtr = $xpath->query('.//sepa:Dbtr', $txInf)->item(0);
            if ($dbtr) {
                $dbtrNm = $xpath->query('.//sepa:Nm', $dbtr)->item(0);
                if ($dbtrNm) {
                    $transaction['debtorName'] = $dbtrNm->nodeValue;
                }

                // Extract debtor address
                $debtorAddress = $this->extractAddress($xpath, $dbtr);
                if (!empty($debtorAddress)) {
                    $transaction['debtorAddress'] = $debtorAddress;
                }
            }

            // Extract debtor account
            $dbtrAcct = $xpath->query('.//sepa:DbtrAcct', $txInf)->item(0);
            if ($dbtrAcct) {
                $iban = $xpath->query('.//sepa:IBAN', $dbtrAcct)->item(0);
                if ($iban) {
                    $transaction['debtorIban'] = $iban->nodeValue;
                }
            }

            // Extract debtor agent (BIC)
            $dbtrAgt = $xpath->query('.//sepa:DbtrAgt', $txInf)->item(0);
            if ($dbtrAgt) {
                $finInstnId = $xpath->query('.//sepa:FinInstnId', $dbtrAgt)->item(0);
                if ($finInstnId) {
                    $bic = $xpath->query('.//sepa:BIC', $finInstnId)->item(0);
                    if ($bic) {
                        $transaction['debtorBic'] = $bic->nodeValue;
                    }
                }
            }

            // Extract remittance information
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
     * Extracts address information from a parent node.
     *
     * @param \DOMXPath $xpath      The XPath object
     * @param \DOMNode  $parentNode The parent node containing address information
     *
     * @return array<string, string> Address array with keys: street, city, postalCode, country
     */
    private function extractAddress(\DOMXPath $xpath, \DOMNode $parentNode): array
    {
        $address = [];

        $pstlAdr = $xpath->query('.//sepa:PstlAdr', $parentNode)->item(0);
        if ($pstlAdr) {
            $strtNm = $xpath->query('.//sepa:StrtNm', $pstlAdr)->item(0);
            if ($strtNm) {
                $address['street'] = $strtNm->nodeValue;
            }

            $twnNm = $xpath->query('.//sepa:TwnNm', $pstlAdr)->item(0);
            if ($twnNm) {
                $address['city'] = $twnNm->nodeValue;
            }

            $pstCd = $xpath->query('.//sepa:PstCd', $pstlAdr)->item(0);
            if ($pstCd) {
                $address['postalCode'] = $pstCd->nodeValue;
            }

            $ctry = $xpath->query('.//sepa:Ctry', $pstlAdr)->item(0);
            if ($ctry) {
                $address['country'] = $ctry->nodeValue;
            }
        }

        return $address;
    }

    /**
     * Validates that an XML string is a valid SEPA Direct Debit file.
     *
     * @param string $xml The XML content
     *
     * @return bool True if valid, false otherwise
     */
    public function isValidDirectDebit(string $xml): bool
    {
        try {
            $dom = new \DOMDocument();
            if (!@$dom->loadXML($xml)) {
                return false;
            }

            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('sepa', 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02');

            // Check for required elements
            $msgId = $xpath->query('//sepa:MsgId')->item(0);
            $cstmrDrctDbtInitn = $xpath->query('//sepa:CstmrDrctDbtInitn')->item(0);

            return null !== $msgId && null !== $cstmrDrctDbtInitn;
        } catch (\Exception $e) {
            return false;
        }
    }
}
