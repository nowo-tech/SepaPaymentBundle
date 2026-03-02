<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator\Constraint;

use Nowo\SepaPaymentBundle\Validator\CreditCardValidator as CreditCardValidatorService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use function is_string;

/**
 * Credit Card constraint validator.
 * Uses CreditCardValidator service for validation logic.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class CreditCardValidator extends ConstraintValidator
{
    /**
     * Credit Card validator service.
     */
    private CreditCardValidatorService $creditCardValidator;

    /**
     * Constructor.
     *
     * @param CreditCardValidatorService $creditCardValidator Credit Card validator service
     */
    public function __construct(CreditCardValidatorService $creditCardValidator)
    {
        $this->creditCardValidator = $creditCardValidator;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof CreditCard) {
            throw new UnexpectedTypeException($constraint, CreditCard::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        if (!$this->creditCardValidator->isValid($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(CreditCard::INVALID_CREDIT_CARD_ERROR)
                ->addViolation();
        }
    }
}
