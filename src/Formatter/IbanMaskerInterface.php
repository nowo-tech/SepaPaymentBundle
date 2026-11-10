<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Formatter;

/**
 * Port: masks an IBAN for display, logs and exports. {@see IbanMasker} is the default implementation (aliased to
 * this interface).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
interface IbanMaskerInterface
{
    /**
     * Masks an IBAN, keeping the country code and check digits and the last characters: "ES91************1332".
     *
     * @param string $iban The IBAN (spaces and case are normalised)
     * @param int $visibleEnd How many trailing characters stay visible (0 to 4)
     */
    public function mask(string $iban, int $visibleEnd = 4): string;

    /**
     * Same as {@see self::mask()}, grouped by four characters: "ES91 **** **** **** **** 1332".
     */
    public function maskFormatted(string $iban, int $visibleEnd = 4): string;
}
