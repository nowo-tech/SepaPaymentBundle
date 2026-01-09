<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\CreditTransfer;

/**
 * Transaction data for a SEPA Credit Transfer.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class Transaction
{
    /**
     * Debtor BIC (optional).
     *
     * @var string|null
     */
    private ?string $debtorBic = null;

    /**
     * Remittance information (optional).
     *
     * @var string|null
     */
    private ?string $remittanceInformation = null;

    /**
     * Debtor address (optional, included in XML if provided).
     *
     * @var array<string, string|null>|null
     */
    private ?array $debtorAddress = null;

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
        private string $endToEndId,
        private float $amount,
        private string $currency,
        private string $debtorIban,
        private string $debtorName
    ) {
    }

    /**
     * Gets the end-to-end identifier.
     *
     * @return string The end-to-end identifier
     */
    public function getEndToEndId(): string
    {
        return $this->endToEndId;
    }

    /**
     * Gets the amount.
     *
     * @return float The amount
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Gets the currency code.
     *
     * @return string The currency code
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Gets the debtor IBAN.
     *
     * @return string The debtor IBAN
     */
    public function getDebtorIban(): string
    {
        return $this->debtorIban;
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
        $this->debtorBic = $debtorBic;

        return $this;
    }

    /**
     * Gets the debtor BIC.
     *
     * @return string|null The debtor BIC
     */
    public function getDebtorBic(): ?string
    {
        return $this->debtorBic;
    }

    /**
     * Gets the debtor name.
     *
     * @return string The debtor name
     */
    public function getDebtorName(): string
    {
        return $this->debtorName;
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
        $this->remittanceInformation = $remittanceInformation;

        return $this;
    }

    /**
     * Gets the remittance information.
     *
     * @return string|null The remittance information
     */
    public function getRemittanceInformation(): ?string
    {
        return $this->remittanceInformation;
    }

    /**
     * Sets the debtor address.
     * Address will be included in the generated XML.
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
        if (is_array($street)) {
            return $this->setDebtorAddressFromArray($street);
        }

        $this->debtorAddress = [
            'street' => $street,
            'city' => $city,
            'postalCode' => $postalCode,
            'country' => $country,
        ];

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
        $this->debtorAddress = [
            'street' => $address['street'] ?? $address['address'] ?? null,
            'city' => $address['city'] ?? null,
            'postalCode' => $address['postalCode'] ?? $address['postal_code'] ?? null,
            'country' => $address['country'] ?? null,
        ];

        return $this;
    }

    /**
     * Gets the debtor address.
     *
     * @return array<string, string|null>|null The debtor address
     */
    public function getDebtorAddress(): ?array
    {
        return $this->debtorAddress;
    }
}
