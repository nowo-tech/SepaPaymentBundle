<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched before validation operations.
 * Allows listeners to modify validation behavior or add custom validation logic.
 *
 * The bundle's validators never dispatch this event (none has since its introduction in 1.1.0); it only exists for hosts that
 * dispatch it themselves around their own validation.
 *
 * @deprecated since 2.4.0, removed in 3.0: dispatch your own framework-free event instead
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class BeforeValidationEvent extends Event
{
    /**
     * Validation result (can be modified by listeners).
     */
    private ?bool $result = null;

    /**
     * Constructor.
     *
     * @param string $validationType Validation type
     * @param string $value Value to validate
     */
    public function __construct(
        /**
         * Validation type (e.g., 'iban', 'bic', 'credit_card').
         */
        private readonly string $validationType,
        /**
         * Value to validate.
         */
        private readonly string $value
    ) {
    }

    /**
     * Gets the validation type.
     *
     * @return string The validation type
     */
    public function getValidationType(): string
    {
        return $this->validationType;
    }

    /**
     * Gets the value to validate.
     *
     * @return string The value
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Gets the validation result.
     *
     * @return bool|null The validation result or null if not set
     */
    public function getResult(): ?bool
    {
        return $this->result;
    }

    /**
     * Sets the validation result.
     *
     * @param bool $result The validation result
     */
    public function setResult(bool $result): void
    {
        // @igor-ignore - Request-scoped Event DTO; not a shared worker service.
        $this->result = $result;
    }

    /**
     * Checks if validation result has been set.
     *
     * @return bool True if result is set, false otherwise
     */
    public function hasResult(): bool
    {
        return $this->result !== null;
    }
}
