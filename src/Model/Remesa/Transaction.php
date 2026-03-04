<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\Remesa;

use Nowo\SepaPaymentBundle\Model\CreditTransfer\Transaction as CreditTransferTransaction;

use const E_USER_DEPRECATED;

/**
 * Transaction data for a SEPA Credit Transfer (deprecated).
 *
 * @deprecated Since 1.1.0, use CreditTransfer\Transaction instead. This class will be removed in 2.0.0.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class Transaction
{
    /**
     * Credit transfer transaction instance.
     */
    private readonly CreditTransferTransaction $creditTransferTransaction;

    /**
     * Constructor.
     * Note: This class maintains the deprecated Remesa API (debtor*), but internally uses CreditTransfer\Transaction.creditor* fields.
     *
     * @param string $endToEndId End-to-end identifier
     * @param float $amount Amount to transfer
     * @param string $currency Currency code (ISO 4217)
     * @param string $debtorIban Debtor IBAN (maps to CreditTransfer\Transaction.creditorIban internally)
     * @param string $debtorName Debtor name (maps to CreditTransfer\Transaction.creditorName internally)
     */
    public function __construct(
        string $endToEndId,
        float $amount,
        string $currency,
        string $debtorIban,
        string $debtorName
    ) {
        @trigger_error('Remesa\Transaction is deprecated since 1.1.0. Use CreditTransfer\Transaction instead.', E_USER_DEPRECATED);

        // Remesa\Transaction uses debtor* API (deprecated), but CreditTransfer\Transaction now uses creditor* fields
        $this->creditTransferTransaction = new CreditTransferTransaction(
            $endToEndId,
            $amount,
            $currency,
            $debtorIban, // Remesa API: debtorIban -> CreditTransfer API: creditorIban
            $debtorName,  // Remesa API: debtorName -> CreditTransfer API: creditorName
        );
    }

    /**
     * Gets the end-to-end identifier.
     *
     * @return string The end-to-end identifier
     */
    public function getEndToEndId(): string
    {
        return $this->creditTransferTransaction->getEndToEndId();
    }

    /**
     * Gets the amount.
     *
     * @return float The amount
     */
    public function getAmount(): float
    {
        return $this->creditTransferTransaction->getAmount();
    }

    /**
     * Gets the currency code.
     *
     * @return string The currency code
     */
    public function getCurrency(): string
    {
        return $this->creditTransferTransaction->getCurrency();
    }

    /**
     * Gets the debtor IBAN.
     * Note: This method maintains the deprecated Remesa API (debtor*), but internally uses CreditTransfer\Transaction.creditor* fields.
     *
     * @return string The debtor IBAN
     */
    public function getDebtorIban(): string
    {
        return $this->creditTransferTransaction->getCreditorIban();
    }

    /**
     * Sets the debtor BIC.
     * Note: This method maintains the deprecated Remesa API (debtor*), but internally uses CreditTransfer\Transaction.creditor* fields.
     *
     * @param string|null $debtorBic The debtor BIC
     */
    public function setDebtorBic(?string $debtorBic): self
    {
        $this->creditTransferTransaction->setCreditorBic($debtorBic);

        return $this;
    }

    /**
     * Gets the debtor BIC.
     * Note: This method maintains the deprecated Remesa API (debtor*), but internally uses CreditTransfer\Transaction.creditor* fields.
     *
     * @return string|null The debtor BIC
     */
    public function getDebtorBic(): ?string
    {
        return $this->creditTransferTransaction->getCreditorBic();
    }

    /**
     * Gets the debtor name.
     * Note: This method maintains the deprecated Remesa API (debtor*), but internally uses CreditTransfer\Transaction.creditor* fields.
     *
     * @return string The debtor name
     */
    public function getDebtorName(): string
    {
        return $this->creditTransferTransaction->getCreditorName();
    }

    /**
     * Sets the remittance information.
     *
     * @param string|null $remittanceInformation The remittance information
     */
    public function setRemittanceInformation(?string $remittanceInformation): self
    {
        $this->creditTransferTransaction->setRemittanceInformation($remittanceInformation);

        return $this;
    }

    /**
     * Gets the remittance information.
     *
     * @return string|null The remittance information
     */
    public function getRemittanceInformation(): ?string
    {
        return $this->creditTransferTransaction->getRemittanceInformation();
    }

    /**
     * Sets the debtor address.
     * Note: This method maintains the deprecated Remesa API (debtor*), but internally uses CreditTransfer\Transaction.creditor* fields.
     *
     * @param array<string, string|null>|string|null $street Address array or street address
     * @param string|null $city City (ignored if first param is array)
     * @param string|null $postalCode Postal code (ignored if first param is array)
     * @param string|null $country Country code (ignored if first param is array)
     */
    public function setDebtorAddress(array|string|null $street = null, ?string $city = null, ?string $postalCode = null, ?string $country = null): self
    {
        $this->creditTransferTransaction->setCreditorAddress($street, $city, $postalCode, $country);

        return $this;
    }

    /**
     * Sets the debtor address from array.
     * Note: This method maintains the deprecated Remesa API (debtor*), but internally uses CreditTransfer\Transaction.creditor* fields.
     *
     * @param array<string, string|null> $address Address array with keys: street, city, postalCode, country
     */
    public function setDebtorAddressFromArray(array $address): self
    {
        $this->creditTransferTransaction->setCreditorAddressFromArray($address);

        return $this;
    }

    /**
     * Gets the debtor address.
     * Note: This method maintains the deprecated Remesa API (debtor*), but internally uses CreditTransfer\Transaction.creditor* fields.
     *
     * @return array<string, string|null>|null The debtor address
     */
    public function getDebtorAddress(): ?array
    {
        return $this->creditTransferTransaction->getCreditorAddress();
    }

    /**
     * Gets the underlying CreditTransferTransaction instance.
     * Internal use only.
     *
     * @internal
     */
    public function getCreditTransferTransaction(): CreditTransferTransaction
    {
        return $this->creditTransferTransaction;
    }
}
