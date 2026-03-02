<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator\Constraint;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * BIC validation constraint.
 * Validates BIC format according to ISO 13616 standard.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Bic extends Constraint
{
    public const INVALID_BIC_ERROR = 'a1b2c3d4-5e6f-7g8h-9i0j-k1l2m3n4o5p6';

    public string $message = 'bic.invalid';

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
