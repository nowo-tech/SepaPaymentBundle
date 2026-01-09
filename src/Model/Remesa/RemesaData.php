<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\Remesa;

use Nowo\SepaPaymentBundle\Model\CreditTransfer\CreditTransferData;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\Transaction as CreditTransferTransaction;

/**
 * Remesa data container (deprecated).
 *
 * @deprecated Since 1.1.0, use CreditTransferData instead. This class will be removed in 2.0.0.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class RemesaData
{
    /**
     * Credit transfer data instance.
     *
     * @var CreditTransferData
     */
    private CreditTransferData $creditTransferData;

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
        string $messageId,
        \DateTimeInterface $creationDate,
        string $initiatingPartyName,
        string $paymentInfoId,
        string $creditorIban,
        string $creditorName,
        \DateTimeInterface $requestedExecutionDate
    ) {
        @trigger_error('RemesaData is deprecated since 1.1.0. Use CreditTransferData instead.', \E_USER_DEPRECATED);

        $this->creditTransferData = new CreditTransferData(
            $messageId,
            $creationDate,
            $initiatingPartyName,
            $paymentInfoId,
            $creditorIban,
            $creditorName,
            $requestedExecutionDate
        );
    }

    /**
     * Gets the message identifier.
     *
     * @return string The message identifier
     */
    public function getMessageId(): string
    {
        return $this->creditTransferData->getMessageId();
    }

    /**
     * Gets the creation date.
     *
     * @return \DateTimeInterface The creation date
     */
    public function getCreationDate(): \DateTimeInterface
    {
        return $this->creditTransferData->getCreationDate();
    }

    /**
     * Gets the initiating party name.
     *
     * @return string The initiating party name
     */
    public function getInitiatingPartyName(): string
    {
        return $this->creditTransferData->getInitiatingPartyName();
    }

    /**
     * Gets the payment information identifier.
     *
     * @return string The payment information identifier
     */
    public function getPaymentInfoId(): string
    {
        return $this->creditTransferData->getPaymentInfoId();
    }

    /**
     * Gets the creditor IBAN.
     *
     * @return string The creditor IBAN
     */
    public function getCreditorIban(): string
    {
        return $this->creditTransferData->getCreditorIban();
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
        $this->creditTransferData->setCreditorBic($creditorBic);

        return $this;
    }

    /**
     * Gets the creditor BIC.
     *
     * @return string|null The creditor BIC
     */
    public function getCreditorBic(): ?string
    {
        return $this->creditTransferData->getCreditorBic();
    }

    /**
     * Gets the creditor name.
     *
     * @return string The creditor name
     */
    public function getCreditorName(): string
    {
        return $this->creditTransferData->getCreditorName();
    }

    /**
     * Gets the requested execution date.
     *
     * @return \DateTimeInterface The requested execution date
     */
    public function getRequestedExecutionDate(): \DateTimeInterface
    {
        return $this->creditTransferData->getRequestedExecutionDate();
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
        $this->creditTransferData->setBatchBooking($batchBooking);

        return $this;
    }

    /**
     * Checks if batch booking is enabled.
     *
     * @return bool True if batch booking is enabled, false otherwise
     */
    public function isBatchBooking(): bool
    {
        return $this->creditTransferData->isBatchBooking();
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
        // Convert Transaction to CreditTransferTransaction
        $creditTransferTransaction = new CreditTransferTransaction(
            $transaction->getEndToEndId(),
            $transaction->getAmount(),
            $transaction->getCurrency(),
            $transaction->getDebtorIban(),
            $transaction->getDebtorName()
        );

        if ($transaction->getDebtorBic() !== null) {
            $creditTransferTransaction->setDebtorBic($transaction->getDebtorBic());
        }

        if ($transaction->getRemittanceInformation() !== null) {
            $creditTransferTransaction->setRemittanceInformation($transaction->getRemittanceInformation());
        }

        if ($transaction->getDebtorAddress() !== null) {
            $creditTransferTransaction->setDebtorAddressFromArray($transaction->getDebtorAddress());
        }

        $this->creditTransferData->addTransaction($creditTransferTransaction);

        return $this;
    }

    /**
     * Gets all transactions.
     *
     * @return array<int, Transaction> The transactions
     */
    public function getTransactions(): array
    {
        $transactions = [];
        foreach ($this->creditTransferData->getTransactions() as $creditTransferTransaction) {
            $transaction = new Transaction(
                $creditTransferTransaction->getEndToEndId(),
                $creditTransferTransaction->getAmount(),
                $creditTransferTransaction->getCurrency(),
                $creditTransferTransaction->getDebtorIban(),
                $creditTransferTransaction->getDebtorName()
            );

            if ($creditTransferTransaction->getDebtorBic() !== null) {
                $transaction->setDebtorBic($creditTransferTransaction->getDebtorBic());
            }

            if ($creditTransferTransaction->getRemittanceInformation() !== null) {
                $transaction->setRemittanceInformation($creditTransferTransaction->getRemittanceInformation());
            }

            if ($creditTransferTransaction->getDebtorAddress() !== null) {
                $transaction->setDebtorAddressFromArray($creditTransferTransaction->getDebtorAddress());
            }

            $transactions[] = $transaction;
        }

        return $transactions;
    }

    /**
     * Calculates the total amount of all transactions.
     *
     * @return float The total amount
     */
    public function getTotalAmount(): float
    {
        return $this->creditTransferData->getTotalAmount();
    }

    /**
     * Sets the creditor address.
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
        $this->creditTransferData->setCreditorAddress($street, $city, $postalCode, $country);

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
        $this->creditTransferData->setCreditorAddressFromArray($address);

        return $this;
    }

    /**
     * Gets the creditor address.
     *
     * @return array<string, string|null>|null The creditor address
     */
    public function getCreditorAddress(): ?array
    {
        return $this->creditTransferData->getCreditorAddress();
    }

    /**
     * Gets the underlying CreditTransferData instance.
     * Internal use only.
     *
     * @return CreditTransferData
     *
     * @internal
     */
    public function getCreditTransferData(): CreditTransferData
    {
        return $this->creditTransferData;
    }
}
