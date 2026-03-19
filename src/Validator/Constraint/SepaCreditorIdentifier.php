<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator\Constraint;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * SEPA Creditor Identifier validation constraint.
 * Validates SEPA Creditor Identifier format and check digits.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class SepaCreditorIdentifier extends Constraint
{
    public const INVALID_SEPA_CREDITOR_IDENTIFIER_ERROR = 'c7d8e9f0-1a2b-3c4d-5e6f-7a8b9c0d1e2f';

    public string $message = 'sepa_creditor_identifier.invalid';

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
