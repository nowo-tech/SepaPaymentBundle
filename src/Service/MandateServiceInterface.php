<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Service;

use DateTimeInterface;
use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Model\Mandate\Mandate;
use Nowo\SepaPaymentBundle\Model\Mandate\MandateHistory;
use Nowo\SepaPaymentBundle\Repository\MandateRepositoryInterface;
use RuntimeException;

/**
 * Port: SEPA mandate lifecycle (create, sequence type, revoke, suspend, reactivate) over a
 * {@see MandateRepositoryInterface}. {@see MandateService} is the default
 * implementation (aliased to this interface); it dispatches the events of {@see \Nowo\SepaPaymentBundle\Event\Mandate}
 * (mandate ids, no entities) after each save.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
interface MandateServiceInterface
{
    /**
     * Creates a new mandate.
     *
     * @param string $mandateId Mandate identifier
     * @param DateTimeInterface $signatureDate Signature date
     * @param string $debtorIban Debtor IBAN
     * @param string $debtorName Debtor name
     * @param string $type Mandate type (CORE, B2B)
     * @param string $sequenceType Sequence type (FRST, RCUR, OOFF, FNAL)
     *
     * @throws InvalidArgumentException If a mandate with this id already exists
     *
     * @return Mandate The created mandate
     */
    public function createMandate(
        string $mandateId,
        DateTimeInterface $signatureDate,
        string $debtorIban,
        string $debtorName,
        string $type = 'CORE',
        string $sequenceType = 'FRST'
    ): Mandate;

    /**
     * Updates the sequence type of a mandate.
     * Validates that the transition is allowed.
     *
     * @param string $mandateId Mandate identifier
     * @param string $sequenceType New sequence type
     *
     * @throws InvalidArgumentException If mandate not found or transition is invalid
     *
     * @return Mandate The updated mandate
     */
    public function updateSequenceType(string $mandateId, string $sequenceType): Mandate;

    /**
     * Revokes a mandate.
     *
     * @param string $mandateId Mandate identifier
     * @param string|null $reason Optional revocation reason
     *
     * @throws InvalidArgumentException If mandate not found
     *
     * @return Mandate The revoked mandate
     */
    public function revokeMandate(string $mandateId, ?string $reason = null): Mandate;

    /**
     * Suspends a mandate.
     *
     * @param string $mandateId Mandate identifier
     *
     * @throws InvalidArgumentException If mandate not found
     *
     * @return Mandate The suspended mandate
     */
    public function suspendMandate(string $mandateId): Mandate;

    /**
     * Reactivates a mandate.
     *
     * @param string $mandateId Mandate identifier
     *
     * @throws InvalidArgumentException If mandate not found
     * @throws RuntimeException If mandate is expired
     *
     * @return Mandate The reactivated mandate
     */
    public function reactivateMandate(string $mandateId): Mandate;

    /**
     * Validates a mandate for use in a Direct Debit transaction.
     *
     * @param string $mandateId Mandate identifier
     * @param string $sequenceType Required sequence type for the transaction
     *
     * @return bool True if valid, false otherwise
     */
    public function validateMandateForTransaction(string $mandateId, string $sequenceType): bool;

    /**
     * Checks if a sequence type transition is valid.
     *
     * @param string $fromSequenceType Current sequence type
     * @param string $toSequenceType Target sequence type
     *
     * @return bool True if transition is valid, false otherwise
     */
    public function isValidSequenceTransition(string $fromSequenceType, string $toSequenceType): bool;

    /**
     * Gets the mandate history.
     *
     * @param string $mandateId Mandate identifier
     *
     * @return array<int, MandateHistory> Array of history entries
     */
    public function getMandateHistory(string $mandateId): array;

    /**
     * Finds a mandate by ID.
     *
     * @param string $mandateId Mandate identifier
     *
     * @return Mandate|null The mandate or null if not found
     */
    public function findMandate(string $mandateId): ?Mandate;

    /**
     * Finds mandates by debtor IBAN.
     *
     * @param string $debtorIban Debtor IBAN
     *
     * @return array<int, Mandate> Array of mandates
     */
    public function findMandatesByDebtorIban(string $debtorIban): array;

    /**
     * Finds all active mandates.
     *
     * @return array<int, Mandate> Array of active mandates
     */
    public function findActiveMandates(): array;

    /**
     * Finds expired mandates.
     *
     * @param DateTimeInterface|null $beforeDate Optional date to find mandates expired before this date
     *
     * @return array<int, Mandate> Array of expired mandates
     */
    public function findExpiredMandates(?DateTimeInterface $beforeDate = null): array;
}
