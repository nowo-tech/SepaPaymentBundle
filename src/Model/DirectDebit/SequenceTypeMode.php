<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\DirectDebit;

use InvalidArgumentException;

use function array_map;
use function implode;
use function sprintf;

/**
 * How the direct debit generator writes the sequence type (`PmtInf/PmtTpInf/SeqTp`).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
enum SequenceTypeMode: string
{
    /**
     * The sequence type comes from the data (the default, as in 2.1): the payment-information block's sequence type,
     * or the transaction's own one ({@see DirectDebitTransaction::setSequenceType()}). A block whose transactions carry
     * different sequence types is written as one `PmtInf` per sequence type (`<PmtInfId>-FRST`, `<PmtInfId>-RCUR`).
     */
    case PER_TRANSACTION = 'per_transaction';

    /** Every block is written with `SeqTp` RCUR; the sequence types in the data are ignored. */
    case ALWAYS_RCUR = 'always_rcur';

    /** No `SeqTp` element is written (it is optional in the ISO 20022 schemas; ask your bank first). */
    case OMIT = 'omit';

    /** The default mode (also the default of the `direct_debit.sequence_type_mode` option). */
    public const DEFAULT = self::PER_TRANSACTION;

    /**
     * The mode for a value such as "omit".
     *
     * @throws InvalidArgumentException If the mode is not supported
     */
    public static function fromValue(self|string $mode): self
    {
        if ($mode instanceof self) {
            return $mode;
        }

        return self::tryFrom($mode) ?? throw new InvalidArgumentException(sprintf('Unsupported sequence type mode "%s": use one of %s.', $mode, implode(', ', self::values())));
    }

    /**
     * All supported values.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $mode): string => $mode->value, self::cases());
    }
}
