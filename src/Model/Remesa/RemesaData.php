<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\Remesa;

/**
 * Credit Transfer data container.
 * Contains all information needed to generate a SEPA Credit Transfer.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class RemesaData
{
    /**
     * Creditor BIC (optional).
     *
     * @var string|null
     */
    private ?string $creditorBic = null;

    /**
     * Creditor address (optional, included in XML if provided).
     *
     * @var array<string, string|null>|null
     */
    private ?array $creditorAddress = null;

    /**
     * Whether batch booking is enabled.
     *
     * @var bool
     */
    private bool $batchBooking = false;

    /**
     * List of transactions.
     *
     * @var array<int, Transaction>
     */
    private array $transactions = [];

    /**
     * Constructor.
     *
     * @param string             $messageId              Message identifier
     * @param \DateTimeInterface $creationDate           Creation date
     * @param string             $initiatingPartyName    Initiating party name
     * @param string             $paymentInfoId          Payment information identifier
     * @param string             $creditorIban           Creditor IBAN
     * @param string             $creditorName           Creditor name
     * @param \DateTimeInterface $requestedExecutionDate Requested execution date
     */
    public function __construct(
        private string $messageId,
        private \DateTimeInterface $creationDate,
        private string $initiatingPartyName,
        private string $paymentInfoId,
        private string $creditorIban,
        private string $creditorName,
        private \DateTimeInterface $requestedExecutionDate
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
     * Gets the creation date.
     *
     * @return \DateTimeInterface The creation date
     */
    public function getCreationDate(): \DateTimeInterface
    {
        return $this->creationDate;
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
     * Gets the creditor name.
     *
     * @return string The creditor name
     */
    public function getCreditorName(): string
    {
        return $this->creditorName;
    }

    /**
     * Gets the requested execution date.
     *
     * @return \DateTimeInterface The requested execution date
     */
    public function getRequestedExecutionDate(): \DateTimeInterface
    {
        return $this->requestedExecutionDate;
    }

    /**
     * Sets whether batch booking is enabled.
     *
     * @param bool $batchBooking Whether batch booking is enabled
     *
     * @return self
     */
    public function setBatchBooking(bool $batchBooking): self
    {
        $this->batchBooking = $batchBooking;

        return $this;
    }

    /**
     * Checks if batch booking is enabled.
     *
     * @return bool True if batch booking is enabled, false otherwise
     */
    public function isBatchBooking(): bool
    {
        return $this->batchBooking;
    }

    /**
     * Adds a transaction.
     *
     * @param Transaction $transaction The transaction to add
     *
     * @return self
     */
    public function addTransaction(Transaction $transaction): self
    {
        $this->transactions[] = $transaction;

        return $this;
    }

    /**
     * Gets all transactions.
     *
     * @return array<int, Transaction> The transactions
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
     * Address will be included in the generated XML.
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
