<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator\Constraint;

use Nowo\SepaPaymentBundle\Validator\IbanValidator as IbanValidatorService;
use Nowo\SepaPaymentBundle\Validator\IbanValidatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use function is_string;

/**
 * IBAN constraint validator.
 * Uses IbanValidator service for validation logic.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class IbanValidator extends ConstraintValidator
{
    /**
     * Constructor.
     *
     * @param IbanValidatorService $ibanValidator IBAN validator service
     */
    public function __construct(
        /**
         * IBAN validator service.
         */
        private readonly IbanValidatorInterface $ibanValidator
    ) {
    }

    /**
     * {@inheritdoc}
     */
    /** PHPStan: missingType.parameter — $value typed as mixed. */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Iban) {
            throw new UnexpectedTypeException($constraint, Iban::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        if (!$this->ibanValidator->isValid($value)) {
            $this->context->buildViolation($constraint->message)
                ->setTranslationDomain($constraint->translationDomain)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(Iban::INVALID_IBAN_ERROR)
                ->addViolation();
        }
    }
}
