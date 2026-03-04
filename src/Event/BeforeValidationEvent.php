<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched before validation operations.
 * Allows listeners to modify validation behavior or add custom validation logic.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class BeforeValidationEvent extends Event
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
