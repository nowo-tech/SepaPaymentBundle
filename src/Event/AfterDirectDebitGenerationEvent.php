<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched after Direct Debit XML generation.
 * Allows listeners to modify the generated XML.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class AfterDirectDebitGenerationEvent extends Event
{
    /**
     * Generated XML (can be modified by listeners).
     */
    private string $xml;

    /**
     * Message ID.
     */
    private string $messageId;

    /**
     * Constructor.
     *
     * @param string $xml The generated XML
     * @param string $messageId The message ID
     */
    public function __construct(string $xml, string $messageId)
    {
        $this->xml       = $xml;
        $this->messageId = $messageId;
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
