<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\Mandate;

use InvalidArgumentException;

use function mb_strlen;
use function preg_match;
use function sprintf;

/**
 * Amendment of a mandate since the last collection (EPC SDD "amendment information details"): the original mandate id,
 * original creditor scheme id / name, original debtor IBAN (account change within the same bank) or a change of debtor
 * bank ("SMNDA", same mandate with a new debtor agent; the next collection must be FRST). Every part is optional, at
 * least one is required. Written in `MndtRltdInf/AmdmntInfDtls` by the direct debit generator.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final readonly class MandateAmendment
{
    /**
     * Constructor.
     *
     * @param string|null $originalMandateId The mandate id before the change (`OrgnlMndtId`, max. 35)
     * @param string|null $originalCreditorSchemeId The creditor identifier before the change (`OrgnlCdtrSchmeId/Id`, max. 35)
     * @param string|null $originalCreditorName The creditor name before the change (`OrgnlCdtrSchmeId/Nm`, max. 70)
     * @param string|null $originalDebtorIban The debtor IBAN before the change, same bank (`OrgnlDbtrAcct/Id/IBAN`)
     * @param bool $newDebtorAgent The debtor moved to another bank (`OrgnlDbtrAgt/FinInstnId/Othr/Id` "SMNDA")
     *
     * @throws InvalidArgumentException If nothing is amended or a value is malformed
     */
    public function __construct(
        private ?string $originalMandateId = null,
        private ?string $originalCreditorSchemeId = null,
        private ?string $originalCreditorName = null,
        private ?string $originalDebtorIban = null,
        private bool $newDebtorAgent = false,
    ) {
        if ($originalMandateId === null && $originalCreditorSchemeId === null && $originalCreditorName === null && $originalDebtorIban === null && !$newDebtorAgent) {
            throw new InvalidArgumentException('A mandate amendment needs at least one changed value.');
        }
        foreach ([['OrgnlMndtId', $originalMandateId, 35], ['OrgnlCdtrSchmeId/Id', $originalCreditorSchemeId, 35], ['OrgnlCdtrSchmeId/Nm', $originalCreditorName, 70]] as [$field, $value, $max]) {
            if ($value !== null && ($value === '' || preg_match('//u', $value) !== 1 || mb_strlen($value, 'UTF-8') > $max)) {
                throw new InvalidArgumentException(sprintf('Invalid %s: use 1 to %d characters of valid UTF-8.', $field, $max));
            }
        }
        if ($originalDebtorIban !== null && preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}$/D', $originalDebtorIban) !== 1) {
            throw new InvalidArgumentException('Invalid original debtor IBAN: use the normalised IBAN (uppercase, no spaces).');
        }
        if ($originalDebtorIban !== null && $newDebtorAgent) {
            throw new InvalidArgumentException('Use either the original debtor IBAN (account change in the same bank) or a new debtor agent (SMNDA), not both.');
        }
    }

    public function getOriginalMandateId(): ?string
    {
        return $this->originalMandateId;
    }

    public function getOriginalCreditorSchemeId(): ?string
    {
        return $this->originalCreditorSchemeId;
    }

    public function getOriginalCreditorName(): ?string
    {
        return $this->originalCreditorName;
    }

    public function getOriginalDebtorIban(): ?string
    {
        return $this->originalDebtorIban;
    }

    public function isNewDebtorAgent(): bool
    {
        return $this->newDebtorAgent;
    }

    /**
     * Plain array for storage; see {@see self::fromArray()}.
     *
     * @return array{originalMandateId: ?string, originalCreditorSchemeId: ?string, originalCreditorName: ?string, originalDebtorIban: ?string, newDebtorAgent: bool}
     */
    public function toArray(): array
    {
        return [
            'originalMandateId'        => $this->originalMandateId,
            'originalCreditorSchemeId' => $this->originalCreditorSchemeId,
            'originalCreditorName'     => $this->originalCreditorName,
            'originalDebtorIban'       => $this->originalDebtorIban,
            'newDebtorAgent'           => $this->newDebtorAgent,
        ];
    }

    /**
     * Rebuilds the amendment from {@see self::toArray()}.
     *
     * @param array{originalMandateId?: ?string, originalCreditorSchemeId?: ?string, originalCreditorName?: ?string, originalDebtorIban?: ?string, newDebtorAgent?: bool} $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['originalMandateId'] ?? null, $data['originalCreditorSchemeId'] ?? null, $data['originalCreditorName'] ?? null, $data['originalDebtorIban'] ?? null, $data['newDebtorAgent'] ?? false);
    }
}
