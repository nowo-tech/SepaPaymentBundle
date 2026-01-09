<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Service;

use Nowo\SepaPaymentBundle\Model\Mandate\Mandate;
use Nowo\SepaPaymentBundle\Model\Mandate\MandateHistory;
use Nowo\SepaPaymentBundle\Model\Mandate\MandateStatus;
use Nowo\SepaPaymentBundle\Repository\MandateRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Mandate service.
 * Provides business logic for mandate lifecycle management.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class MandateService
{
    public const SERVICE_NAME = 'nowo_sepa_payment.service.mandate_service';

    /**
     * Valid sequence type transitions.
     * Maps from current sequence type to allowed next sequence types.
     *
     * @var array<string, array<string>>
     */
    private const VALID_SEQUENCE_TRANSITIONS = [
        'FRST' => ['RCUR', 'FNAL'],
        'RCUR' => ['RCUR', 'FNAL'],
        'OOFF' => ['FNAL'],
        'FNAL' => [], // FNAL is terminal
    ];

    /**
     * Constructor.
     *
     * @param MandateRepositoryInterface $repository Mandate repository
     */
    public function __construct(
        private MandateRepositoryInterface $repository
    ) {
    }

    /**
     * Creates a new mandate.
     *
     * @param string             $mandateId     Mandate identifier
     * @param \DateTimeInterface $signatureDate Signature date
     * @param string             $debtorIban    Debtor IBAN
     * @param string             $debtorName    Debtor name
     * @param string             $type          Mandate type (CORE, B2B)
     * @param string             $sequenceType  Sequence type (FRST, RCUR, OOFF, FNAL)
     *
     * @return Mandate The created mandate
     */
    public function createMandate(
        string $mandateId,
        \DateTimeInterface $signatureDate,
        string $debtorIban,
        string $debtorName,
        string $type = 'CORE',
        string $sequenceType = 'FRST'
    ): Mandate {
        // Check if mandate already exists
        if ($this->repository->findById($mandateId) !== null) {
            throw new \InvalidArgumentException("Mandate with ID '{$mandateId}' already exists");
        }

        $mandate = new Mandate($mandateId, $signatureDate, $debtorIban, $debtorName, $type, $sequenceType);
        $this->repository->save($mandate);

        // Add history entry
        $this->repository->addHistory(new MandateHistory(
            $mandateId,
            new \DateTime(),
            'created',
            '',
            'active',
            'Mandate created'
        ));

        return $mandate;
    }

    /**
     * Updates the sequence type of a mandate.
     * Validates that the transition is allowed.
     *
     * @param string $mandateId    Mandate identifier
     * @param string $sequenceType New sequence type
     *
     * @return Mandate The updated mandate
     *
     * @throws \InvalidArgumentException If mandate not found or transition is invalid
     */
    public function updateSequenceType(string $mandateId, string $sequenceType): Mandate
    {
        $mandate = $this->repository->findById($mandateId);
        if ($mandate === null) {
            throw new \InvalidArgumentException("Mandate with ID '{$mandateId}' not found");
        }

        $oldSequenceType = $mandate->getSequenceType();

        // Validate transition
        if (!$this->isValidSequenceTransition($oldSequenceType, $sequenceType)) {
            throw new \InvalidArgumentException(
                "Invalid sequence type transition from '{$oldSequenceType}' to '{$sequenceType}'"
            );
        }

        $mandate->setSequenceType($sequenceType);
        $this->repository->save($mandate);

        // Add history entry
        $this->repository->addHistory(new MandateHistory(
            $mandateId,
            new \DateTime(),
            'sequence_change',
            $oldSequenceType,
            $sequenceType,
            "Sequence type changed from {$oldSequenceType} to {$sequenceType}"
        ));

        return $mandate;
    }

    /**
     * Revokes a mandate.
     *
     * @param string      $mandateId Mandate identifier
     * @param string|null $reason    Optional revocation reason
     *
     * @return Mandate The revoked mandate
     *
     * @throws \InvalidArgumentException If mandate not found
     */
    public function revokeMandate(string $mandateId, ?string $reason = null): Mandate
    {
        $mandate = $this->repository->findById($mandateId);
        if ($mandate === null) {
            throw new \InvalidArgumentException("Mandate with ID '{$mandateId}' not found");
        }

        $oldStatus = $mandate->getStatus()->value;
        $mandate->revoke($reason);
        $this->repository->save($mandate);

        // Add history entry
        $this->repository->addHistory(new MandateHistory(
            $mandateId,
            new \DateTime(),
            'status_change',
            $oldStatus,
            MandateStatus::REVOKED->value,
            $reason ? "Mandate revoked: {$reason}" : 'Mandate revoked'
        ));

        return $mandate;
    }

    /**
     * Suspends a mandate.
     *
     * @param string $mandateId Mandate identifier
     *
     * @return Mandate The suspended mandate
     *
     * @throws \InvalidArgumentException If mandate not found
     */
    public function suspendMandate(string $mandateId): Mandate
    {
        $mandate = $this->repository->findById($mandateId);
        if ($mandate === null) {
            throw new \InvalidArgumentException("Mandate with ID '{$mandateId}' not found");
        }

        $oldStatus = $mandate->getStatus()->value;
        $mandate->suspend();
        $this->repository->save($mandate);

        // Add history entry
        $this->repository->addHistory(new MandateHistory(
            $mandateId,
            new \DateTime(),
            'status_change',
            $oldStatus,
            MandateStatus::SUSPENDED->value,
            'Mandate suspended'
        ));

        return $mandate;
    }

    /**
     * Reactivates a mandate.
     *
     * @param string $mandateId Mandate identifier
     *
     * @return Mandate The reactivated mandate
     *
     * @throws \InvalidArgumentException If mandate not found
     * @throws \RuntimeException        If mandate is expired
     */
    public function reactivateMandate(string $mandateId): Mandate
    {
        $mandate = $this->repository->findById($mandateId);
        if ($mandate === null) {
            throw new \InvalidArgumentException("Mandate with ID '{$mandateId}' not found");
        }

        $oldStatus = $mandate->getStatus()->value;
        $mandate->reactivate();
        $this->repository->save($mandate);

        // Add history entry
        $this->repository->addHistory(new MandateHistory(
            $mandateId,
            new \DateTime(),
            'status_change',
            $oldStatus,
            MandateStatus::ACTIVE->value,
            'Mandate reactivated'
        ));

        return $mandate;
    }

    /**
     * Validates a mandate for use in a Direct Debit transaction.
     *
     * @param string $mandateId    Mandate identifier
     * @param string $sequenceType Required sequence type for the transaction
     *
     * @return bool True if valid, false otherwise
     */
    public function validateMandateForTransaction(string $mandateId, string $sequenceType): bool
    {
        $mandate = $this->repository->findById($mandateId);
        if ($mandate === null) {
            return false;
        }

        // Check if mandate is active
        if (!$mandate->isActive() || $mandate->getStatus() !== MandateStatus::ACTIVE) {
            return false;
        }

        // Check if mandate is expired
        if ($mandate->isExpired()) {
            return false;
        }

        // Check if sequence type transition is valid
        if (!$this->isValidSequenceTransition($mandate->getSequenceType(), $sequenceType)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if a sequence type transition is valid.
     *
     * @param string $fromSequenceType Current sequence type
     * @param string $toSequenceType   Target sequence type
     *
     * @return bool True if transition is valid, false otherwise
     */
    public function isValidSequenceTransition(string $fromSequenceType, string $toSequenceType): bool
    {
        if (!isset(self::VALID_SEQUENCE_TRANSITIONS[$fromSequenceType])) {
            return false;
        }

        return in_array($toSequenceType, self::VALID_SEQUENCE_TRANSITIONS[$fromSequenceType], true);
    }

    /**
     * Gets the mandate history.
     *
     * @param string $mandateId Mandate identifier
     *
     * @return array<int, MandateHistory> Array of history entries
     */
    public function getMandateHistory(string $mandateId): array
    {
        return $this->repository->getHistory($mandateId);
    }

    /**
     * Finds a mandate by ID.
     *
     * @param string $mandateId Mandate identifier
     *
     * @return Mandate|null The mandate or null if not found
     */
    public function findMandate(string $mandateId): ?Mandate
    {
        return $this->repository->findById($mandateId);
    }

    /**
     * Finds mandates by debtor IBAN.
     *
     * @param string $debtorIban Debtor IBAN
     *
     * @return array<int, Mandate> Array of mandates
     */
    public function findMandatesByDebtorIban(string $debtorIban): array
    {
        return $this->repository->findByDebtorIban($debtorIban);
    }

    /**
     * Finds all active mandates.
     *
     * @return array<int, Mandate> Array of active mandates
     */
    public function findActiveMandates(): array
    {
        return $this->repository->findActive();
    }

    /**
     * Finds expired mandates.
     *
     * @param \DateTimeInterface|null $beforeDate Optional date to find mandates expired before this date
     *
     * @return array<int, Mandate> Array of expired mandates
     */
    public function findExpiredMandates(?\DateTimeInterface $beforeDate = null): array
    {
        return $this->repository->findExpired($beforeDate);
    }
}
