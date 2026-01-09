<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Generator;

use Digitick\Sepa\DomBuilder\DomBuilderFactory;
use Digitick\Sepa\GroupHeader;
use Digitick\Sepa\PaymentInformation;
use Digitick\Sepa\TransferFile\CustomerCreditTransferFile;
use Digitick\Sepa\TransferInformation\CustomerCreditTransferInformation;
use Nowo\SepaPaymentBundle\Model\Remesa\RemesaData;
use Nowo\SepaPaymentBundle\Model\Remesa\Transaction;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\HttpFoundation\Response;

/**
 * SEPA Credit Transfer generator.
 * Generates SEPA Credit Transfer XML files using Digitick\Sepa library according to ISO 20022 standard.
 * Used for payment remittances where the debtor sends money to the creditor.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class RemesaGenerator
{
    public const SERVICE_NAME = 'nowo_sepa_payment.generator.remesa_generator';

    /**
     * Constructor.
     *
     * @param IbanValidator $ibanValidator IBAN validator instance
     */
    public function __construct(
        private IbanValidator $ibanValidator
    ) {
    }

    /**
     * Generates a SEPA Credit Transfer XML file from array data.
     *
     * @param array<string, mixed> $data The remesa data in array format
     *
     * @throws \InvalidArgumentException If the data is invalid
     *
     * @return string The XML content
     */
    public function generateFromArray(array $data): string
    {
        $remesaData = $this->createRemesaDataFromArray($data);

        return $this->generate($remesaData);
    }

    /**
     * Generates a SEPA Credit Transfer XML file.
     *
     * @param RemesaData $remesaData The remesa data
     *
     * @throws \InvalidArgumentException If the data is invalid
     *
     * @return string The XML content
     */
    public function generate(RemesaData $remesaData): string
    {
        $this->validateRemesaData($remesaData);

        // Create and configure group header
        $groupHeader = new GroupHeader(
            $remesaData->getMessageId(),
            $remesaData->getInitiatingPartyName()
        );

        // Create transfer file (pain.001.001.03 format) with group header
        $transferFile = new CustomerCreditTransferFile($groupHeader);

        // Create payment information
        $paymentInformation = new PaymentInformation(
            $remesaData->getPaymentInfoId(),
            $this->ibanValidator->normalize($remesaData->getCreditorIban()),
            $remesaData->getCreditorBic() ?? '',
            $remesaData->getCreditorName(),
            'EUR'
        );
        // Payment method is automatically set based on the transfer file type (CustomerCreditTransferFile)
        $paymentInformation->setBatchBooking($remesaData->isBatchBooking());
        $paymentInformation->setDueDate($remesaData->getRequestedExecutionDate());

        // Set creditor address if available
        $creditorAddress = $remesaData->getCreditorAddress();
        if (null !== $creditorAddress) {
            $this->setCreditorPostalAddress($paymentInformation, $creditorAddress);
        }

        // Add transactions
        foreach ($remesaData->getTransactions() as $transaction) {
            $transferInformation = new CustomerCreditTransferInformation(
                (int) round($transaction->getAmount() * 100), // Convert to cents
                $this->ibanValidator->normalize($transaction->getDebtorIban()),
                $transaction->getDebtorName(),
                $transaction->getEndToEndId()
            );

            if (null !== $transaction->getDebtorBic()) {
                $transferInformation->setBic($transaction->getDebtorBic());
            }

            if (null !== $transaction->getRemittanceInformation()) {
                $transferInformation->setRemittanceInformation($transaction->getRemittanceInformation());
            }

            // Set debtor address if available
            $debtorAddress = $transaction->getDebtorAddress();
            if (null !== $debtorAddress) {
                $this->setPostalAddress($transferInformation, $debtorAddress);
            }

            $paymentInformation->addTransfer($transferInformation);
        }

        $transferFile->addPaymentInformation($paymentInformation);

        // Generate XML
        $domBuilder = DomBuilderFactory::createDomBuilder($transferFile);
        $xml = $domBuilder->asXml();

        // Add addresses to XML if they were provided
        $xml = $this->addAddressesToXml($xml, $remesaData);

        return $xml;
    }

    /**
     * Creates an HTTP Response with XML content for download.
     *
     * @param string $xmlData  The XML content
     * @param string $filename The filename for the download (e.g., "remesa-pago.xml")
     *
     * @return Response The HTTP response with XML content
     */
    public function createResponse(string $xmlData, string $filename): Response
    {
        return new Response($xmlData, Response::HTTP_OK, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /**
     * Creates RemesaData from array format.
     * Supports both camelCase and snake_case field names.
     *
     * @param array<string, mixed> $data The data in array format
     *
     * @throws \InvalidArgumentException If the data is invalid
     *
     * @return RemesaData The RemesaData object
     */
    private function createRemesaDataFromArray(array $data): RemesaData
    {
        // Normalize field names (support both camelCase and snake_case)
        $data = $this->normalizeArrayKeys($data);

        // Validate required fields
        $required = ['reference', 'initiatingPartyName', 'paymentInfoId', 'creditorIban', 'creditorName', 'requestedExecutionDate'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Parse dates
        $creationDate = $data['creationDate'] ?? new \DateTime();
        if (is_string($creationDate)) {
            $creationDate = new \DateTime($creationDate);
        } elseif (!$creationDate instanceof \DateTimeInterface) {
            throw new \InvalidArgumentException('creationDate must be a string or DateTimeInterface');
        }

        $requestedExecutionDate = $data['requestedExecutionDate'];
        if (is_string($requestedExecutionDate)) {
            $requestedExecutionDate = new \DateTime($requestedExecutionDate);
        } elseif (!$requestedExecutionDate instanceof \DateTimeInterface) {
            throw new \InvalidArgumentException('requestedExecutionDate must be a string or DateTimeInterface');
        }

        $remesaData = new RemesaData(
            $data['reference'],
            $creationDate,
            $data['initiatingPartyName'],
            $data['paymentInfoId'],
            $data['creditorIban'],
            $data['creditorName'],
            $requestedExecutionDate
        );

        if (isset($data['creditorBic'])) {
            $remesaData->setCreditorBic($data['creditorBic']);
        }

        if (isset($data['batchBooking'])) {
            $remesaData->setBatchBooking((bool) $data['batchBooking']);
        }

        // Set creditor address if provided (optional)
        if (isset($data['creditorAddress']) && is_array($data['creditorAddress']) && !empty($data['creditorAddress'])) {
            $remesaData->setCreditorAddressFromArray($data['creditorAddress']);
        } elseif (isset($data['creditor_street']) || isset($data['creditor_city']) || isset($data['creditor_postal_code']) || isset($data['creditor_country'])
                  || isset($data['creditorStreet']) || isset($data['creditorCity']) || isset($data['creditorPostalCode']) || isset($data['creditorCountry'])) {
            // Support individual address fields (only if at least one is provided)
            $remesaData->setCreditorAddress(
                $data['creditor_street'] ?? $data['creditorStreet'] ?? null,
                $data['creditor_city'] ?? $data['creditorCity'] ?? null,
                $data['creditor_postal_code'] ?? $data['creditorPostalCode'] ?? null,
                $data['creditor_country'] ?? $data['creditorCountry'] ?? null
            );
        }

        // Add transactions (after normalization, 'items' should already be 'transactions')
        if (isset($data['transactions']) && is_array($data['transactions'])) {
            foreach ($data['transactions'] as $transactionData) {
                // Normalize transaction array keys
                $transactionData = $this->normalizeTransactionArrayKeys($transactionData);
                $transaction = $this->createTransactionFromArray($transactionData);
                $remesaData->addTransaction($transaction);
            }
        }

        return $remesaData;
    }

    /**
     * Normalizes array keys from snake_case to camelCase format.
     * Supports both formats for backward compatibility.
     *
     * @param array<string, mixed> $data The data array
     *
     * @return array<string, mixed> Normalized array
     */
    private function normalizeArrayKeys(array $data): array
    {
        $mapping = [
            'message_id' => 'reference',
            'initiating_party_name' => 'initiatingPartyName',
            'payment_name' => 'paymentInfoId',
            'payment_info_id' => 'paymentInfoId',
            'creation_date' => 'creationDate',
            'requested_execution_date' => 'requestedExecutionDate',
            'creditor_name' => 'creditorName',
            'creditor_iban' => 'creditorIban',
            'creditor_bic' => 'creditorBic',
            'batch_booking' => 'batchBooking',
            'items' => 'transactions',
            'creditor_address' => 'creditorAddress',
        ];

        $normalized = [];
        foreach ($data as $key => $value) {
            // If key exists in mapping, use mapped key, otherwise keep original
            $normalizedKey = $mapping[$key] ?? $key;
            $normalized[$normalizedKey] = $value;
        }

        return $normalized;
    }

    /**
     * Normalizes transaction array keys from snake_case to camelCase format.
     *
     * @param array<string, mixed> $data The transaction data array
     *
     * @return array<string, mixed> Normalized array
     */
    private function normalizeTransactionArrayKeys(array $data): array
    {
        $mapping = [
            'instruction_id' => 'endToEndId',
            'end_to_end_id' => 'endToEndId',
            'debtor_iban' => 'debtorIban',
            'debtor_name' => 'debtorName',
            'debtor_bic' => 'debtorBic',
            'information' => 'remittanceInformation',
            'remittance_information' => 'remittanceInformation',
            'debtor_address' => 'debtorAddress',
        ];

        $normalized = [];
        foreach ($data as $key => $value) {
            // If key exists in mapping, use mapped key, otherwise keep original
            $normalizedKey = $mapping[$key] ?? $key;
            $normalized[$normalizedKey] = $value;
        }

        return $normalized;
    }

    /**
     * Creates Transaction from array format.
     *
     * @param array<string, mixed> $transactionData The transaction data
     *
     * @throws \InvalidArgumentException If the data is invalid
     *
     * @return Transaction The Transaction object
     */
    private function createTransactionFromArray(array $transactionData): Transaction
    {
        $required = ['amount', 'debtorIban', 'debtorName', 'endToEndId'];
        foreach ($required as $field) {
            if (!isset($transactionData[$field])) {
                throw new \InvalidArgumentException("Missing required transaction field: {$field}");
            }
        }

        // Parse amount (convert from cents if needed, but assume it's already in currency units)
        $amount = (float) $transactionData['amount'];
        // If amount seems to be in cents (very large number), convert to currency units
        if ($amount > 10000) {
            $amount = $amount / 100;
        }

        // Currency defaults to EUR if not provided
        $currency = $transactionData['currency'] ?? 'EUR';

        $transaction = new Transaction(
            $transactionData['endToEndId'],
            $amount,
            $currency,
            $transactionData['debtorIban'],
            $transactionData['debtorName']
        );

        if (isset($transactionData['debtorBic'])) {
            $transaction->setDebtorBic($transactionData['debtorBic']);
        }

        if (isset($transactionData['remittanceInformation'])) {
            $transaction->setRemittanceInformation($transactionData['remittanceInformation']);
        }

        // Set debtor address if provided (optional)
        if (isset($transactionData['debtorAddress']) && is_array($transactionData['debtorAddress']) && !empty($transactionData['debtorAddress'])) {
            $transaction->setDebtorAddressFromArray($transactionData['debtorAddress']);
        } elseif (isset($transactionData['debtor_street']) || isset($transactionData['debtor_city']) || isset($transactionData['debtor_postal_code']) || isset($transactionData['debtor_country'])
                  || isset($transactionData['debtorStreet']) || isset($transactionData['debtorCity']) || isset($transactionData['debtorPostalCode']) || isset($transactionData['debtorCountry'])) {
            // Support individual address fields (only if at least one is provided)
            $transaction->setDebtorAddress(
                $transactionData['debtor_street'] ?? $transactionData['debtorStreet'] ?? null,
                $transactionData['debtor_city'] ?? $transactionData['debtorCity'] ?? null,
                $transactionData['debtor_postal_code'] ?? $transactionData['debtorPostalCode'] ?? null,
                $transactionData['debtor_country'] ?? $transactionData['debtorCountry'] ?? null
            );
        }

        return $transaction;
    }

    /**
     * Sets postal address on transfer information (debtor address).
     * Uses available methods from the Digitick\Sepa library.
     *
     * @param CustomerCreditTransferInformation $transferInformation The transfer information object
     * @param array<string, string|null>        $address             Address array with keys: street, city, postalCode, country
     *
     * @return void
     */
    private function setPostalAddress(
        CustomerCreditTransferInformation $transferInformation,
        array $address
    ): void {
        // Try to set postal address using available methods
        if (method_exists($transferInformation, 'setPostalAddress')) {
            $transferInformation->setPostalAddress(
                $address['street'] ?? '',
                $address['city'] ?? '',
                $address['postalCode'] ?? '',
                $address['country'] ?? ''
            );
        } elseif (method_exists($transferInformation, 'setDebtorPostalAddress')) {
            $transferInformation->setDebtorPostalAddress(
                $address['street'] ?? '',
                $address['city'] ?? '',
                $address['postalCode'] ?? '',
                $address['country'] ?? ''
            );
        } elseif (method_exists($transferInformation, 'setAddress')) {
            $transferInformation->setAddress(
                $address['street'] ?? '',
                $address['city'] ?? '',
                $address['postalCode'] ?? '',
                $address['country'] ?? ''
            );
        }
        // Note: If the library doesn't support addresses in this format,
        // the address is still stored internally for internal use
    }

    /**
     * Sets creditor postal address on payment information.
     * Uses available methods from the Digitick\Sepa library.
     *
     * @param PaymentInformation         $paymentInformation The payment information object
     * @param array<string, string|null> $address            Address array with keys: street, city, postalCode, country
     *
     * @return void
     */
    private function setCreditorPostalAddress(
        PaymentInformation $paymentInformation,
        array $address
    ): void {
        // Try to set creditor postal address using available methods
        if (method_exists($paymentInformation, 'setCreditorPostalAddress')) {
            $paymentInformation->setCreditorPostalAddress(
                $address['street'] ?? '',
                $address['city'] ?? '',
                $address['postalCode'] ?? '',
                $address['country'] ?? ''
            );
        } elseif (method_exists($paymentInformation, 'setPostalAddress')) {
            $paymentInformation->setPostalAddress(
                $address['street'] ?? '',
                $address['city'] ?? '',
                $address['postalCode'] ?? '',
                $address['country'] ?? ''
            );
        } elseif (method_exists($paymentInformation, 'setAddress')) {
            $paymentInformation->setAddress(
                $address['street'] ?? '',
                $address['city'] ?? '',
                $address['postalCode'] ?? '',
                $address['country'] ?? ''
            );
        }
        // Note: If the library doesn't support addresses in this format,
        // the address is still stored internally for internal use
    }

    /**
     * Adds addresses to the generated XML using DOM manipulation.
     * This ensures addresses are included even if the library doesn't support them directly.
     *
     * @param string     $xml        The generated XML
     * @param RemesaData $remesaData The remesa data with addresses
     *
     * @return string The XML with addresses added
     */
    private function addAddressesToXml(string $xml, RemesaData $remesaData): string
    {
        try {
            $dom = new \DOMDocument();
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;

            if (!@$dom->loadXML($xml)) {
                // If XML is invalid, return original
                return $xml;
            }

            $xpath = new \DOMXPath($dom);
            // Detect namespace from root element
            $root = $dom->documentElement;
            $namespace = $root->namespaceURI ?? 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03';
            $xpath->registerNamespace('ns', $namespace);

            // Add creditor address if available
            $creditorAddress = $remesaData->getCreditorAddress();
            if (null !== $creditorAddress) {
                $this->addCreditorAddressToDom($dom, $xpath, $creditorAddress, $namespace);
            }

            // Add debtor addresses for each transaction
            $transactions = $remesaData->getTransactions();
            foreach ($transactions as $index => $transaction) {
                $debtorAddress = $transaction->getDebtorAddress();
                if (null !== $debtorAddress) {
                    $this->addDebtorAddressToDom($dom, $xpath, $debtorAddress, $index, $namespace);
                }
            }

            return $dom->saveXML();
        } catch (\Exception $e) {
            // If DOM manipulation fails, return original XML
            return $xml;
        }
    }

    /**
     * Adds creditor address to DOM.
     *
     * @param \DOMDocument $dom       The DOM document
     * @param \DOMXPath    $xpath     The XPath object
     * @param array        $address   The address array
     * @param string       $namespace The namespace URI
     *
     * @return void
     */
    private function addCreditorAddressToDom(\DOMDocument $dom, \DOMXPath $xpath, array $address, string $namespace): void
    {
        // Find Cdtr (Creditor) element
        $creditorNodes = $xpath->query('//ns:Cdtr');
        if ($creditorNodes === false || $creditorNodes->length === 0) {
            // Try without namespace prefix
            $creditorNodes = $xpath->query('//Cdtr');
            if ($creditorNodes === false || $creditorNodes->length === 0) {
                return;
            }
        }

        $creditorNode = $creditorNodes->item(0);
        $this->createPostalAddressElement($dom, $creditorNode, $address, $namespace);
    }

    /**
     * Adds debtor address to DOM.
     *
     * @param \DOMDocument $dom       The DOM document
     * @param \DOMXPath    $xpath     The XPath object
     * @param array        $address   The address array
     * @param int          $index     Transaction index
     * @param string       $namespace The namespace URI
     *
     * @return void
     */
    private function addDebtorAddressToDom(\DOMDocument $dom, \DOMXPath $xpath, array $address, int $index, string $namespace): void
    {
        // Find Dbtr (Debtor) elements
        $debtorNodes = $xpath->query('//ns:Dbtr');
        if ($debtorNodes === false || $debtorNodes->length === 0) {
            // Try without namespace prefix
            $debtorNodes = $xpath->query('//Dbtr');
            if ($debtorNodes === false || $debtorNodes->length <= $index) {
                return;
            }
        }

        if ($debtorNodes->length <= $index) {
            return;
        }

        $debtorNode = $debtorNodes->item($index);
        $this->createPostalAddressElement($dom, $debtorNode, $address, $namespace);
    }

    /**
     * Creates a PstlAdr (Postal Address) element in the DOM.
     * Only creates the element if at least one address field is provided.
     *
     * @param \DOMDocument $dom        The DOM document
     * @param \DOMElement  $parentNode The parent node
     * @param array        $address    The address array
     * @param string       $namespace  The namespace URI
     *
     * @return void
     */
    private function createPostalAddressElement(\DOMDocument $dom, \DOMElement $parentNode, array $address, string $namespace): void
    {
        // Check if at least one address field is provided
        $hasAddress = !empty($address['street'])
            || !empty($address['city'])
            || !empty($address['postalCode'])
            || !empty($address['country']);

        if (!$hasAddress) {
            // Don't create empty address element
            return;
        }

        // Check if PstlAdr already exists
        $existing = $parentNode->getElementsByTagNameNS($namespace, 'PstlAdr');
        if ($existing->length > 0) {
            // Remove existing address
            $parentNode->removeChild($existing->item(0));
        }

        $pstlAdr = $dom->createElementNS($namespace, 'PstlAdr');

        // Add structured address elements only if they are not empty
        if (!empty($address['street'])) {
            $strtNm = $dom->createElementNS($namespace, 'StrtNm', htmlspecialchars($address['street'], ENT_XML1, 'UTF-8'));
            $pstlAdr->appendChild($strtNm);
        }

        if (!empty($address['city'])) {
            $twnNm = $dom->createElementNS($namespace, 'TwnNm', htmlspecialchars($address['city'], ENT_XML1, 'UTF-8'));
            $pstlAdr->appendChild($twnNm);
        }

        if (!empty($address['postalCode'])) {
            $pstCd = $dom->createElementNS($namespace, 'PstCd', htmlspecialchars($address['postalCode'], ENT_XML1, 'UTF-8'));
            $pstlAdr->appendChild($pstCd);
        }

        if (!empty($address['country'])) {
            $ctry = $dom->createElementNS($namespace, 'Ctry', htmlspecialchars($address['country'], ENT_XML1, 'UTF-8'));
            $pstlAdr->appendChild($ctry);
        }

        // Only add PstlAdr if it has at least one child element
        if ($pstlAdr->childNodes->length > 0) {
            // Insert after Nm (Name) element if it exists, otherwise append
            $nmNodes = $parentNode->getElementsByTagNameNS($namespace, 'Nm');
            if ($nmNodes->length > 0) {
                $nextSibling = $nmNodes->item(0)->nextSibling;
                if ($nextSibling) {
                    $parentNode->insertBefore($pstlAdr, $nextSibling);
                } else {
                    $parentNode->appendChild($pstlAdr);
                }
            } else {
                $parentNode->appendChild($pstlAdr);
            }
        }
    }

    /**
     * Validates remesa data.
     *
     * @param RemesaData $remesaData The remesa data to validate
     *
     * @throws \InvalidArgumentException If the data is invalid
     *
     * @return void
     */
    private function validateRemesaData(RemesaData $remesaData): void
    {
        if (!$this->ibanValidator->isValid($remesaData->getCreditorIban())) {
            throw new \InvalidArgumentException('Invalid creditor IBAN: ' . $remesaData->getCreditorIban());
        }

        foreach ($remesaData->getTransactions() as $transaction) {
            if (!$this->ibanValidator->isValid($transaction->getDebtorIban())) {
                throw new \InvalidArgumentException('Invalid debtor IBAN: ' . $transaction->getDebtorIban());
            }
        }
    }
}
