<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator\Constraint;

use Nowo\SepaPaymentBundle\Validator\BicValidator as BicValidatorService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use function is_string;

/**
 * BIC constraint validator.
 * Uses BicValidator service for validation logic.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class BicValidator extends ConstraintValidator
{
    /**
     * Constructor.
     *
     * @param BicValidatorService $bicValidator BIC validator service
     */
    public function __construct(
        /**
         * BIC validator service.
         */
        private readonly BicValidatorService $bicValidator
    ) {
    }

    /**
     * {@inheritdoc}
     * PHPStan: missingType.parameter — $value untyped; ConstraintValidator API uses mixed, type in PHPDoc.
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Bic) {
            throw new UnexpectedTypeException($constraint, Bic::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        if (!$this->bicValidator->isValid($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(Bic::INVALID_BIC_ERROR)
                ->addViolation();
        }
    }
}
