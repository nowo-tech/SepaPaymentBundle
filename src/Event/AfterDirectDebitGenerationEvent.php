<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched after Direct Debit XML generation.
 * Allows listeners to modify the generated XML.
 *
 * It carries ids and plain values only (message id, payment information ids, version, count, control sum). The bundle
 * never persists anything: the event fires inside `generate()`, before the host stores or sends the file, so a listener
 * must not assume the remittance is saved (dispatch your own event after your commit for that).
 *
 * Still dispatched exactly as before; the framework-free twin {@see Generation\DirectDebitGeneratedEvent} follows it.
 *
 * @deprecated since 2.4.0, removed in 3.0: listen to {@see Generation\DirectDebitGeneratedEvent} (no Symfony base class)
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class AfterDirectDebitGenerationEvent extends Event
{
    /**
     * Constructor.
     *
     * @param string $xml The generated XML
     * @param string $messageId The message ID
     * @param list<string> $paymentInformationIds The PmtInfId of every written block, in file order (since 2.2)
     * @param string|null $painVersion The written message version, e.g. "pain.008.001.09" (since 2.2)
     * @param int $numberOfTransactions The number of written transactions (since 2.2)
     * @param int $controlSumCents The control sum in cents (since 2.2)
     */
    public function __construct(
        /**
         * Generated XML (can be modified by listeners).
         */
        private string $xml,
        /**
         * Message ID.
         */
        private readonly string $messageId,
        private readonly array $paymentInformationIds = [],
        private readonly ?string $painVersion = null,
        private readonly int $numberOfTransactions = 0,
        private readonly int $controlSumCents = 0,
    ) {
    }

    /**
     * Gets the PmtInfId of every written block, in file order.
     *
     * @return list<string>
     */
    public function getPaymentInformationIds(): array
    {
        return $this->paymentInformationIds;
    }

    /**
     * Gets the written message version (e.g. "pain.008.001.09").
     */
    public function getPainVersion(): ?string
    {
        return $this->painVersion;
    }

    /**
     * Gets the number of written transactions.
     */
    public function getNumberOfTransactions(): int
    {
        return $this->numberOfTransactions;
    }

    /**
     * Gets the control sum in cents.
     */
    public function getControlSumCents(): int
    {
        return $this->controlSumCents;
    }

    /**
     * Gets the generated XML.
     *
     * @return string The generated XML
     */
    public function getXml(): string
    {
        return $this->xml;
    }

    /**
     * Sets the generated XML.
     *
     * @param string $xml The generated XML
     */
    public function setXml(string $xml): void
    {
        // @igor-ignore - Request-scoped Event DTO; not a shared worker service.
        $this->xml = $xml;
    }

    /**
     * Gets the message ID.
     *
     * @return string The message ID
     */
    public function getMessageId(): string
    {
        return $this->messageId;
    }
}
