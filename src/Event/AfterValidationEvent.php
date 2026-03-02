<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched after validation operations.
 * Allows listeners to log, monitor, or react to validation results.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class AfterValidationEvent extends Event
{
    /**
     * Validation type (e.g., 'iban', 'bic', 'credit_card').
     */
    private string $validationType;

    /**
     * Value that was validated.
     */
    private string $value;

    /**
     * Validation result.
     */
    private bool $result;

    /**
     * Constructor.
     *
     * @param string $validationType Validation type
     * @param string $value Value that was validated
     * @param bool $result Validation result
     */
    public function __construct(string $validationType, string $value, bool $result)
    {
        $this->validationType = $validationType;
        $this->value          = $value;
        $this->result         = $result;
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
     * Gets the value that was validated.
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
     * @return bool The validation result
     */
    public function getResult(): bool
    {
        return $this->result;
    }
}
