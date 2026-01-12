<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator\Constraint;

use Nowo\SepaPaymentBundle\Validator\BicValidator as BicValidatorService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * BIC constraint validator.
 * Uses BicValidator service for validation logic.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class BicValidator extends ConstraintValidator
{
    /**
     * BIC validator service.
     *
     * @var BicValidatorService
     */
    private BicValidatorService $bicValidator;

    /**
     * Constructor.
     *
     * @param BicValidatorService $bicValidator BIC validator service
     */
    public function __construct(BicValidatorService $bicValidator)
    {
        $this->bicValidator = $bicValidator;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Bic) {
            throw new UnexpectedTypeException($constraint, Bic::class);
        }

        if (null === $value || '' === $value) {
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
