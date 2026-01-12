<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Credit Card validation constraint.
 * Validates credit card numbers using the Luhn algorithm.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class CreditCard extends Constraint
{
    public const INVALID_CREDIT_CARD_ERROR = 'd1e2f3a4-5b6c-7d8e-9f0a-1b2c3d4e5f6a';

    public string $message = 'credit_card.invalid';

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
