<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator\Constraint;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * SEPA Country validation constraint.
 * Validates if a country code is a SEPA member country.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class SepaCountry extends Constraint
{
    public const INVALID_SEPA_COUNTRY_ERROR = 'e2f3a4b5-6c7d-8e9f-0a1b-2c3d4e5f6a7b';

    public string $message = 'sepa_country.invalid';

    public string $translationDomain = 'NowoSepaPaymentBundle';

    /**
     * {@inheritdoc}
     * PHPStan: missingType.iterableValue — value type for $options.
     *
     * @param array<string, mixed> $options
     */
    public function __construct(
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
        array $options = []
    ) {
        parent::__construct($options, $groups, $payload);

        $this->message = $message ?? $this->message;
    }
}
