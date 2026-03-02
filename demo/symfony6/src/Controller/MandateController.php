<?php

declare(strict_types=1);

namespace App\Controller;

use DateTime;
use Exception;
use Nowo\SepaPaymentBundle\Model\Mandate\Mandate;
use Nowo\SepaPaymentBundle\Service\MandateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_UNICODE;

class MandateController extends AbstractController
{
    /**
     * Demo mandate creation.
     */
    #[Route('/demo-mandate', name: 'demo_mandate')]
    public function demoMandate(): JsonResponse
    {
        $mandate = new Mandate(
            'MANDATE-001',
            new DateTime('2024-01-15'),
            'ES9121000418450200051332',
            'John Doe',
            'CORE',
            'FRST',
        );

        $mandate->setDebtorBic('CAIXESBBXXX');
        $mandate->setSequenceType('RCUR');
        $mandate->setActive(true);

        return new JsonResponse([
            'mandateId'     => $mandate->getMandateId(),
            'signatureDate' => $mandate->getSignatureDate()->format('Y-m-d'),
            'debtorIban'    => $mandate->getDebtorIban(),
            'debtorBic'     => $mandate->getDebtorBic(),
            'debtorName'    => $mandate->getDebtorName(),
            'type'          => $mandate->getType(),
            'sequenceType'  => $mandate->getSequenceType(),
            'active'        => $mandate->isActive(),
        ]);
    }

