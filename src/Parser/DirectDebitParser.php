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
     * @throws InvalidArgumentException If the XML is invalid
     *
     * @return array<string, mixed> Parsed data
     */
    public function parseDirectDebit(string $xml): array
    {
        $dom = new DOMDocument();
        if (!@$dom->loadXML($xml)) {
            throw new InvalidArgumentException('Invalid XML format');
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('sepa', 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02');

        $data = [];

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

        $pmtMtd = $this->getFirstNode($xpath, '//sepa:PmtMtd');
        if ($pmtMtd instanceof DOMNode) {
            $data['paymentMethod'] = $pmtMtd->nodeValue;
        }

        $nbOfTxs = $this->getFirstNode($xpath, '//sepa:NbOfTxs');
        if ($nbOfTxs instanceof DOMNode) {
            $data['numberOfTransactions'] = (int) $nbOfTxs->nodeValue;
        }

        $ctrlSum = $this->getFirstNode($xpath, '//sepa:CtrlSum');
        if ($ctrlSum instanceof DOMNode) {
            $data['controlSum'] = (float) $ctrlSum->nodeValue;
        }

        $pmtInf = $this->getFirstNode($xpath, '//sepa:PmtInf');
        if ($pmtInf instanceof DOMNode) {
            $seqTp = $this->getFirstNode($xpath, './/sepa:SeqTp', $pmtInf);
            if ($seqTp instanceof DOMNode) {
                $data['sequenceType'] = $seqTp->nodeValue;
            }

            $reqdColltnDt = $this->getFirstNode($xpath, './/sepa:ReqdColltnDt', $pmtInf);
            if ($reqdColltnDt instanceof DOMNode) {
                $data['dueDate'] = $reqdColltnDt->nodeValue;
            }

            $cdtr = $this->getFirstNode($xpath, './/sepa:Cdtr', $pmtInf);
            if ($cdtr instanceof DOMNode) {
                $cdtrNm = $this->getFirstNode($xpath, './/sepa:Nm', $cdtr);
                if ($cdtrNm instanceof DOMNode) {
                    $data['creditorName'] = $cdtrNm->nodeValue;
                }
                $creditorAddress = $this->extractAddress($xpath, $cdtr);
                if ($creditorAddress !== []) {
                    $data['creditorAddress'] = $creditorAddress;
                }
            }

            $cdtrAcct = $this->getFirstNode($xpath, './/sepa:CdtrAcct', $pmtInf);
            if ($cdtrAcct instanceof DOMNode) {
                $iban = $this->getFirstNode($xpath, './/sepa:IBAN', $cdtrAcct);
                if ($iban instanceof DOMNode) {
                    $data['creditorIban'] = $iban->nodeValue;
                }
            }

            $cdtrSchmeId = $this->getFirstNode($xpath, './/sepa:CdtrSchmeId', $pmtInf);
            if ($cdtrSchmeId instanceof DOMNode) {
                $id = $this->getFirstNode($xpath, './/sepa:Id/sepa:PrvtId/sepa:Othr/sepa:Id', $cdtrSchmeId);
                if ($id instanceof DOMNode) {
                    $data['creditorId'] = $id->nodeValue;
                }
            }

            $lclInstrm = $this->getFirstNode($xpath, './/sepa:LclInstrm', $pmtInf);
            if ($lclInstrm instanceof DOMNode) {
                $cd = $this->getFirstNode($xpath, './/sepa:Cd', $lclInstrm);
                if ($cd instanceof DOMNode) {
                    $data['localInstrumentCode'] = $cd->nodeValue;
                }
            }
        }

        $transactions      = [];
        $drctDbtTxInfNodes = $xpath->query('//sepa:DrctDbtTxInf');
        if ($drctDbtTxInfNodes instanceof DOMNodeList) {
            foreach ($drctDbtTxInfNodes as $txInf) {
                $ctx = $txInf instanceof DOMNode ? $txInf : null;
                if (!$ctx instanceof DOMNode) {
                    continue;
                }
                $transaction = [];

                $endToEndId = $this->getFirstNode($xpath, './/sepa:EndToEndId', $ctx);
                if ($endToEndId instanceof DOMNode) {
                    $transaction['endToEndId'] = $endToEndId->nodeValue;
                }

                $instdAmt = $this->getFirstNode($xpath, './/sepa:InstdAmt', $ctx);
                if ($instdAmt instanceof DOMNode) {
                    $transaction['amount']   = (float) $instdAmt->nodeValue;
                    $transaction['currency'] = $instdAmt instanceof DOMElement ? $instdAmt->getAttribute('Ccy') : '';
                }

                $mndtRltdInf = $this->getFirstNode($xpath, './/sepa:MndtRltdInf', $ctx);
                if ($mndtRltdInf instanceof DOMNode) {
                    $mndtId = $this->getFirstNode($xpath, './/sepa:MndtId', $mndtRltdInf);
                    if ($mndtId instanceof DOMNode) {
                        $transaction['mandateId'] = $mndtId->nodeValue;
                    }
                    $dtOfSgntr = $this->getFirstNode($xpath, './/sepa:DtOfSgntr', $mndtRltdInf);
                    if ($dtOfSgntr instanceof DOMNode) {
                        $transaction['mandateSignDate'] = $dtOfSgntr->nodeValue;
                    }
                }

                $dbtr = $this->getFirstNode($xpath, './/sepa:Dbtr', $ctx);
                if ($dbtr instanceof DOMNode) {
                    $dbtrNm = $this->getFirstNode($xpath, './/sepa:Nm', $dbtr);
                    if ($dbtrNm instanceof DOMNode) {
                        $transaction['debtorName'] = $dbtrNm->nodeValue;
                    }
                    $debtorAddress = $this->extractAddress($xpath, $dbtr);
                    if ($debtorAddress !== []) {
                        $transaction['debtorAddress'] = $debtorAddress;
                    }
                }

                $dbtrAcct = $this->getFirstNode($xpath, './/sepa:DbtrAcct', $ctx);
                if ($dbtrAcct instanceof DOMNode) {
                    $iban = $this->getFirstNode($xpath, './/sepa:IBAN', $dbtrAcct);
                    if ($iban instanceof DOMNode) {
                        $transaction['debtorIban'] = $iban->nodeValue;
                    }
                }

                $dbtrAgt = $this->getFirstNode($xpath, './/sepa:DbtrAgt', $ctx);
                if ($dbtrAgt instanceof DOMNode) {
                    $finInstnId = $this->getFirstNode($xpath, './/sepa:FinInstnId', $dbtrAgt);
                    if ($finInstnId instanceof DOMNode) {
                        $bic = $this->getFirstNode($xpath, './/sepa:BIC', $finInstnId);
                        if ($bic instanceof DOMNode) {
                            $transaction['debtorBic'] = $bic->nodeValue;
                        }
                    }
                }

                $rmtInf = $this->getFirstNode($xpath, './/sepa:Ustrd', $ctx);
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
     * Extracts address information from a parent node.
     *
     * @param DOMXPath $xpath The XPath object
     * @param DOMNode $parentNode The parent node containing address information
     *
     * PHPStan: return.type — nodeValue can be null; actual type is array<string, string|null>.
     *
     * @return array<string, string|null> Address array with keys: street, city, postalCode, country
     */
    private function extractAddress(DOMXPath $xpath, DOMNode $parentNode): array
    {
        $address = [];
        $pstlAdr = $this->getFirstNode($xpath, './/sepa:PstlAdr', $parentNode);
        if ($pstlAdr instanceof DOMNode) {
            $strtNm = $this->getFirstNode($xpath, './/sepa:StrtNm', $pstlAdr);
            if ($strtNm instanceof DOMNode) {
                $address['street'] = $strtNm->nodeValue;
            }
            $twnNm = $this->getFirstNode($xpath, './/sepa:TwnNm', $pstlAdr);
            if ($twnNm instanceof DOMNode) {
                $address['city'] = $twnNm->nodeValue;
            }
            $pstCd = $this->getFirstNode($xpath, './/sepa:PstCd', $pstlAdr);
            if ($pstCd instanceof DOMNode) {
                $address['postalCode'] = $pstCd->nodeValue;
            }
            $ctry = $this->getFirstNode($xpath, './/sepa:Ctry', $pstlAdr);
            if ($ctry instanceof DOMNode) {
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
            // Empty string check
            if (trim($xml) === '') {
                return false;
            }

            $dom = new DOMDocument();
            if (!@$dom->loadXML($xml)) {
                return false;
            }

            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('sepa', 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02');

            $msgId             = $this->getFirstNode($xpath, '//sepa:MsgId');
            $cstmrDrctDbtInitn = $this->getFirstNode($xpath, '//sepa:CstmrDrctDbtInitn');

            return $msgId instanceof DOMNode && $cstmrDrctDbtInitn instanceof DOMNode;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Returns the first node from an XPath query.
     * PHPStan: query() returns DOMNodeList|false; helper avoids calling item() on false.
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
