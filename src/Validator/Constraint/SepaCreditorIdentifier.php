<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * SEPA Creditor Identifier validation constraint.
 * Validates SEPA Creditor Identifier format and check digits.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class SepaCreditorIdentifier extends Constraint
{
    public const INVALID_SEPA_CREDITOR_IDENTIFIER_ERROR = 'c7d8e9f0-1a2b-3c4d-5e6f-7a8b9c0d1e2f';

    public string $message = 'sepa_creditor_identifier.invalid';

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
