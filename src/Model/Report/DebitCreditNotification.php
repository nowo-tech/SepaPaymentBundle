<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\Report;

use DateTimeImmutable;
use Nowo\SepaPaymentBundle\Parser\DebitCreditNotificationParser;

/**
 * A bank-to-customer debit / credit notification (camt.054.001.02 or .08), as returned by
 * {@see DebitCreditNotificationParser}. Plain values only: the host matches the
 * entries and transactions to its own collections.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final readonly class DebitCreditNotification
{
    /**
     * Constructor.
     *
     * @param string $version The message version, e.g. "camt.054.001.08"
     * @param string $messageId The message id (`GrpHdr/MsgId`)
     * @param ?DateTimeImmutable $creationDateTime The creation date and time (`GrpHdr/CreDtTm`)
     * @param list<AccountNotification> $notifications The account notifications (`Ntfctn`)
     */
    public function __construct(
        private string $version,
        private string $messageId,
        private ?DateTimeImmutable $creationDateTime,
        private array $notifications,
    ) {
    }

    /**
     * The message version, e.g. "camt.054.001.08".
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * The message id (`GrpHdr/MsgId`).
     */
    public function getMessageId(): string
    {
        return $this->messageId;
    }

    /**
     * The creation date and time (`GrpHdr/CreDtTm`).
     */
    public function getCreationDateTime(): ?DateTimeImmutable
    {
        return $this->creationDateTime;
    }

    /**
     * The account notifications (`Ntfctn`).
     *
     * @return list<AccountNotification>
     */
    public function getNotifications(): array
    {
        return $this->notifications;
    }

    /**
     * Every transaction of every entry of every notification, in file order.
     *
     * @return list<NotificationTransaction>
     */
    public function getTransactions(): array
    {
        $transactions = [];
        foreach ($this->notifications as $notification) {
            foreach ($notification->getEntries() as $entry) {
                foreach ($entry->getTransactions() as $transaction) {
                    $transactions[] = $transaction;
                }
            }
        }

        return $transactions;
    }

    /**
     * Every transaction of a booked entry (`Ntry/Sts` BOOK), in file order. Reversals are included: check
     * {@see NotificationTransaction::isReversal()}. Booked entries without transaction details are only in
     * {@see self::getEntriesWithoutTransactions()}.
     *
     * @return list<NotificationTransaction>
     */
    public function getBookedTransactions(): array
    {
        $booked = [];
        foreach ($this->getTransactions() as $transaction) {
            if ($transaction->isBooked()) {
                $booked[] = $transaction;
            }
        }

        return $booked;
    }

    /**
     * Every entry without transaction details (`NtryDtls/TxDtls`), e.g. a batch booked as one amount: it moves money
     * that {@see self::getTransactions()} does not list.
     *
     * @return list<NotificationEntry>
     */
    public function getEntriesWithoutTransactions(): array
    {
        $entries = [];
        foreach ($this->notifications as $notification) {
            foreach ($notification->getEntries() as $entry) {
                if ($entry->getTransactions() === []) {
                    $entries[] = $entry;
                }
            }
        }

        return $entries;
    }
}
