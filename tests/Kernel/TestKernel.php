<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Kernel;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel;

use function dirname;

/**
 * Minimal kernel for integration tests.
 * Uses MicroKernelTrait; project dir is tests/Fixtures/app so config lives there.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * Project dir for the test app (tests/Fixtures/app). Ensures config is isolated.
     */
    public function getProjectDir(): string
    {
        return dirname(__DIR__) . '/Fixtures/app';
    }
}
