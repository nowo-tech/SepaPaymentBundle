<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Logger;

use Psr\Log\LoggerInterface;
use Stringable;

/**
 * Test logger implementation for testing purposes.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
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
