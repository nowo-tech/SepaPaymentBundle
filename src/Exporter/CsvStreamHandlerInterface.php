<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Exporter;

/**
 * Handles opening a temporary stream and reading its contents for CSV generation.
 * Allows testing failure paths (e.g. fopen or stream_get_contents failure) via test doubles.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
interface CsvStreamHandlerInterface
{
    /**
     * Opens a writable temporary stream for CSV output.
     *
     * @return resource|false Stream resource or false on failure
     */
    public function open();

    /**
     * Reads the full contents of the stream after writing.
     *
     * @param resource $stream Stream opened by open()
     *
     * @return string|false Contents or false on failure
     */
    public function getContents($stream);
}
