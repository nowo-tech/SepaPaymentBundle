<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\CreditTransfer;

use Nowo\SepaPaymentBundle\Model\Amount;

use function is_array;

/**
 * Transaction data for a SEPA Credit Transfer.
 * Represents a creditor (supplier/beneficiary) that receives money.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class Transaction
{
    /**
     * Amount in cents (the exact value used in the XML).
     */
    private readonly int $amountCents;

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
     * `$amount` in euros: a decimal string ("1500.25") or an int (whole euros) is exact; a float still works but is deprecated since 2.1
     * (it is rounded half up to the cent like `round($x, 2)`, see {@see Amount}); another numeric string ("1e3", " 1.5") is read like a float and deprecated too — use {@see self::fromCents()} or a decimal string.
     *
     * @param string $endToEndId End-to-end identifier
     * @param float|int|string $amount Amount to transfer, in euros (decimal string or whole euros as int; float and non-decimal numeric strings deprecated)
     * @param string $currency Currency code (ISO 4217)
     * @param string $creditorIban Creditor IBAN (beneficiary that receives)
     * @param string $creditorName Creditor name (beneficiary that receives)
     */
    public function __construct(
        private readonly string $endToEndId,
        int|float|string $amount,
        private readonly string $currency,
        private readonly string $creditorIban,
        private readonly string $creditorName
    ) {
        $this->amountCents = Amount::centsFromValue($amount, 'CreditTransfer\\Transaction', 'Transaction::fromCents()');
    }

    /**
     * Creates a transaction from an amount in integer cents (exact; preferred).
     *
     * @param string $endToEndId End-to-end identifier
     * @param int $amountCents Amount to transfer, in cents (1500025 = 15000.25)
     * @param string $currency Currency code (ISO 4217)
     * @param string $creditorIban Creditor IBAN (beneficiary that receives)
     * @param string $creditorName Creditor name (beneficiary that receives)
     */
    public static function fromCents(string $endToEndId, int $amountCents, string $currency, string $creditorIban, string $creditorName): self
    {
        return new self($endToEndId, Amount::decimalFromCents($amountCents), $currency, $creditorIban, $creditorName);
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
     * Gets the amount (a float view of {@see self::getAmountCents()}; prefer the cents for arithmetic).
     *
     * @return float The amount
     */
    public function getAmount(): float
    {
        return $this->amountCents / 100;
    }

    /**
     * Gets the amount in cents (exact).
     *
     * @return int The amount in cents
     */
    public function getAmountCents(): int
    {
        return $this->amountCents;
    }

    /**
     * Gets the amount as a decimal string with two decimals ("15000.25").
     *
     * @return string The amount
     */
    public function getAmountDecimal(): string
    {
        return Amount::decimalFromCents($this->amountCents);
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
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
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
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
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

        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
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
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
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
