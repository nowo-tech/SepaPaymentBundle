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
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class NowoSepaPaymentBundle extends Bundle
{
    /**
     * Overridden to allow for the custom extension alias.
     * PHPStan: Parent Bundle::getContainerExtension() can return ExtensionInterface|false; we always return the extension.
     * Fix: return extension only if it is ExtensionInterface (normalize false to null for declared type).
     *
     * @return ExtensionInterface|null The container extension instance, or null if not available
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        if ($this->extension === null) {
            $this->extension = new NowoSepaPaymentExtension();
        }

        $ext = $this->extension;

        return $ext instanceof ExtensionInterface ? $ext : null;
    }
}
