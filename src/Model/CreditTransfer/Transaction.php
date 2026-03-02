<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\CreditTransfer;

use function is_array;

/**
 * Transaction data for a SEPA Credit Transfer.
 * Represents a creditor (supplier/beneficiary) that receives money.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class Transaction
{
    /**
     * Creditor BIC (optional).
     */
    private ?string $creditorBic = null;

    /**
     * Remittance information (optional).
     */
    private ?string $remittanceInformation = null;

    /**
     * Creditor address (optional, included in XML if provided).
     *
     * @var array<string, string|null>|null
     */
    private ?array $creditorAddress = null;

    /**
     * Constructor.
     *
     * @param string $endToEndId End-to-end identifier
     * @param float $amount Amount to transfer
     * @param string $currency Currency code (ISO 4217)
     * @param string $creditorIban Creditor IBAN (beneficiary that receives)
     * @param string $creditorName Creditor name (beneficiary that receives)
     */
    public function __construct(
        private string $endToEndId,
        private float $amount,
        private string $currency,
        private string $creditorIban,
        private string $creditorName
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
     * Sets the remittance information.
     *
     * @param string|null $remittanceInformation The remittance information
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
     * Sets the creditor address.
     * Address will be included in the generated XML.
     *
     * @param array<string, string|null>|string|null $street Address array or street address
     * @param string|null $city City (ignored if first param is array)
     * @param string|null $postalCode Postal code (ignored if first param is array)
     * @param string|null $country Country code (ignored if first param is array)
     */
    public function setCreditorAddress(array|string|null $street = null, ?string $city = null, ?string $postalCode = null, ?string $country = null): self
    {
        if (is_array($street)) {
            return $this->setCreditorAddressFromArray($street);
        }

        $this->creditorAddress = [
            'street'     => $street,
            'city'       => $city,
            'postalCode' => $postalCode,
            'country'    => $country,
        ];

        return $this;
    }

    /**
     * Sets the creditor address from array.
     *
     * @param array<string, string|null> $address Address array with keys: street, city, postalCode, country
     */
    public function setCreditorAddressFromArray(array $address): self
    {
        $this->creditorAddress = [
            'street'     => $address['street'] ?? $address['address'] ?? null,
            'city'       => $address['city'] ?? null,
            'postalCode' => $address['postalCode'] ?? $address['postal_code'] ?? null,
            'country'    => $address['country'] ?? null,
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
