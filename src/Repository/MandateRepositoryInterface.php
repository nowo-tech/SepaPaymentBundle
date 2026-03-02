<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Repository;

use DateTimeInterface;
use Nowo\SepaPaymentBundle\Model\Mandate\Mandate;
use Nowo\SepaPaymentBundle\Model\Mandate\MandateHistory;

/**
 * Mandate repository interface.
 * Defines methods for storing and retrieving mandates.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
interface MandateRepositoryInterface
{
    /**
     * Saves a mandate.
     *
     * @param Mandate $mandate The mandate to save
     */
    public function save(Mandate $mandate): void;

    /**
     * Finds a mandate by its identifier.
     *
     * @param string $mandateId The mandate identifier
     *
     * @return Mandate|null The mandate or null if not found
     */
    public function findById(string $mandateId): ?Mandate;

    /**
     * Finds mandates by debtor IBAN.
     *
     * @param string $debtorIban The debtor IBAN
     *
     * @return array<int, Mandate> Array of mandates
     */
    public function findByDebtorIban(string $debtorIban): array;

    /**
     * Finds all active mandates.
     *
     * @return array<int, Mandate> Array of active mandates
     */
    public function findActive(): array;

    /**
     * Finds expired mandates.
     *
     * @param DateTimeInterface|null $beforeDate Optional date to find mandates expired before this date
     *
     * @return array<int, Mandate> Array of expired mandates
     */
    public function findExpired(?DateTimeInterface $beforeDate = null): array;

    /**
     * Deletes a mandate.
     *
     * @param string $mandateId The mandate identifier
     *
     * @return bool True if deleted, false if not found
     */
    public function delete(string $mandateId): bool;

    /**
     * Adds a history entry for a mandate.
     *
     * @param MandateHistory $history The history entry
     */
    public function addHistory(MandateHistory $history): void;

    /**
     * Gets the history for a mandate.
     *
     * @param string $mandateId The mandate identifier
     *
     * @return array<int, MandateHistory> Array of history entries (ordered by timestamp, oldest first)
     */
    public function getHistory(string $mandateId): array;
}
