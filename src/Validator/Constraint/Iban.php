<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator\Constraint;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * IBAN validation constraint.
 * Validates IBAN format and check digits according to ISO 13616 standard.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Iban extends Constraint
{
    public const INVALID_IBAN_ERROR = 'b6d7a4c1-3b2e-4f8a-9c5d-1e2f3a4b5c6d';

    public string $message = 'iban.invalid';

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
