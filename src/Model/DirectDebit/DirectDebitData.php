<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model\DirectDebit;

use DateTimeInterface;
use Nowo\SepaPaymentBundle\Model\PartyIdentification;

use function array_merge;
use function array_values;
use function is_array;

/**
 * Direct Debit data container.
 * Contains all information needed to generate a SEPA Direct Debit.
 *
 * One file holds one or more payment-information blocks (`PmtInf`):
 * - the constructor describes a first block (payment info id, due date, sequence type, local instrument) whose
 *   transactions are added with {@see self::addTransaction()} (the 2.1 single-block API, unchanged);
 * - {@see self::addPaymentInformation()} adds further blocks ({@see PaymentInformationData}), each with its own
 *   transactions; {@see self::fromPaymentInformation()} builds a file from blocks only.
 * The constructor block is written first when it has transactions (or when there are no other blocks).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class DirectDebitData
{
    /**
     * Creditor BIC (optional).
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
     * Further payment-information blocks.
     *
     * @var list<PaymentInformationData>
     */
    private array $paymentInformation = [];

    /**
     * Initiating party identification (`GrpHdr/InitgPty/Id`, optional).
     */
    private ?PartyIdentification $initiatingPartyIdentification = null;

    /**
     * Constructor.
     *
     * @param string $messageId Message identifier
     * @param string $initiatingPartyName Initiating party name
     * @param string $paymentInfoId Payment information identifier
     * @param DateTimeInterface $dueDate Due date
     * @param string $creditorName Creditor name
     * @param string $creditorIban Creditor IBAN
     * @param string $sequenceType Sequence type (FRST, RCUR, OOFF, FNAL)
     * @param string $creditorId Creditor identifier (SEPA identifier)
     * @param string $localInstrumentCode Local instrument code (CORE, B2B)
     */
    public function __construct(
        private readonly string $messageId,
        private readonly string $initiatingPartyName,
        private readonly string $paymentInfoId,
        private readonly DateTimeInterface $dueDate,
        private readonly string $creditorName,
        private readonly string $creditorIban,
        private readonly string $sequenceType,
        private readonly string $creditorId,
        private readonly string $localInstrumentCode
    ) {
    }

    /**
     * Builds a file from payment-information blocks only. The getters of the single-block API
     * ({@see self::getPaymentInfoId()}, {@see self::getDueDate()}, {@see self::getSequenceType()},
     * {@see self::getLocalInstrumentCode()}) return the first block's values.
     *
     * @param string $messageId Message identifier
     * @param string $initiatingPartyName Initiating party name
     * @param string $creditorName Creditor name
     * @param string $creditorIban Creditor IBAN
     * @param string $creditorId Creditor identifier (SEPA identifier)
     * @param PaymentInformationData $first The first block
     * @param PaymentInformationData ...$more Further blocks
     */
    public static function fromPaymentInformation(
        string $messageId,
        string $initiatingPartyName,
        string $creditorName,
        string $creditorIban,
        string $creditorId,
        PaymentInformationData $first,
        PaymentInformationData ...$more,
    ): self {
        $data = new self(
            $messageId,
            $initiatingPartyName,
            $first->getPaymentInfoId(),
            $first->getDueDate(),
            $creditorName,
            $creditorIban,
            $first->getSequenceType() ?? SequenceType::RECURRENT,
            $creditorId,
            $first->getLocalInstrumentCode(),
        );
        $data->addPaymentInformation($first);
        foreach ($more as $block) {
            $data->addPaymentInformation($block);
        }

        return $data;
    }

    /**
     * Adds a payment-information block (written after the constructor block).
     *
     * @param PaymentInformationData $paymentInformation The block
     */
    public function addPaymentInformation(PaymentInformationData $paymentInformation): self
    {
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
        $this->paymentInformation[] = $paymentInformation;

        return $this;
    }

    /**
     * Gets the payment-information blocks in file order: the constructor block (when it has transactions, or when
     * there is no other block) followed by the blocks added with {@see self::addPaymentInformation()}.
     *
     * @return list<PaymentInformationData>
     */
    public function getPaymentInformation(): array
    {
        $blocks = [];
        if ($this->transactions !== [] || $this->paymentInformation === []) {
            $blocks[] = PaymentInformationData::fromSingleBlock(
                $this->paymentInfoId,
                $this->dueDate,
                $this->sequenceType,
                $this->localInstrumentCode,
                array_values($this->transactions),
            );
        }

        return array_merge($blocks, $this->paymentInformation);
    }

    /**
     * Sets the initiating party identification (`GrpHdr/InitgPty/Id`), e.g.
     * `PartyIdentification::organisation('ES12000B12345678')`.
     *
     * @param PartyIdentification|null $identification The identification, or null for none
     */
    public function setInitiatingPartyIdentification(?PartyIdentification $identification): self
    {
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
        $this->initiatingPartyIdentification = $identification;

        return $this;
    }

    /**
     * Gets the initiating party identification.
     */
    public function getInitiatingPartyIdentification(): ?PartyIdentification
    {
        return $this->initiatingPartyIdentification;
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
     * @return DateTimeInterface The due date
     */
    public function getDueDate(): DateTimeInterface
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
     */
    public function addTransaction(DirectDebitTransaction $transaction): self
    {
        // @igor-ignore - Domain model value object; instance-scoped, not a shared service.
        $this->transactions[] = $transaction;

        return $this;
    }

    /**
     * Gets all transactions of the file, block after block (the constructor block's transactions first, as added with
     * {@see self::addTransaction()}, then those of the blocks added with {@see self::addPaymentInformation()}).
     *
     * @return array<int, DirectDebitTransaction> The transactions
     */
    public function getTransactions(): array
    {
        if ($this->paymentInformation === []) {
            return $this->transactions;
        }

        $transactions = [];
        foreach ($this->getPaymentInformation() as $block) {
            foreach ($block->getTransactions() as $transaction) {
                $transactions[] = $transaction;
            }
        }

        return $transactions;
    }

    /**
     * Calculates the total amount of all transactions, in euros (summed in cents, so 0.1 + 0.2 is 0.3).
     *
     * @return float The total amount
     */
    public function getTotalAmount(): float
    {
        return $this->getTotalAmountCents() / 100;
    }

    /**
     * Calculates the total amount of all transactions, in cents (exact; the XML control sum).
     *
     * @return int The total amount in cents
     */
    public function getTotalAmountCents(): int
    {
        $total = 0;
        foreach ($this->getTransactions() as $transaction) {
            $total += $transaction->getAmountCents();
        }

        return $total;
    }

    /**
     * Sets the creditor address.
     * Address will be included in the generated XML (as of v0.0.8).
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
