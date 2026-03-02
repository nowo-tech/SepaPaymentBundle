<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Exporter;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

use function is_array;

use const JSON_ERROR_NONE;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_UNICODE;

/**
 * Export service for converting SEPA payment data to various formats.
 * Supports JSON and CSV export formats.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class ExportService
{
    public const SERVICE_NAME = 'nowo_sepa_payment.exporter.export_service';

    /**
     * Exports Credit Transfer data to JSON format.
     *
     * @param array<string, mixed> $data The credit transfer data (from parser or array format)
     * @param bool $prettyPrint Whether to pretty print JSON (default: true)
     *
     * @return string JSON string
     */
    public function exportCreditTransferToJson(array $data, bool $prettyPrint = true): string
    {
        $json = json_encode($data, $prettyPrint ? JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE : JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new RuntimeException('Failed to encode data to JSON: ' . json_last_error_msg());
        }

        return $json;
    }

    /**
     * Exports Direct Debit data to JSON format.
     *
     * @param array<string, mixed> $data The direct debit data (from parser or array format)
     * @param bool $prettyPrint Whether to pretty print JSON (default: true)
     *
     * @return string JSON string
     */
    public function exportDirectDebitToJson(array $data, bool $prettyPrint = true): string
    {
        return $this->exportCreditTransferToJson($data, $prettyPrint);
    }

    /**
     * Exports Credit Transfer data to CSV format.
     *
     * @param array<string, mixed> $data The credit transfer data (from parser or array format)
     * @param string $delimiter CSV delimiter (default: ',')
     * @param string $enclosure CSV enclosure (default: '"')
     *
     * @return string CSV string
     */
    public function exportCreditTransferToCsv(array $data, string $delimiter = ',', string $enclosure = '"'): string
    {
        $rows = [];

        // Header row
        $header = [
            'Message ID',
            'Creation Date',
            'Initiating Party Name',
            'Payment Info ID',
            'Creditor IBAN',
            'Creditor Name',
            'Creditor BIC',
            'Execution Date',
            'Transaction: End-to-End ID',
            'Transaction: Amount',
            'Transaction: Currency',
            'Transaction: Debtor IBAN',
            'Transaction: Debtor Name',
            'Transaction: Debtor BIC',
            'Transaction: Remittance Information',
        ];
        $rows[] = $header;

        // Extract transactions
        $transactions = $data['transactions'] ?? [];
        if (empty($transactions) && isset($data['paymentInfo']['transactions'])) {
            $transactions = $data['paymentInfo']['transactions'];
        }

        foreach ($transactions as $transaction) {
            $row = [
                $data['messageId'] ?? '',
                $data['creationDate'] ?? '',
                $data['initiatingPartyName'] ?? '',
                $data['paymentInfoId'] ?? ($data['paymentInfo']['paymentInfoId'] ?? ''),
                $data['creditorIban'] ?? ($data['paymentInfo']['creditorIban'] ?? ''),
                $data['creditorName'] ?? ($data['paymentInfo']['creditorName'] ?? ''),
                $data['creditorBic'] ?? ($data['paymentInfo']['creditorBic'] ?? ''),
                $data['requestedExecutionDate'] ?? ($data['paymentInfo']['requestedExecutionDate'] ?? ''),
                $transaction['endToEndId'] ?? '',
                $transaction['amount'] ?? '',
                $transaction['currency'] ?? '',
                $transaction['debtorIban'] ?? '',
                $transaction['debtorName'] ?? '',
                $transaction['debtorBic'] ?? '',
                $transaction['remittanceInformation'] ?? '',
            ];
            $rows[] = $row;
        }

        // If no transactions, add one row with header data
        if (empty($transactions)) {
            $rows[] = [
                $data['messageId'] ?? '',
                $data['creationDate'] ?? '',
                $data['initiatingPartyName'] ?? '',
                $data['paymentInfoId'] ?? '',
                $data['creditorIban'] ?? '',
                $data['creditorName'] ?? '',
                $data['creditorBic'] ?? '',
                $data['requestedExecutionDate'] ?? '',
                '', '', '', '', '', '',
            ];
        }

        return $this->arrayToCsv($rows, $delimiter, $enclosure);
    }

    /**
     * Exports Direct Debit data to CSV format.
     *
     * @param array<string, mixed> $data The direct debit data (from parser or array format)
     * @param string $delimiter CSV delimiter (default: ',')
     * @param string $enclosure CSV enclosure (default: '"')
     *
     * @return string CSV string
     */
    public function exportDirectDebitToCsv(array $data, string $delimiter = ',', string $enclosure = '"'): string
    {
        $rows = [];

        // Header row
        $header = [
            'Message ID',
            'Creation Date',
            'Initiating Party Name',
            'Payment Info ID',
            'Creditor IBAN',
            'Creditor Name',
            'Creditor BIC',
            'Creditor ID',
            'Due Date',
            'Sequence Type',
            'Local Instrument Code',
            'Transaction: End-to-End ID',
            'Transaction: Amount',
            'Transaction: Currency',
            'Transaction: Debtor IBAN',
            'Transaction: Debtor Name',
            'Transaction: Debtor BIC',
            'Transaction: Mandate ID',
            'Transaction: Mandate Signature Date',
            'Transaction: Remittance Information',
        ];
        $rows[] = $header;

        // Extract transactions
        $transactions = $data['transactions'] ?? [];
        if (empty($transactions) && isset($data['paymentInfo']['transactions'])) {
            $transactions = $data['paymentInfo']['transactions'];
        }

        $paymentInfo = $data['paymentInfo'] ?? $data;

        foreach ($transactions as $transaction) {
            $row = [
                $data['messageId'] ?? '',
                $data['creationDate'] ?? '',
                $data['initiatingPartyName'] ?? '',
                $paymentInfo['paymentInfoId'] ?? '',
                $paymentInfo['creditorIban'] ?? '',
                $paymentInfo['creditorName'] ?? '',
                $paymentInfo['creditorBic'] ?? '',
                $paymentInfo['creditorId'] ?? '',
                $paymentInfo['dueDate'] ?? '',
                $paymentInfo['sequenceType'] ?? '',
                $paymentInfo['localInstrumentCode'] ?? '',
                $transaction['endToEndId'] ?? '',
                $transaction['amount'] ?? '',
                $transaction['currency'] ?? '',
                $transaction['debtorIban'] ?? '',
                $transaction['debtorName'] ?? '',
                $transaction['debtorBic'] ?? '',
                $transaction['mandateId'] ?? '',
                $transaction['mandateSignatureDate'] ?? '',
                $transaction['remittanceInformation'] ?? '',
            ];
            $rows[] = $row;
        }

        // If no transactions, add one row with header data
        if (empty($transactions)) {
            $rows[] = [
                $data['messageId'] ?? '',
                $data['creationDate'] ?? '',
                $data['initiatingPartyName'] ?? '',
                $paymentInfo['paymentInfoId'] ?? '',
                $paymentInfo['creditorIban'] ?? '',
                $paymentInfo['creditorName'] ?? '',
                $paymentInfo['creditorBic'] ?? '',
                $paymentInfo['creditorId'] ?? '',
                $paymentInfo['dueDate'] ?? '',
                $paymentInfo['sequenceType'] ?? '',
                $paymentInfo['localInstrumentCode'] ?? '',
                '', '', '', '', '', '', '', '', '',
            ];
        }

        return $this->arrayToCsv($rows, $delimiter, $enclosure);
    }

    /**
     * Imports Credit Transfer data from JSON format.
     *
     * @param string $json JSON string
     *
     * @return array<string, mixed> Credit transfer data array
     */
    public function importCreditTransferFromJson(string $json): array
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON format: ' . json_last_error_msg());
        }

        if (!is_array($data)) {
            throw new InvalidArgumentException('JSON must contain an object/array');
        }

        return $data;
    }

    /**
     * Imports Direct Debit data from JSON format.
     *
     * @param string $json JSON string
     *
     * @return array<string, mixed> Direct debit data array
     */
    public function importDirectDebitFromJson(string $json): array
    {
        return $this->importCreditTransferFromJson($json);
    }

    /**
     * Converts an array to CSV format.
     *
     * @param array<int, array<int, mixed>> $rows Array of rows
     * @param string $delimiter CSV delimiter
     * @param string $enclosure CSV enclosure
     *
     * @return string CSV string
     */
    private function arrayToCsv(array $rows, string $delimiter, string $enclosure): string
    {
        $output = fopen('php://temp', 'r+');

        if ($output === false) {
            throw new RuntimeException('Failed to open temporary stream for CSV generation');
        }

        foreach ($rows as $row) {
            fputcsv($output, $row, $delimiter, $enclosure, '\\');
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        if ($csv === false) {
            throw new RuntimeException('Failed to generate CSV content');
        }

        return $csv;
    }
}
