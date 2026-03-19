<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Command;

/**
 * Stream wrapper that makes file_get_contents() return false.
 * url_stat() returns a file-like stat so file_exists/is_file/is_readable pass;
 * stream_open() returns false so file_get_contents fails.
 *
 * @internal
 */
class ReadFailStreamWrapper
{
    /** @var resource|null */
    public $context;

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        return false;
    }

    /**
     * @return array<int|string, int>|false
     */
    public function url_stat(string $path, int $flags): array|false
    {
        return [
            0         => 0,
            1         => 0,
            2         => 0100644,
            3         => 0,
            4         => 0,
            5         => 0,
            6         => 0,
            7         => 0,
            8         => 0,
            9         => 0,
            10        => 0,
            11        => 0,
            12        => 0,
            'dev'     => 0,
            'ino'     => 0,
            'mode'    => 0100644,
            'nlink'   => 0,
            'uid'     => 0,
            'gid'     => 0,
            'rdev'    => 0,
            'size'    => 0,
            'atime'   => 0,
            'mtime'   => 0,
            'ctime'   => 0,
            'blksize' => 0,
            'blocks'  => 0,
        ];
    }
}
