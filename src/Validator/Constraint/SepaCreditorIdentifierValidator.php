<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator\Constraint;

use Nowo\SepaPaymentBundle\Validator\SepaCreditorIdentifierValidator as SepaCreditorIdentifierValidatorService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * SEPA Creditor Identifier constraint validator.
 * Uses SepaCreditorIdentifierValidator service for validation logic.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class SepaCreditorIdentifierValidator extends ConstraintValidator
{
    /**
     * SEPA Creditor Identifier validator service.
     *
     * @var SepaCreditorIdentifierValidatorService
     */
    private SepaCreditorIdentifierValidatorService $sepaCreditorIdentifierValidator;

    /**
     * Constructor.
     *
     * @param SepaCreditorIdentifierValidatorService $sepaCreditorIdentifierValidator SEPA Creditor Identifier validator service
     */
    public function __construct(SepaCreditorIdentifierValidatorService $sepaCreditorIdentifierValidator)
    {
        $this->sepaCreditorIdentifierValidator = $sepaCreditorIdentifierValidator;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof SepaCreditorIdentifier) {
            throw new UnexpectedTypeException($constraint, SepaCreditorIdentifier::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        if (!$this->sepaCreditorIdentifierValidator->isValid($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(SepaCreditorIdentifier::INVALID_SEPA_CREDITOR_IDENTIFIER_ERROR)
                ->addViolation();
        }
    }
}
