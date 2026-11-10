<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\Report;

use Nowo\SepaPaymentBundle\Model\ReasonCode;

/**
 * A status or return reason of a bank report (`StsRsnInf` in pain.002, `RtrInf` in camt.054): the ISO code
 * (`Rsn/Cd`) or a proprietary one (`Rsn/Prtry`), plus the additional information lines.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final readonly class StatusReason
{
    /**
     * Constructor.
     *
     * @param ?string $code The ISO 20022 reason code as found in the file (`Rsn/Cd`), e.g. "AM04"
     * @param ?string $proprietaryCode The proprietary reason (`Rsn/Prtry`)
     * @param ?string $originatorName The name of the party that issued the reason (`Orgtr/Nm`)
     * @param list<string> $additionalInformation The additional information lines (`AddtlInf`)
     */
    public function __construct(
        private ?string $code,
        private ?string $proprietaryCode,
        private ?string $originatorName,
        private array $additionalInformation,
    ) {
    }

    /**
     * The ISO 20022 reason code as found in the file (`Rsn/Cd`), e.g. "AM04".
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * The proprietary reason (`Rsn/Prtry`).
     */
    public function getProprietaryCode(): ?string
    {
        return $this->proprietaryCode;
    }

    /**
     * The name of the party that issued the reason (`Orgtr/Nm`).
     */
    public function getOriginatorName(): ?string
    {
        return $this->originatorName;
    }

    /**
     * The additional information lines (`AddtlInf`).
     *
     * @return list<string>
     */
    public function getAdditionalInformation(): array
    {
        return $this->additionalInformation;
    }

    /**
     * The known reason code ({@see ReasonCode}), or null when there is none or it is not in the enum.
     */
    public function getReasonCode(): ?ReasonCode
    {
        return $this->code === null ? null : ReasonCode::fromCode($this->code);
    }
}
