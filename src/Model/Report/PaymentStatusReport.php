<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\Report;

use DateTimeImmutable;
use Nowo\SepaPaymentBundle\Parser\PaymentStatusReportParser;

/**
 * A customer payment status report (pain.002.001.03 or .10), as returned by
 * {@see PaymentStatusReportParser}. Plain values only: the host matches the statuses
 * to its own collections.
 *
 * A rejection can come at three levels, and the lower levels are often left out: a group RJCT ("total reject") may
 * carry no block or transaction at all, and a rejected block may list no transaction. Check
 * {@see self::isFullyRejected()} and {@see self::getRejectedPaymentInformationIds()} before the transaction lines.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final readonly class PaymentStatusReport
{
    /**
     * Constructor.
     *
     * @param string $version The message version, e.g. "pain.002.001.10"
     * @param string $messageId The report message id (`GrpHdr/MsgId`)
     * @param ?DateTimeImmutable $creationDateTime The report creation date and time (`GrpHdr/CreDtTm`)
     * @param string $originalMessageId The original message id (`OrgnlGrpInfAndSts/OrgnlMsgId`), i.e. your pain.008 / pain.001 MsgId
     * @param string $originalMessageNameId The original message name (`OrgnlMsgNmId`), e.g. "pain.008.001.08"
     * @param ?string $groupStatus The group status (`GrpSts`), e.g. "RJCT", "PART", "ACCP"
     * @param list<StatusReason> $groupReasons The group status reasons (`OrgnlGrpInfAndSts/StsRsnInf`)
     * @param list<PaymentInformationStatus> $paymentInformationStatuses The block statuses (`OrgnlPmtInfAndSts`)
     */
    public function __construct(
        private string $version,
        private string $messageId,
        private ?DateTimeImmutable $creationDateTime,
        private string $originalMessageId,
        private string $originalMessageNameId,
        private ?string $groupStatus,
        private array $groupReasons,
        private array $paymentInformationStatuses,
    ) {
    }

    /**
     * The message version, e.g. "pain.002.001.10".
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * The report message id (`GrpHdr/MsgId`).
     */
    public function getMessageId(): string
    {
        return $this->messageId;
    }

    /**
     * The report creation date and time (`GrpHdr/CreDtTm`).
     */
    public function getCreationDateTime(): ?DateTimeImmutable
    {
        return $this->creationDateTime;
    }

    /**
     * The original message id (`OrgnlGrpInfAndSts/OrgnlMsgId`), i.e. your pain.008 / pain.001 MsgId.
     */
    public function getOriginalMessageId(): string
    {
        return $this->originalMessageId;
    }

    /**
     * The original message name (`OrgnlMsgNmId`), e.g. "pain.008.001.08".
     */
    public function getOriginalMessageNameId(): string
    {
        return $this->originalMessageNameId;
    }

    /**
     * The group status (`GrpSts`), e.g. "RJCT", "PART", "ACCP".
     */
    public function getGroupStatus(): ?string
    {
        return $this->groupStatus;
    }

    /**
     * The group status reasons (`OrgnlGrpInfAndSts/StsRsnInf`).
     *
     * @return list<StatusReason>
     */
    public function getGroupReasons(): array
    {
        return $this->groupReasons;
    }

    /**
     * The block statuses (`OrgnlPmtInfAndSts`).
     *
     * @return list<PaymentInformationStatus>
     */
    public function getPaymentInformationStatuses(): array
    {
        return $this->paymentInformationStatuses;
    }

    /**
     * Every transaction status of every block, in file order.
     *
     * @return list<TransactionStatus>
     */
    public function getTransactionStatuses(): array
    {
        $transactions = [];
        foreach ($this->paymentInformationStatuses as $block) {
            foreach ($block->getTransactions() as $transaction) {
                $transactions[] = $transaction;
            }
        }

        return $transactions;
    }

    /**
     * Whether the whole original file was rejected (`GrpSts` RJCT): every collection of
     * {@see self::getOriginalMessageId()}, even when the report lists no block or transaction.
     */
    public function isFullyRejected(): bool
    {
        return $this->groupStatus === 'RJCT';
    }

    /**
     * The original PmtInfId of every rejected block ({@see PaymentInformationStatus::isRejected()}), in file order.
     *
     * @return list<string>
     */
    public function getRejectedPaymentInformationIds(): array
    {
        $ids = [];
        foreach ($this->paymentInformationStatuses as $block) {
            if ($block->isRejected()) {
                $ids[] = $block->getOriginalPaymentInformationId();
            }
        }

        return $ids;
    }

    /**
     * Every transaction rejected by its own, its block's or the group's status, in file order.
     *
     * @return list<TransactionStatus>
     */
    public function getRejectedTransactionStatuses(): array
    {
        $rejected = [];
        foreach ($this->getTransactionStatuses() as $transaction) {
            if ($transaction->isRejected()) {
                $rejected[] = $transaction;
            }
        }

        return $rejected;
    }

    /**
     * The original EndToEndId of every rejected transaction line (see {@see self::getRejectedTransactionStatuses()}).
     * Collections of a rejected group or block that the report does not list are not here: check
     * {@see self::isFullyRejected()} and {@see self::getRejectedPaymentInformationIds()} too.
     *
     * @return list<string>
     */
    public function getRejectedEndToEndIds(): array
    {
        $ids = [];
        foreach ($this->getRejectedTransactionStatuses() as $transaction) {
            if ($transaction->getOriginalEndToEndId() !== null) {
                $ids[] = $transaction->getOriginalEndToEndId();
            }
        }

        return $ids;
    }
}
