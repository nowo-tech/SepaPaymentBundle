<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Structured logger for SEPA Payment Bundle operations.
 * Provides contextual logging for generation, validation, and parsing operations.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class SepaPaymentLogger
{
    public const SERVICE_NAME = 'nowo_sepa_payment.logger.sepa_payment_logger';

    /**
     * Logger instance.
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Constructor.
     *
     * @param LoggerInterface|null $logger Optional logger instance (defaults to NullLogger if not provided)
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Logs Credit Transfer generation start.
     *
     * @param string $messageId Message ID
     * @param int    $transactionCount Number of transactions
     * @param array<string, mixed> $context Additional context
     *
     * @return void
     */
    public function logCreditTransferGenerationStart(string $messageId, int $transactionCount, array $context = []): void
    {
        $this->logger->info('SEPA Credit Transfer generation started', array_merge([
            'operation' => 'credit_transfer_generation',
            'status' => 'started',
            'message_id' => $messageId,
            'transaction_count' => $transactionCount,
        ], $context));
    }

    /**
     * Logs Credit Transfer generation success.
     *
     * @param string $messageId Message ID
     * @param int    $transactionCount Number of transactions
     * @param int    $xmlLength XML length in bytes
     * @param array<string, mixed> $context Additional context
     *
     * @return void
     */
    public function logCreditTransferGenerationSuccess(string $messageId, int $transactionCount, int $xmlLength, array $context = []): void
    {
        $this->logger->info('SEPA Credit Transfer generation completed successfully', array_merge([
            'operation' => 'credit_transfer_generation',
            'status' => 'success',
            'message_id' => $messageId,
            'transaction_count' => $transactionCount,
            'xml_length' => $xmlLength,
        ], $context));
    }

    /**
     * Logs Credit Transfer generation failure.
     *
     * @param string $messageId Message ID
     * @param string $error Error message
     * @param array<string, mixed> $context Additional context
     *
     * @return void
     */
    public function logCreditTransferGenerationFailure(string $messageId, string $error, array $context = []): void
    {
        $this->logger->error('SEPA Credit Transfer generation failed', array_merge([
            'operation' => 'credit_transfer_generation',
            'status' => 'failure',
            'message_id' => $messageId,
            'error' => $error,
        ], $context));
    }

    /**
     * Logs Direct Debit generation start.
     *
     * @param string $messageId Message ID
     * @param int    $transactionCount Number of transactions
     * @param array<string, mixed> $context Additional context
     *
     * @return void
     */
    public function logDirectDebitGenerationStart(string $messageId, int $transactionCount, array $context = []): void
    {
        $this->logger->info('SEPA Direct Debit generation started', array_merge([
            'operation' => 'direct_debit_generation',
            'status' => 'started',
            'message_id' => $messageId,
            'transaction_count' => $transactionCount,
        ], $context));
    }

    /**
     * Logs Direct Debit generation success.
     *
     * @param string $messageId Message ID
     * @param int    $transactionCount Number of transactions
     * @param int    $xmlLength XML length in bytes
     * @param array<string, mixed> $context Additional context
     *
     * @return void
     */
    public function logDirectDebitGenerationSuccess(string $messageId, int $transactionCount, int $xmlLength, array $context = []): void
    {
        $this->logger->info('SEPA Direct Debit generation completed successfully', array_merge([
            'operation' => 'direct_debit_generation',
            'status' => 'success',
            'message_id' => $messageId,
            'transaction_count' => $transactionCount,
            'xml_length' => $xmlLength,
        ], $context));
    }

    /**
     * Logs Direct Debit generation failure.
     *
     * @param string $messageId Message ID
     * @param string $error Error message
     * @param array<string, mixed> $context Additional context
     *
     * @return void
     */
    public function logDirectDebitGenerationFailure(string $messageId, string $error, array $context = []): void
    {
        $this->logger->error('SEPA Direct Debit generation failed', array_merge([
            'operation' => 'direct_debit_generation',
            'status' => 'failure',
            'message_id' => $messageId,
            'error' => $error,
        ], $context));
    }

    /**
     * Logs IBAN validation.
     *
     * @param string $iban IBAN to validate
     * @param bool   $isValid Validation result
     * @param array<string, mixed> $context Additional context
     *
     * @return void
     */
    public function logIbanValidation(string $iban, bool $isValid, array $context = []): void
    {
        $level = $isValid ? 'info' : 'warning';
        $this->logger->log($level, 'IBAN validation', array_merge([
            'operation' => 'iban_validation',
            'iban' => $iban,
            'is_valid' => $isValid,
        ], $context));
    }

    /**
     * Logs BIC validation.
     *
     * @param string $bic BIC to validate
     * @param bool   $isValid Validation result
     * @param array<string, mixed> $context Additional context
     *
     * @return void
     */
    public function logBicValidation(string $bic, bool $isValid, array $context = []): void
    {
        $level = $isValid ? 'info' : 'warning';
        $this->logger->log($level, 'BIC validation', array_merge([
            'operation' => 'bic_validation',
            'bic' => $bic,
            'is_valid' => $isValid,
        ], $context));
    }

    /**
     * Logs business rules validation.
     *
     * @param string $rule Rule name
     * @param bool   $isValid Validation result
     * @param array<string, mixed> $context Additional context
     *
     * @return void
     */
    public function logBusinessRulesValidation(string $rule, bool $isValid, array $context = []): void
    {
        $level = $isValid ? 'info' : 'warning';
        $this->logger->log($level, 'Business rules validation', array_merge([
            'operation' => 'business_rules_validation',
            'rule' => $rule,
            'is_valid' => $isValid,
        ], $context));
    }

    /**
     * Logs Credit Transfer parsing.
     *
     * @param string $messageId Parsed message ID
     * @param int    $transactionCount Number of transactions parsed
     * @param array<string, mixed> $context Additional context
     *
     * @return void
     */
    public function logCreditTransferParsing(string $messageId, int $transactionCount, array $context = []): void
    {
        $this->logger->info('SEPA Credit Transfer parsed', array_merge([
            'operation' => 'credit_transfer_parsing',
            'message_id' => $messageId,
            'transaction_count' => $transactionCount,
        ], $context));
    }

    /**
     * Logs Direct Debit parsing.
     *
     * @param string $messageId Parsed message ID
     * @param int    $transactionCount Number of transactions parsed
     * @param array<string, mixed> $context Additional context
     *
     * @return void
     */
    public function logDirectDebitParsing(string $messageId, int $transactionCount, array $context = []): void
    {
        $this->logger->info('SEPA Direct Debit parsed', array_merge([
            'operation' => 'direct_debit_parsing',
            'message_id' => $messageId,
            'transaction_count' => $transactionCount,
        ], $context));
    }

    /**
     * Logs parsing error.
     *
     * @param string $error Error message
     * @param array<string, mixed> $context Additional context
     *
     * @return void
     */
    public function logParsingError(string $error, array $context = []): void
    {
        $this->logger->error('SEPA XML parsing failed', array_merge([
            'operation' => 'parsing',
            'status' => 'failure',
            'error' => $error,
        ], $context));
    }

    /**
     * Logs XSD validation.
     *
     * @param string $type Validation type (credit_transfer or direct_debit)
     * @param bool   $isValid Validation result
     * @param array<string, mixed> $context Additional context
     *
     * @return void
     */
    public function logXsdValidation(string $type, bool $isValid, array $context = []): void
    {
        $level = $isValid ? 'info' : 'error';
        $this->logger->log($level, 'XSD schema validation', array_merge([
            'operation' => 'xsd_validation',
            'type' => $type,
            'is_valid' => $isValid,
        ], $context));
    }
}