    /**
     * Demo Mandate Management - Create and manage mandates.
     *
     * @param MandateService $mandateService Mandate service
     */
    #[Route('/demo-mandate-management', name: 'demo_mandate_management')]
    public function demoMandateManagement(MandateService $mandateService): JsonResponse
    {
        try {
            // Create a new mandate
            $mandate = $mandateService->createMandate(
                'MANDATE-DEMO-001',
                new DateTime('2024-01-15'),
                'ES9121000418450200051332',
                'John Doe',
                'CORE',
                'FRST',
            );

            // Update sequence type
            $mandateService->updateSequenceType('MANDATE-DEMO-001', 'RCUR');

            // Get mandate history
            $history = $mandateService->getMandateHistory('MANDATE-DEMO-001');

            // Validate mandate for transaction
            $isValid = $mandateService->validateMandateForTransaction('MANDATE-DEMO-001', 'RCUR');

            $response = new JsonResponse([
                'message' => 'Mandate management demonstration',
                'mandate' => [
                    'id'             => $mandate->getMandateId(),
                    'debtorIban'     => $mandate->getDebtorIban(),
                    'debtorName'     => $mandate->getDebtorName(),
                    'type'           => $mandate->getType(),
                    'sequenceType'   => $mandate->getSequenceType(),
                    'status'         => $mandate->getStatus()->value,
                    'isActive'       => $mandate->isActive(),
                    'signatureDate'  => $mandate->getSignatureDate()->format('Y-m-d'),
                    'expirationDate' => $mandate->getExpirationDate()?->format('Y-m-d'),
                    'isExpired'      => $mandate->isExpired(),
                ],
                'history'    => array_map(static fn ($h) => $h->toArray(), $history),
                'validation' => [
                    'isValidForTransaction' => $isValid,
                    'canUseSequenceType'    => 'RCUR',
                ],
            ]);
            $response->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            return $response;
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Demo Mandate Lifecycle - Create, suspend, reactivate, revoke.
     *
     * @param MandateService $mandateService Mandate service
     */
    #[Route('/demo-mandate-lifecycle', name: 'demo_mandate_lifecycle')]
    public function demoMandateLifecycle(MandateService $mandateService): JsonResponse
    {
        try {
            // Create mandate
            $mandate1 = $mandateService->createMandate(
                'MANDATE-LIFECYCLE-001',
                new DateTime('2024-01-15'),
                'ES9121000418450200051332',
                'John Doe',
            );

            // Suspend mandate
            $mandateService->suspendMandate('MANDATE-LIFECYCLE-001');
            $suspendedMandate = $mandateService->findMandate('MANDATE-LIFECYCLE-001');

            // Reactivate mandate
            $mandateService->reactivateMandate('MANDATE-LIFECYCLE-001');
            $reactivatedMandate = $mandateService->findMandate('MANDATE-LIFECYCLE-001');

            // Revoke mandate
            $mandateService->revokeMandate('MANDATE-LIFECYCLE-001', 'Customer request');
            $revokedMandate = $mandateService->findMandate('MANDATE-LIFECYCLE-001');

            $response = new JsonResponse([
                'message' => 'Mandate lifecycle demonstration',
                'stages'  => [
                    'created' => [
                        'status'   => $mandate1->getStatus()->value,
                        'isActive' => $mandate1->isActive(),
                    ],
                    'suspended' => [
                        'status'   => $suspendedMandate->getStatus()->value,
                        'isActive' => $suspendedMandate->isActive(),
                    ],
                    'reactivated' => [
                        'status'   => $reactivatedMandate->getStatus()->value,
                        'isActive' => $reactivatedMandate->isActive(),
                    ],
                    'revoked' => [
                        'status'           => $revokedMandate->getStatus()->value,
                        'isActive'         => $revokedMandate->isActive(),
                        'revocationDate'   => $revokedMandate->getRevocationDate()?->format('Y-m-d H:i:s'),
                        'revocationReason' => $revokedMandate->getRevocationReason(),
                    ],
                ],
                'history' => array_map(static fn ($h) => $h->toArray(), $mandateService->getMandateHistory('MANDATE-LIFECYCLE-001')),
            ]);
            $response->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            return $response;
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Demo Mandate Sequence Type Transitions.
     *
     * @param MandateService $mandateService Mandate service
     */
    #[Route('/demo-mandate-sequence-transitions', name: 'demo_mandate_sequence_transitions')]
    public function demoMandateSequenceTransitions(MandateService $mandateService): JsonResponse
    {
        try {
            // Create mandate with FRST
            $mandate = $mandateService->createMandate(
                'MANDATE-SEQ-001',
                new DateTime('2024-01-15'),
                'ES9121000418450200051332',
                'John Doe',
                'CORE',
                'FRST',
            );

            // Valid transitions from FRST
            $validFromFirst = [
                'FRST → RCUR' => $mandateService->isValidSequenceTransition('FRST', 'RCUR'),
                'FRST → FNAL' => $mandateService->isValidSequenceTransition('FRST', 'FNAL'),
                'FRST → OOFF' => $mandateService->isValidSequenceTransition('FRST', 'OOFF'),
            ];

            // Update to RCUR
            $mandateService->updateSequenceType('MANDATE-SEQ-001', 'RCUR');
            $mandateRcur = $mandateService->findMandate('MANDATE-SEQ-001');

            // Valid transitions from RCUR
            $validFromRcur = [
                'RCUR → RCUR' => $mandateService->isValidSequenceTransition('RCUR', 'RCUR'),
                'RCUR → FNAL' => $mandateService->isValidSequenceTransition('RCUR', 'FNAL'),
                'RCUR → FRST' => $mandateService->isValidSequenceTransition('RCUR', 'FRST'),
            ];

            $response = new JsonResponse([
                'message' => 'Mandate sequence type transitions demonstration',
                'mandate' => [
                    'id'                  => $mandate->getMandateId(),
                    'initialSequenceType' => 'FRST',
                    'currentSequenceType' => $mandateRcur->getSequenceType(),
                ],
                'validTransitions' => [
                    'from_FRST' => $validFromFirst,
                    'from_RCUR' => $validFromRcur,
                ],
                'note' => 'SEPA mandates follow strict sequence type rules: FRST → RCUR/FNAL, RCUR → RCUR/FNAL, OOFF → FNAL, FNAL is terminal',
            ]);
            $response->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            return $response;
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
