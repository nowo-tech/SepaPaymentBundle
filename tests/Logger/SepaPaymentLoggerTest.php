<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Logger;

use Nowo\SepaPaymentBundle\Logger\SepaPaymentLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;

/**
 * Test cases for SepaPaymentLogger.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class SepaPaymentLoggerTest extends TestCase
{
    private TestLogger $testLogger;
    private SepaPaymentLogger $sepaLogger;

    protected function setUp(): void
    {
        $this->testLogger = new TestLogger();
        $this->sepaLogger = new SepaPaymentLogger($this->testLogger);
    }

    public function testLogCreditTransferGenerationStart(): void
    {
        $this->sepaLogger->logCreditTransferGenerationStart('MSG-001', 2, ['creditor' => 'Test Company']);

        $this->assertCount(1, $this->testLogger->logs);
        $log = $this->testLogger->logs[0];
        $this->assertEquals(LogLevel::INFO, $log['level']);
        $this->assertEquals('SEPA Credit Transfer generation started', $log['message']);
        $this->assertEquals('MSG-001', $log['context']['message_id']);
        $this->assertEquals(2, $log['context']['transaction_count']);
        $this->assertEquals('credit_transfer_generation', $log['context']['operation']);
        $this->assertEquals('started', $log['context']['status']);
        $this->assertEquals('Test Company', $log['context']['creditor']);
    }

    public function testLogCreditTransferGenerationSuccess(): void
    {
        $this->sepaLogger->logCreditTransferGenerationSuccess('MSG-001', 2, 1024, ['creditor' => 'Test Company']);

        $this->assertCount(1, $this->testLogger->logs);
        $log = $this->testLogger->logs[0];
        $this->assertEquals(LogLevel::INFO, $log['level']);
        $this->assertEquals('SEPA Credit Transfer generation completed successfully', $log['message']);
        $this->assertEquals('MSG-001', $log['context']['message_id']);
        $this->assertEquals(2, $log['context']['transaction_count']);
        $this->assertEquals(1024, $log['context']['xml_length']);
        $this->assertEquals('success', $log['context']['status']);
    }

    public function testLogCreditTransferGenerationFailure(): void
    {
        $this->sepaLogger->logCreditTransferGenerationFailure('MSG-001', 'Invalid IBAN', ['creditor' => 'Test Company']);

        $this->assertCount(1, $this->testLogger->logs);
        $log = $this->testLogger->logs[0];
        $this->assertEquals(LogLevel::ERROR, $log['level']);
        $this->assertEquals('SEPA Credit Transfer generation failed', $log['message']);
        $this->assertEquals('MSG-001', $log['context']['message_id']);
        $this->assertEquals('Invalid IBAN', $log['context']['error']);
        $this->assertEquals('failure', $log['context']['status']);
    }

    public function testLogDirectDebitGenerationStart(): void
    {
        $this->sepaLogger->logDirectDebitGenerationStart('MSG-001', 3, ['creditor' => 'Test Company']);

        $this->assertCount(1, $this->testLogger->logs);
        $log = $this->testLogger->logs[0];
        $this->assertEquals(LogLevel::INFO, $log['level']);
        $this->assertEquals('SEPA Direct Debit generation started', $log['message']);
        $this->assertEquals('MSG-001', $log['context']['message_id']);
        $this->assertEquals(3, $log['context']['transaction_count']);
        $this->assertEquals('direct_debit_generation', $log['context']['operation']);
    }

    public function testLogDirectDebitGenerationSuccess(): void
    {
        $this->sepaLogger->logDirectDebitGenerationSuccess('MSG-001', 3, 2048);

        $this->assertCount(1, $this->testLogger->logs);
        $log = $this->testLogger->logs[0];
        $this->assertEquals(LogLevel::INFO, $log['level']);
        $this->assertEquals('SEPA Direct Debit generation completed successfully', $log['message']);
        $this->assertEquals(2048, $log['context']['xml_length']);
    }

    public function testLogDirectDebitGenerationFailure(): void
    {
        $this->sepaLogger->logDirectDebitGenerationFailure('MSG-001', 'Invalid mandate');

        $this->assertCount(1, $this->testLogger->logs);
        $log = $this->testLogger->logs[0];
        $this->assertEquals(LogLevel::ERROR, $log['level']);
        $this->assertEquals('Invalid mandate', $log['context']['error']);
    }

    public function testLogIbanValidation(): void
    {
        $this->sepaLogger->logIbanValidation('ES9121000418450200051332', true);
        $this->sepaLogger->logIbanValidation('INVALID', false);

        $this->assertCount(2, $this->testLogger->logs);
        $this->assertEquals(LogLevel::INFO, $this->testLogger->logs[0]['level']);
        $this->assertEquals(LogLevel::WARNING, $this->testLogger->logs[1]['level']);
        $this->assertTrue($this->testLogger->logs[0]['context']['is_valid']);
        $this->assertFalse($this->testLogger->logs[1]['context']['is_valid']);
    }

    public function testLogBicValidation(): void
    {
        $this->sepaLogger->logBicValidation('CAIXESBBXXX', true);
        $this->sepaLogger->logBicValidation('INVALID', false);

        $this->assertCount(2, $this->testLogger->logs);
        $this->assertEquals(LogLevel::INFO, $this->testLogger->logs[0]['level']);
        $this->assertEquals(LogLevel::WARNING, $this->testLogger->logs[1]['level']);
    }

    public function testLogBusinessRulesValidation(): void
    {
        $this->sepaLogger->logBusinessRulesValidation('amount_limit', true);
        $this->sepaLogger->logBusinessRulesValidation('date_validation', false);

        $this->assertCount(2, $this->testLogger->logs);
        $this->assertEquals(LogLevel::INFO, $this->testLogger->logs[0]['level']);
        $this->assertEquals(LogLevel::WARNING, $this->testLogger->logs[1]['level']);
        $this->assertEquals('amount_limit', $this->testLogger->logs[0]['context']['rule']);
    }

    public function testLogCreditTransferParsing(): void
    {
        $this->sepaLogger->logCreditTransferParsing('MSG-001', 2);

        $this->assertCount(1, $this->testLogger->logs);
        $log = $this->testLogger->logs[0];
        $this->assertEquals(LogLevel::INFO, $log['level']);
        $this->assertEquals('SEPA Credit Transfer parsed', $log['message']);
        $this->assertEquals('credit_transfer_parsing', $log['context']['operation']);
    }

    public function testLogDirectDebitParsing(): void
    {
        $this->sepaLogger->logDirectDebitParsing('MSG-001', 3);

        $this->assertCount(1, $this->testLogger->logs);
        $log = $this->testLogger->logs[0];
        $this->assertEquals(LogLevel::INFO, $log['level']);
        $this->assertEquals('direct_debit_parsing', $log['context']['operation']);
    }

    public function testLogParsingError(): void
    {
        $this->sepaLogger->logParsingError('Invalid XML format');

        $this->assertCount(1, $this->testLogger->logs);
        $log = $this->testLogger->logs[0];
        $this->assertEquals(LogLevel::ERROR, $log['level']);
        $this->assertEquals('Invalid XML format', $log['context']['error']);
    }

    public function testLogXsdValidation(): void
    {
        $this->sepaLogger->logXsdValidation('credit_transfer', true);
        $this->sepaLogger->logXsdValidation('direct_debit', false);

        $this->assertCount(2, $this->testLogger->logs);
        $this->assertEquals(LogLevel::INFO, $this->testLogger->logs[0]['level']);
        $this->assertEquals(LogLevel::ERROR, $this->testLogger->logs[1]['level']);
    }

    public function testNullLogger(): void
    {
        $nullLogger = new SepaPaymentLogger();
        // Should not throw any errors
        $nullLogger->logCreditTransferGenerationStart('MSG-001', 1);
        $nullLogger->logCreditTransferGenerationSuccess('MSG-001', 1, 100);
        $nullLogger->logCreditTransferGenerationFailure('MSG-001', 'Error');
        $this->assertTrue(true); // If we get here, no errors occurred
    }
}

/**
 * Test logger implementation for testing purposes.
 */
class TestLogger implements LoggerInterface
{
    /**
     * @var array<int, array{level: string, message: string, context: array<string, mixed>}>
     */
    public array $logs = [];

    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->logs[] = [
            'level'   => $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}
