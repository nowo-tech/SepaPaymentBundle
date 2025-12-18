<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\DirectDebit;

/**
 * Direct Debit transaction data.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class DirectDebitTransaction
{
    /**
     * Remittance information (optional).
     *
     * @var string|null
     */
    private ?string $remittanceInformation = null;

    /**
     * Debtor BIC (optional).
     *
     * @var string|null
     */
    private ?string $debtorBic = null;

    /**
     * Additional data (optional).
     * Can be used to store extra fields that may be needed for specific use cases.
     *
     * @var array<string, mixed>
     */
    private array $additionalData = [];

    /**
     * Constructor.
     *
     * @param float              $amount                Amount to debit
     * @param string             $debtorIban            Debtor IBAN
     * @param string             $debtorName            Debtor name
     * @param string             $debtorMandate         Debtor mandate identifier
     * @param \DateTimeInterface $debtorMandateSignDate Debtor mandate sign date
     * @param string             $endToEndId            End-to-end identifier
     */
    public function __construct(
        private float $amount,
        private string $debtorIban,
        private string $debtorName,
        private string $debtorMandate,
        private \DateTimeInterface $debtorMandateSignDate,
        private string $endToEndId
    ) {
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
     * Gets the debtor IBAN.
     *
     * @return string The debtor IBAN
     */
    public function getDebtorIban(): string
    {
        return $this->debtorIban;
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
     * Gets the debtor mandate identifier.
     *
     * @return string The debtor mandate identifier
     */
    public function getDebtorMandate(): string
    {
        return $this->debtorMandate;
    }

    /**
     * Gets the debtor mandate sign date.
     *
     * @return \DateTimeInterface The debtor mandate sign date
     */
    public function getDebtorMandateSignDate(): \DateTimeInterface
    {
        return $this->debtorMandateSignDate;
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
     * Sets additional data.
     *
     * @param array<string, mixed> $additionalData Additional data
     *
     * @return self
     */
    public function setAdditionalData(array $additionalData): self
    {
        $this->additionalData = $additionalData;

        return $this;
    }

    /**
     * Gets additional data.
     *
     * @return array<string, mixed> Additional data
     */
    public function getAdditionalData(): array
    {
        return $this->additionalData;
    }

    /**
     * Sets a specific additional data field.
     *
     * @param string $key   The field key
     * @param mixed  $value The field value
     *
     * @return self
     */
    public function setAdditionalField(string $key, mixed $value): self
    {
        $this->additionalData[$key] = $value;

        return $this;
    }

    /**
     * Gets a specific additional data field.
     *
     * @param string $key     The field key
     * @param mixed  $default Default value if key doesn't exist
     *
     * @return mixed The field value or default
     */
    public function getAdditionalField(string $key, mixed $default = null): mixed
    {
        return $this->additionalData[$key] ?? $default;
    }

    /**
     * Sets the debtor address.
     * Address will be included in the generated XML (as of v0.0.8).
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

        $this->additionalData['debtorAddress'] = [
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
        $this->additionalData['debtorAddress'] = [
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
        return $this->additionalData['debtorAddress'] ?? null;
    }
}
