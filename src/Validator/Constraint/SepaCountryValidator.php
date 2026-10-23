<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator\Constraint;

use Nowo\SepaPaymentBundle\Validator\SepaCountryValidator as SepaCountryValidatorService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use function is_string;

/**
 * SEPA Country constraint validator.
 * Uses SepaCountryValidator service for validation logic.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 *
 * @final since 1.5, will be final in 2.0 (not an extension point; decorate or replace the service instead)
 */
class SepaCountryValidator extends ConstraintValidator
{
    /**
     * Constructor.
     *
     * @param SepaCountryValidatorService $sepaCountryValidator SEPA Country validator service
     */
    public function __construct(
        /**
         * SEPA Country validator service.
         */
        private readonly SepaCountryValidatorService $sepaCountryValidator
    ) {
    }

    /**
     * {@inheritdoc}
     */
    /** PHPStan: missingType.parameter — $value typed as mixed. */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SepaCountry) {
            throw new UnexpectedTypeException($constraint, SepaCountry::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        if (!$this->sepaCountryValidator->isSepaCountry($value)) {
            $this->context->buildViolation($constraint->message)
                ->setTranslationDomain($constraint->translationDomain)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(SepaCountry::INVALID_SEPA_COUNTRY_ERROR)
                ->addViolation();
        }
    }
}
