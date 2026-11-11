<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\Mandate;

use DateTimeImmutable;
use InvalidArgumentException;

use function is_string;
use function sprintf;
use function trim;

/**
 * Strict date parsing for stored mandate rows ({@see MandateSnapshot::fromArray()}, {@see MandateEvidence::fromArray()}).
 * Only absolute formats are accepted: ATOM ("2026-01-15T10:00:00+01:00", optionally with microseconds), "Y-m-d H:i:s"
 * and "Y-m-d". Relative values ("now", "tomorrow", "@0", "") are refused, so a damaged row never becomes the current
 * time.
 *
 * @internal
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class SnapshotDate
{
    /** Accepted formats, tried in order ("!" resets the fields the format does not set). */
    private const FORMATS = ['!Y-m-d\TH:i:sP', '!Y-m-d\TH:i:s.uP', '!Y-m-d H:i:s', '!Y-m-d H:i:s.u', '!Y-m-d'];

    private function __construct()
    {
    }

    /**
     * Parses a required date.
     *
     * @param string $field What the value is (used in the error message)
     *
     * @throws InvalidArgumentException If the value is missing, empty or not an absolute date
     */
    public static function required(mixed $value, string $field): DateTimeImmutable
    {
        return self::optional($value, $field) ?? throw new InvalidArgumentException(sprintf('Invalid %s: it is missing or empty.', $field));
    }

    /**
     * Parses an optional date: null (or a missing value) gives null.
     *
     * @param string $field What the value is (used in the error message)
     *
     * @throws InvalidArgumentException If the value is not null and not an absolute date (an empty string included)
     */
    public static function optional(mixed $value, string $field): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }
        if (!is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException(sprintf('Invalid %s: it is missing or empty.', $field));
        }
        $trimmed = trim($value);
        foreach (self::FORMATS as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $trimmed);
            if ($date === false) {
                continue;
            }
            $errors = DateTimeImmutable::getLastErrors();
            if ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0)) {
                return $date;
            }
        }

        throw new InvalidArgumentException(sprintf('Invalid %s "%s": use an absolute date such as "2026-01-15T10:00:00+01:00" or "2026-01-15".', $field, $trimmed));
    }
}
