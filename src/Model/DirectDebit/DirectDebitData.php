<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\DirectDebit;

/**
 * Direct Debit data container.
 * Contains all information needed to generate a SEPA Direct Debit.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class DirectDebitData
{
    /**
     * Creditor BIC (optional).
     *
     * @var string|null
     */
    private ?string $creditorBic = null;

    /**
     * Creditor address (optional, stored internally, not included in XML).
     *
     * @var array<string, string|null>|null
     */
    private ?array $creditorAddress = null;

    /**
     * List of transactions.
     *
     * @var array<int, DirectDebitTransaction>
     */
    private array $transactions = [];

    /**
     * Constructor.
     *
     * @param string             $messageId           Message identifier
     * @param string             $initiatingPartyName Initiating party name
     * @param string             $paymentInfoId       Payment information identifier
     * @param \DateTimeInterface $dueDate             Due date
     * @param string             $creditorName        Creditor name
     * @param string             $creditorIban        Creditor IBAN
     * @param string             $sequenceType        Sequence type (FRST, RCUR, OOFF, FNAL)
     * @param string             $creditorId          Creditor identifier (SEPA identifier)
     * @param string             $localInstrumentCode Local instrument code (CORE, B2B)
     */
    public function __construct(
        private string $messageId,
        private string $initiatingPartyName,
        private string $paymentInfoId,
        private \DateTimeInterface $dueDate,
        private string $creditorName,
        private string $creditorIban,
        private string $sequenceType,
        private string $creditorId,
        private string $localInstrumentCode
    ) {
    }

    /**
     * Gets the message identifier.
     *
     * @return string The message identifier
     */
    public function getMessageId(): string
    {
        return $this->messageId;
    }

    /**
     * Gets the initiating party name.
     *
     * @return string The initiating party name
     */
    public function getInitiatingPartyName(): string
    {
        return $this->initiatingPartyName;
    }

    /**
     * Gets the payment information identifier.
     *
     * @return string The payment information identifier
     */
    public function getPaymentInfoId(): string
    {
        return $this->paymentInfoId;
    }

    /**
     * Gets the due date.
     *
     * @return \DateTimeInterface The due date
     */
    public function getDueDate(): \DateTimeInterface
    {
        return $this->dueDate;
    }

    /**
     * Gets the creditor name.
     *
     * @return string The creditor name
     */
    public function getCreditorName(): string
    {
        return $this->creditorName;
    }

    /**
     * Gets the creditor IBAN.
     *
     * @return string The creditor IBAN
     */
    public function getCreditorIban(): string
    {
        return $this->creditorIban;
    }

    /**
     * Sets the creditor BIC.
     *
     * @param string|null $creditorBic The creditor BIC
     *
     * @return self
     */
    public function setCreditorBic(?string $creditorBic): self
    {
        $this->creditorBic = $creditorBic;

        return $this;
    }

    /**
     * Gets the creditor BIC.
     *
     * @return string|null The creditor BIC
     */
    public function getCreditorBic(): ?string
    {
        return $this->creditorBic;
    }

    /**
     * Gets the sequence type.
     *
     * @return string The sequence type
     */
    public function getSequenceType(): string
    {
        return $this->sequenceType;
    }

    /**
     * Gets the creditor identifier.
     *
     * @return string The creditor identifier
     */
    public function getCreditorId(): string
    {
        return $this->creditorId;
    }

    /**
     * Gets the local instrument code.
     *
     * @return string The local instrument code
     */
    public function getLocalInstrumentCode(): string
    {
        return $this->localInstrumentCode;
    }

    /**
     * Adds a transaction.
     *
     * @param DirectDebitTransaction $transaction The transaction to add
     *
     * @return self
     */
    public function addTransaction(DirectDebitTransaction $transaction): self
    {
        $this->transactions[] = $transaction;

        return $this;
    }

    /**
     * Gets all transactions.
     *
     * @return array<int, DirectDebitTransaction> The transactions
     */
    public function getTransactions(): array
    {
        return $this->transactions;
    }

    /**
     * Calculates the total amount of all transactions.
     *
     * @return float The total amount
     */
    public function getTotalAmount(): float
    {
        $total = 0.0;
        foreach ($this->transactions as $transaction) {
            $total += $transaction->getAmount();
        }

        return $total;
    }

    /**
     * Sets the creditor address.
     * Address will be included in the generated XML (as of v0.0.8).
     *
     * @param array<string, string|null>|string|null $street     Address array or street address
     * @param string|null                            $city       City (ignored if first param is array)
     * @param string|null                            $postalCode Postal code (ignored if first param is array)
     * @param string|null                            $country    Country code (ignored if first param is array)
     *
     * @return self
     */
    public function setCreditorAddress(array|string|null $street = null, ?string $city = null, ?string $postalCode = null, ?string $country = null): self
    {
        if (is_array($street)) {
            return $this->setCreditorAddressFromArray($street);
        }

        $this->creditorAddress = [
            'street' => $street,
            'city' => $city,
            'postalCode' => $postalCode,
            'country' => $country,
        ];

        return $this;
    }

    /**
     * Sets the creditor address from array.
     *
     * @param array<string, string|null> $address Address array with keys: street, city, postalCode, country
     *
     * @return self
     */
    public function setCreditorAddressFromArray(array $address): self
    {
        $this->creditorAddress = [
            'street' => $address['street'] ?? $address['address'] ?? null,
            'city' => $address['city'] ?? null,
            'postalCode' => $address['postalCode'] ?? $address['postal_code'] ?? null,
            'country' => $address['country'] ?? null,
        ];

        return $this;
    }

    /**
     * Gets the creditor address.
     *
     * @return array<string, string|null>|null The creditor address
     */
    public function getCreditorAddress(): ?array
    {
        return $this->creditorAddress;
    }
}
