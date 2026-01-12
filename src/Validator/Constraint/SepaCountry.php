<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * SEPA Country validation constraint.
 * Validates if a country code is a SEPA member country.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class SepaCountry extends Constraint
{
    public const INVALID_SEPA_COUNTRY_ERROR = 'e2f3a4b5-6c7d-8e9f-0a1b-2c3d4e5f6a7b';

    public string $message = 'sepa_country.invalid';

    /**
     * {@inheritdoc}
     */
    public function __construct(
        ?string $message = null,
        ?array $groups = null,
        $payload = null,
        array $options = []
    ) {
        parent::__construct($options, $groups, $payload);

        $this->message = $message ?? $this->message;
    }
}
