<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\Report;

use DateTimeImmutable;

/**
 * One account notification of a camt.054 message (`Ntfctn`).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final readonly class AccountNotification
{
    /**
     * Constructor.
     *
     * @param string $id The notification id (`Id`)
     * @param ?DateTimeImmutable $creationDateTime The notification creation date and time (`CreDtTm`)
     * @param ?string $accountIban The account IBAN (`Acct/Id/IBAN`)
     * @param list<NotificationEntry> $entries The entries (`Ntry`)
     */
    public function __construct(
        private string $id,
        private ?DateTimeImmutable $creationDateTime,
        private ?string $accountIban,
        private array $entries,
    ) {
    }

    /**
     * The notification id (`Id`).
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * The notification creation date and time (`CreDtTm`).
     */
    public function getCreationDateTime(): ?DateTimeImmutable
    {
        return $this->creationDateTime;
    }

    /**
     * The account IBAN (`Acct/Id/IBAN`).
     */
    public function getAccountIban(): ?string
    {
        return $this->accountIban;
    }

    /**
     * The entries (`Ntry`).
     *
     * @return list<NotificationEntry>
     */
    public function getEntries(): array
    {
        return $this->entries;
    }
}
