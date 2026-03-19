<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Exporter;

/**
 * Default CSV stream handler using php://temp.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class PhpTempCsvStreamHandler implements CsvStreamHandlerInterface
{
    public function open()
    {
        return fopen('php://temp', 'r+');
    }

    /**
     * @param resource $stream
     */
    public function getContents($stream): string|false
    {
        return stream_get_contents($stream);
    }
}
