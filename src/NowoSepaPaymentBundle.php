<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle;

use Nowo\SepaPaymentBundle\DependencyInjection\NowoSepaPaymentExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Symfony bundle for SEPA payment management.
 * Provides tools for IBAN validation, mandate management, and SEPA credit transfer generation.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class NowoSepaPaymentBundle extends Bundle
{
    /**
     * Overridden to allow for the custom extension alias.
     * Creates and returns the container extension instance if not already created.
     *
     * @return ExtensionInterface|null The container extension instance, or null if not available
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new NowoSepaPaymentExtension();
        }

        return $this->extension;
    }
}
