<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\DirectDebit;

use InvalidArgumentException;

use function implode;
use function in_array;
use function sprintf;

/**
 * SEPA direct debit sequence types (ISO 20022 SequenceType3Code) accepted by the new 2.2 APIs.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class SequenceType
{
    public const FIRST = 'FRST';

    public const RECURRENT = 'RCUR';

    public const ONE_OFF = 'OOFF';

    public const FINAL = 'FNAL';

    /** @var list<string> */
    public const ALL = [self::FIRST, self::RECURRENT, self::ONE_OFF, self::FINAL];

    private function __construct()
    {
    }

    /**
     * Checks a sequence type code.
     *
     * @param string $sequenceType The code (FRST, RCUR, OOFF or FNAL)
     * @param string $context Where the code comes from (used in the error message)
     *
     * @throws InvalidArgumentException If the code is not one of FRST, RCUR, OOFF, FNAL
     */
    public static function assertValid(string $sequenceType, string $context): string
    {
        if (!in_array($sequenceType, self::ALL, true)) {
            throw new InvalidArgumentException(sprintf('Invalid sequence type "%s" for %s: use one of %s.', $sequenceType, $context, implode(', ', self::ALL)));
        }

        return $sequenceType;
    }
}
