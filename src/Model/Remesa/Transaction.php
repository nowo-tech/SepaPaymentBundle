<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\Remesa;

use Nowo\SepaPaymentBundle\Model\CreditTransfer\Transaction as CreditTransferTransaction;

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
     *
     * @var CreditTransferTransaction
     */
    private CreditTransferTransaction $creditTransferTransaction;

    /**
     * Constructor.
     *
     * @param string $endToEndId End-to-end identifier
     * @param float  $amount     Amount to transfer
     * @param string $currency   Currency code (ISO 4217)
     * @param string $debtorIban Debtor IBAN
     * @param string $debtorName Debtor name
     */
    public function __construct(
        string $endToEndId,
        float $amount,
        string $currency,
        string $debtorIban,
        string $debtorName
    ) {
        @trigger_error('Remesa\Transaction is deprecated since 1.1.0. Use CreditTransfer\Transaction instead.', \E_USER_DEPRECATED);

        $this->creditTransferTransaction = new CreditTransferTransaction(
            $endToEndId,
            $amount,
            $currency,
            $debtorIban,
            $debtorName
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
     *
     * @return string The debtor IBAN
     */
    public function getDebtorIban(): string
    {
        return $this->creditTransferTransaction->getDebtorIban();
    }

    /**
     * Sets the debtor BIC.
     *
     * @param string|null $debtorBic The debtor BIC
     *
     * @return self
     */
    public function setDebtorBic(?string $debtorBic): self
    {
        $this->creditTransferTransaction->setDebtorBic($debtorBic);

        return $this;
    }

    /**
     * Gets the debtor BIC.
     *
     * @return string|null The debtor BIC
     */
    public function getDebtorBic(): ?string
    {
        return $this->creditTransferTransaction->getDebtorBic();
    }

    /**
     * Gets the debtor name.
     *
     * @return string The debtor name
     */
    public function getDebtorName(): string
    {
        return $this->creditTransferTransaction->getDebtorName();
    }

    /**
     * Sets the remittance information.
     *
     * @param string|null $remittanceInformation The remittance information
     *
     * @return self
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
     *
     * @param array<string, string|null>|string|null $street     Address array or street address
     * @param string|null                            $city       City (ignored if first param is array)
     * @param string|null                            $postalCode Postal code (ignored if first param is array)
     * @param string|null                            $country    Country code (ignored if first param is array)
     *
     * @return self
     */
    public function setDebtorAddress(array|string|null $street = null, ?string $city = null, ?string $postalCode = null, ?string $country = null): self
    {
        $this->creditTransferTransaction->setDebtorAddress($street, $city, $postalCode, $country);

        return $this;
    }

    /**
     * Sets the debtor address from array.
     *
     * @param array<string, string|null> $address Address array with keys: street, city, postalCode, country
     *
     * @return self
     */
    public function setDebtorAddressFromArray(array $address): self
    {
        $this->creditTransferTransaction->setDebtorAddressFromArray($address);

        return $this;
    }

    /**
     * Gets the debtor address.
     *
     * @return array<string, string|null>|null The debtor address
     */
    public function getDebtorAddress(): ?array
    {
        return $this->creditTransferTransaction->getDebtorAddress();
    }

    /**
     * Gets the underlying CreditTransferTransaction instance.
     * Internal use only.
     *
     * @return CreditTransferTransaction
     *
     * @internal
     */
    public function getCreditTransferTransaction(): CreditTransferTransaction
    {
        return $this->creditTransferTransaction;
    }
}
