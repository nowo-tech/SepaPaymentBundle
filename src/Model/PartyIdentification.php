<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model;

use InvalidArgumentException;

use function preg_match;
use function sprintf;
use function strlen;

/**
 * Identification of a party as an ISO 20022 generic identification (`Id/OrgId/Othr` or `Id/PrvtId/Othr`), e.g. the
 * initiating party (`GrpHdr/InitgPty/Id`) that Spanish banks require: the creditor's NIF-based id, without a scheme or
 * with a proprietary scheme name.
 *
 * Written as:
 *
 *     <Id><OrgId|PrvtId><Othr><Id>…</Id>[<SchmeNm><Cd|Prtry>…</Cd|Prtry></SchmeNm>][<Issr>…</Issr>]</Othr></OrgId|PrvtId></Id>
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class PartyIdentification
{
    /**
     * Longest identifier (`Othr/Id`, Max35Text).
     */
    public const MAX_ID_LENGTH = 35;

    /**
     * Constructor (use {@see self::organisation()} or {@see self::privatePerson()}).
     *
     * @param string $id The identifier (`Othr/Id`, 1 to 35 characters)
     * @param bool $organisation True for `OrgId`, false for `PrvtId`
     * @param string|null $schemeCode External scheme code (`SchmeNm/Cd`, 1 to 4 characters, e.g. "TXID", "NIDN")
     * @param string|null $schemeProprietary Proprietary scheme name (`SchmeNm/Prtry`, 1 to 35 characters, e.g. "SEPA")
     * @param string|null $issuer Issuer (`Issr`, 1 to 35 characters)
     *
     * @throws InvalidArgumentException If a value is empty or too long, or both scheme forms are given
     */
    private function __construct(
        private readonly string $id,
        private readonly bool $organisation,
        private readonly ?string $schemeCode,
        private readonly ?string $schemeProprietary,
        private readonly ?string $issuer,
    ) {
        $this->assertText('Othr/Id', $id, self::MAX_ID_LENGTH);
        if ($schemeCode !== null && $schemeProprietary !== null) {
            throw new InvalidArgumentException('A party identification has either a scheme code (SchmeNm/Cd) or a proprietary scheme name (SchmeNm/Prtry), not both.');
        }
        if ($schemeCode !== null && preg_match('/^[A-Z0-9]{1,4}$/D', $schemeCode) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid scheme code "%s": use 1 to 4 uppercase letters or digits (ISO 20022 external code, e.g. "TXID").', $schemeCode));
        }
        if ($schemeProprietary !== null) {
            $this->assertText('SchmeNm/Prtry', $schemeProprietary, 35);
        }
        if ($issuer !== null) {
            $this->assertText('Issr', $issuer, 35);
        }
    }

    /**
     * An organisation identification (`OrgId/Othr`).
     *
     * @param string $id The identifier (e.g. the creditor identifier or the NIF with its suffix)
     * @param string|null $schemeProprietary Proprietary scheme name (`SchmeNm/Prtry`), e.g. "SEPA"
     * @param string|null $schemeCode External scheme code (`SchmeNm/Cd`), e.g. "TXID"
     * @param string|null $issuer Issuer (`Issr`)
     */
    public static function organisation(string $id, ?string $schemeProprietary = null, ?string $schemeCode = null, ?string $issuer = null): self
    {
        return new self($id, true, $schemeCode, $schemeProprietary, $issuer);
    }

    /**
     * A private person identification (`PrvtId/Othr`).
     *
     * @param string $id The identifier (e.g. a national identity number)
     * @param string|null $schemeProprietary Proprietary scheme name (`SchmeNm/Prtry`)
     * @param string|null $schemeCode External scheme code (`SchmeNm/Cd`), e.g. "NIDN"
     * @param string|null $issuer Issuer (`Issr`)
     */
    public static function privatePerson(string $id, ?string $schemeProprietary = null, ?string $schemeCode = null, ?string $issuer = null): self
    {
        return new self($id, false, $schemeCode, $schemeProprietary, $issuer);
    }

    /**
     * Gets the identifier (`Othr/Id`).
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Whether this is an organisation identification (`OrgId`); false for a private one (`PrvtId`).
     */
    public function isOrganisation(): bool
    {
        return $this->organisation;
    }

    /**
     * Gets the external scheme code (`SchmeNm/Cd`).
     */
    public function getSchemeCode(): ?string
    {
        return $this->schemeCode;
    }

    /**
     * Gets the proprietary scheme name (`SchmeNm/Prtry`).
     */
    public function getSchemeProprietary(): ?string
    {
        return $this->schemeProprietary;
    }

    /**
     * Gets the issuer (`Issr`).
     */
    public function getIssuer(): ?string
    {
        return $this->issuer;
    }

    /**
     * Checks a Max35Text value.
     *
     * @throws InvalidArgumentException If the value is empty or longer than $max characters
     */
    private function assertText(string $field, string $value, int $max): void
    {
        if ($value === '' || strlen($value) > $max) {
            throw new InvalidArgumentException(sprintf('Invalid %s "%s": use 1 to %d characters.', $field, $value, $max));
        }
    }
}
