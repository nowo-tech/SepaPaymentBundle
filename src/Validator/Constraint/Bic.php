<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator\Constraint;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * BIC validation constraint.
 * Validates BIC format according to ISO 13616 standard.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Bic extends Constraint
{
    public const INVALID_BIC_ERROR = '4e3f8a2c-9b1d-4c7e-8f5a-2d6b0c1e9a73';

    public string $message = 'bic.invalid';

    public string $translationDomain = 'NowoSepaPaymentBundle';

    /**
     * {@inheritdoc}
     * PHPStan: missingType.iterableValue — $options must have iterable value type.
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
