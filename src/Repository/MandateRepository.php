<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Repository;

use Nowo\SepaPaymentBundle\Model\Mandate\Mandate;
use Nowo\SepaPaymentBundle\Model\Mandate\MandateHistory;
use Nowo\SepaPaymentBundle\Model\Mandate\MandateStatus;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * In-memory mandate repository implementation.
 * This is a simple implementation that stores mandates in memory.
 * For production use, implement a database-backed repository.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class MandateRepository implements MandateRepositoryInterface
{
    public const SERVICE_NAME = 'nowo_sepa_payment.repository.mandate_repository';

    /**
     * In-memory storage for mandates.
     *
     * @var array<string, Mandate>
     */
    private array $mandates = [];

    /**
     * In-memory storage for mandate history.
     *
     * @var array<string, array<int, MandateHistory>>
     */
    private array $history = [];

    /**
     * Saves a mandate.
     *
     * @param Mandate $mandate The mandate to save
     *
     * @return void
     */
    public function save(Mandate $mandate): void
    {
        $this->mandates[$mandate->getMandateId()] = $mandate;
    }

    /**
     * Finds a mandate by its identifier.
     *
     * @param string $mandateId The mandate identifier
     *
     * @return Mandate|null The mandate or null if not found
     */
    public function findById(string $mandateId): ?Mandate
    {
        return $this->mandates[$mandateId] ?? null;
    }

    /**
     * Finds mandates by debtor IBAN.
     *
     * @param string $debtorIban The debtor IBAN
     *
     * @return array<int, Mandate> Array of mandates
     */
    public function findByDebtorIban(string $debtorIban): array
    {
        $result = [];
        foreach ($this->mandates as $mandate) {
            if ($mandate->getDebtorIban() === $debtorIban) {
                $result[] = $mandate;
            }
        }

        return $result;
    }

    /**
     * Finds all active mandates.
     *
     * @return array<int, Mandate> Array of active mandates
     */
    public function findActive(): array
    {
        $result = [];
        foreach ($this->mandates as $mandate) {
            if ($mandate->isActive()) {
                $result[] = $mandate;
            }
        }

        return $result;
    }

    /**
     * Finds expired mandates.
     *
     * @param \DateTimeInterface|null $beforeDate Optional date to find mandates expired before this date
     *
     * @return array<int, Mandate> Array of expired mandates
     */
    public function findExpired(?\DateTimeInterface $beforeDate = null): array
    {
        $result = [];
        $checkDate = $beforeDate ?? new \DateTime();

        foreach ($this->mandates as $mandate) {
            // Check if mandate has expiration date and is expired
            $expirationDate = $this->getExpirationDate($mandate);
            if ($expirationDate !== null && $expirationDate < $checkDate) {
                $result[] = $mandate;
            }
        }

        return $result;
    }

    /**
     * Deletes a mandate.
     *
     * @param string $mandateId The mandate identifier
     *
     * @return bool True if deleted, false if not found
     */
    public function delete(string $mandateId): bool
    {
        if (isset($this->mandates[$mandateId])) {
            unset($this->mandates[$mandateId]);

            return true;
        }

        return false;
    }

    /**
     * Adds a history entry for a mandate.
     *
     * @param MandateHistory $history The history entry
     *
     * @return void
     */
    public function addHistory(MandateHistory $history): void
    {
        $mandateId = $history->getMandateId();
        if (!isset($this->history[$mandateId])) {
            $this->history[$mandateId] = [];
        }
        $this->history[$mandateId][] = $history;

        // Sort by timestamp (oldest first)
        usort($this->history[$mandateId], function (MandateHistory $a, MandateHistory $b) {
            return $a->getTimestamp() <=> $b->getTimestamp();
        });
    }

    /**
     * Gets the history for a mandate.
     *
     * @param string $mandateId The mandate identifier
     *
     * @return array<int, MandateHistory> Array of history entries (ordered by timestamp, oldest first)
     */
    public function getHistory(string $mandateId): array
    {
        return $this->history[$mandateId] ?? [];
    }

    /**
     * Gets the expiration date for a mandate.
     * SEPA mandates typically expire 36 months after signature date.
     *
     * @param Mandate $mandate The mandate
     *
     * @return \DateTimeInterface|null The expiration date or null if not applicable
     */
    private function getExpirationDate(Mandate $mandate): ?\DateTimeInterface
    {
        // SEPA mandates expire 36 months after signature date
        $signatureDate = $mandate->getSignatureDate();
        $expirationDate = new \DateTime($signatureDate->format('Y-m-d H:i:s'));
        $expirationDate->modify('+36 months');

        return $expirationDate;
    }

    /**
     * Clears all mandates (useful for testing).
     *
     * @return void
     */
    public function clear(): void
    {
        $this->mandates = [];
        $this->history = [];
    }
}
