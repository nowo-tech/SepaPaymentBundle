<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator\Constraint;

use Nowo\SepaPaymentBundle\Validator\IbanValidator as IbanValidatorService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * IBAN constraint validator.
 * Uses IbanValidator service for validation logic.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class IbanValidator extends ConstraintValidator
{
    /**
     * IBAN validator service.
     *
     * @var IbanValidatorService
     */
    private IbanValidatorService $ibanValidator;

    /**
     * Constructor.
     *
     * @param IbanValidatorService $ibanValidator IBAN validator service
     */
    public function __construct(IbanValidatorService $ibanValidator)
    {
        $this->ibanValidator = $ibanValidator;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Iban) {
            throw new UnexpectedTypeException($constraint, Iban::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        if (!$this->ibanValidator->isValid($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(Iban::INVALID_IBAN_ERROR)
                ->addViolation();
        }
    }
}
