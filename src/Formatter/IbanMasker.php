<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Formatter;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

use function chunk_split;
use function preg_replace;
use function rtrim;
use function sprintf;
use function str_repeat;
use function strlen;
use function strtoupper;
use function substr;

/**
 * Masks IBANs for display, logs and CSV exports: the country code and check digits (4 characters) and at most the last
 * 4 characters stay visible, everything else becomes "*". Spaces, dashes and other separators are dropped first.
 * Anything shorter than 15 characters (the shortest IBAN, Norway) is fully masked, so a malformed value never leaks. Stateless (worker safe); usable without the container (`new IbanMasker()`).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
#[AsAlias(id: IbanMaskerInterface::class, public: true)]
final class IbanMasker implements IbanMaskerInterface
{
    public const SERVICE_NAME = 'nowo_sepa_payment.formatter.iban_masker';

    /** Visible leading characters (country code and check digits). */
    private const VISIBLE_START = 4;

    /** Largest number of visible trailing characters. */
    private const MAX_VISIBLE_END = 4;

    /** Shortest value that is masked partially: the shortest IBAN (NO, 15 characters). */
    private const MIN_PARTIAL_LENGTH = 15;

    public function mask(string $iban, int $visibleEnd = 4): string
    {
        if ($visibleEnd < 0 || $visibleEnd > self::MAX_VISIBLE_END) {
            throw new InvalidArgumentException(sprintf('Invalid number of visible IBAN characters %d: use 0 to %d.', $visibleEnd, self::MAX_VISIBLE_END));
        }

        $normalised = strtoupper((string) preg_replace('/[^A-Za-z0-9]+/', '', $iban));
        $length     = strlen($normalised);
        if ($length < self::MIN_PARTIAL_LENGTH) {
            return str_repeat('*', $length);
        }

        return substr($normalised, 0, self::VISIBLE_START)
            . str_repeat('*', $length - self::VISIBLE_START - $visibleEnd)
            . ($visibleEnd === 0 ? '' : substr($normalised, -$visibleEnd));
    }

    public function maskFormatted(string $iban, int $visibleEnd = 4): string
    {
        return rtrim(chunk_split($this->mask($iban, $visibleEnd), 4, ' '));
    }
}
