<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * SEPA business rules validator.
 * Validates SEPA-specific business rules and limits.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class SepaBusinessRulesValidator
{
    public const SERVICE_NAME = 'nowo_sepa_payment.validator.sepa_business_rules_validator';

    /**
     * Maximum transaction amount in EUR (999,999,999.99).
     */
    public const MAX_TRANSACTION_AMOUNT = 999999999.99;

    /**
     * Maximum number of transactions per file.
     */
    public const MAX_TRANSACTIONS_PER_FILE = 99999;

    /**
     * SEPA currency (EUR only).
     */
    public const SEPA_CURRENCY = 'EUR';

    /**
     * Validates if a transaction amount is within SEPA limits.
     *
     * @param float $amount The transaction amount
     *
     * @return bool True if valid, false otherwise
     */
    public function isValidTransactionAmount(float $amount): bool
    {
        return $amount > 0 && $amount <= self::MAX_TRANSACTION_AMOUNT;
    }

    /**
     * Validates if the number of transactions is within SEPA limits.
     *
     * @param int $transactionCount The number of transactions
     *
     * @return bool True if valid, false otherwise
     */
    public function isValidTransactionCount(int $transactionCount): bool
    {
        return $transactionCount > 0 && $transactionCount <= self::MAX_TRANSACTIONS_PER_FILE;
    }

    /**
     * Validates if an execution date is valid (must be a future date or today).
     *
     * @param \DateTimeInterface $executionDate The execution date
     * @param bool               $allowToday    Whether to allow today's date (default: true)
     *
     * @return bool True if valid, false otherwise
     */
    public function isValidExecutionDate(\DateTimeInterface $executionDate, bool $allowToday = true): bool
    {
        $today = new \DateTime('today');
        $executionDateOnly = new \DateTime($executionDate->format('Y-m-d'));

        if ($allowToday) {
            return $executionDateOnly >= $today;
        }

        return $executionDateOnly > $today;
    }

    /**
     * Validates if a date is a business day (Monday to Friday).
     *
     * @param \DateTimeInterface $date The date to validate
     *
     * @return bool True if it's a business day, false otherwise
     */
    public function isBusinessDay(\DateTimeInterface $date): bool
    {
        $dayOfWeek = (int) $date->format('N'); // 1 (Monday) to 7 (Sunday)

        return $dayOfWeek >= 1 && $dayOfWeek <= 5;
    }

    /**
     * Validates if a currency is valid for SEPA (EUR only).
     *
     * @param string $currency The currency code (ISO 4217)
     *
     * @return bool True if valid, false otherwise
     */
    public function isValidSepaCurrency(string $currency): bool
    {
        return strtoupper($currency) === self::SEPA_CURRENCY;
    }

    /**
     * Validates if a mandate expiration date is valid (must be in the future).
     *
     * @param \DateTimeInterface $expirationDate The mandate expiration date
     *
     * @return bool True if valid, false otherwise
     */
    public function isValidMandateExpirationDate(\DateTimeInterface $expirationDate): bool
    {
        $today = new \DateTime('today');
        $expirationDateOnly = new \DateTime($expirationDate->format('Y-m-d'));

        return $expirationDateOnly > $today;
    }

    /**
     * Validates if a sequence type transition is valid.
     * Valid transitions:
     * - FRST → RCUR (First to Recurring)
     * - RCUR → RCUR (Recurring to Recurring)
     * - OOFF → OOFF (One-off to One-off)
     * - FNAL → FNAL (Final to Final)
     *
     * @param string|null $previousSequenceType Previous sequence type
     * @param string      $newSequenceType      New sequence type
     *
     * @return bool True if valid transition, false otherwise
     */
    public function isValidSequenceTypeTransition(?string $previousSequenceType, string $newSequenceType): bool
    {
        $validTransitions = [
            null => ['FRST', 'OOFF'], // First transaction can be FRST or OOFF
            'FRST' => ['RCUR', 'FNAL'],
            'RCUR' => ['RCUR', 'FNAL'],
            'OOFF' => ['OOFF'],
            'FNAL' => ['FNAL'],
        ];

        $allowedNext = $validTransitions[$previousSequenceType] ?? [];

        return in_array($newSequenceType, $allowedNext, true);
    }

    /**
     * Validates all business rules for a credit transfer.
     *
     * @param float              $amount           Transaction amount
     * @param int                $transactionCount Number of transactions
     * @param \DateTimeInterface $executionDate    Execution date
     * @param string             $currency         Currency code
     *
     * @return array<string, string> Array of validation errors (empty if valid)
     */
    public function validateCreditTransfer(float $amount, int $transactionCount, \DateTimeInterface $executionDate, string $currency): array
    {
        $errors = [];

        if (!$this->isValidTransactionAmount($amount)) {
            $errors[] = sprintf('Transaction amount %.2f exceeds maximum allowed amount of %.2f EUR', $amount, self::MAX_TRANSACTION_AMOUNT);
        }

        if (!$this->isValidTransactionCount($transactionCount)) {
            $errors[] = sprintf('Transaction count %d exceeds maximum allowed count of %d', $transactionCount, self::MAX_TRANSACTIONS_PER_FILE);
        }

        if (!$this->isValidExecutionDate($executionDate)) {
            $errors[] = 'Execution date must be today or in the future';
        }

        if (!$this->isValidSepaCurrency($currency)) {
            $errors[] = sprintf('Currency %s is not valid for SEPA. Only EUR is allowed.', $currency);
        }

        return $errors;
    }

    /**
     * Validates all business rules for a direct debit.
     *
     * @param float                   $amount                Transaction amount
     * @param int                     $transactionCount      Number of transactions
     * @param \DateTimeInterface      $dueDate               Due date
     * @param string                  $currency              Currency code
     * @param string                  $sequenceType          Sequence type (FRST, RCUR, OOFF, FNAL)
     * @param \DateTimeInterface|null $mandateExpirationDate Mandate expiration date (optional)
     *
     * @return array<string, string> Array of validation errors (empty if valid)
     */
    public function validateDirectDebit(
        float $amount,
        int $transactionCount,
        \DateTimeInterface $dueDate,
        string $currency,
        string $sequenceType,
        ?\DateTimeInterface $mandateExpirationDate = null
    ): array {
        $errors = $this->validateCreditTransfer($amount, $transactionCount, $dueDate, $currency);

        $validSequenceTypes = ['FRST', 'RCUR', 'OOFF', 'FNAL'];
        if (!in_array($sequenceType, $validSequenceTypes, true)) {
            $errors[] = sprintf('Invalid sequence type: %s. Must be one of: %s', $sequenceType, implode(', ', $validSequenceTypes));
        }

        if (null !== $mandateExpirationDate && !$this->isValidMandateExpirationDate($mandateExpirationDate)) {
            $errors[] = 'Mandate expiration date must be in the future';
        }

        return $errors;
    }
}
