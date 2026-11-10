<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\DirectDebit;

use InvalidArgumentException;

use function array_map;
use function implode;
use function sprintf;

/**
 * ISO 20022 customer direct debit initiation (pain.008) message versions the generator can write.
 *
 * Every version is validated against the official ISO 20022 schema shipped in `src/Resources/schemas`
 * (see the NOTICE file there). `pain.008.001.09` is the default, as in 2.1.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
enum Pain008Version: string
{
    /** CustomerDirectDebitInitiationV02 (2009), the EPC rulebook format before November 2023. */
    case V02 = 'pain.008.001.02';

    /** CustomerDirectDebitInitiationV08 (2019), the EPC 2023 implementation guidelines format. */
    case V08 = 'pain.008.001.08';

    /** CustomerDirectDebitInitiationV09 (2020), the bundle default. */
    case V09 = 'pain.008.001.09';

    /** The default version (also the default of the `direct_debit.pain_version` option). */
    public const DEFAULT = self::V09;

    /**
     * The version for a value such as "pain.008.001.08".
     *
     * @throws InvalidArgumentException If the version is not supported
     */
    public static function fromValue(self|string $version): self
    {
        if ($version instanceof self) {
            return $version;
        }

        return self::tryFrom($version) ?? throw new InvalidArgumentException(sprintf('Unsupported pain.008 version "%s": use one of %s.', $version, implode(', ', self::values())));
    }

    /**
     * All supported values, oldest first.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $version): string => $version->value, self::cases());
    }

    /**
     * The XML namespace of the version (urn:iso:std:iso:20022:tech:xsd:pain.008.001.xx).
     */
    public function getNamespace(): string
    {
        return 'urn:iso:std:iso:20022:tech:xsd:' . $this->value;
    }
}
