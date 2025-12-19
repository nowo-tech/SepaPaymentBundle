<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Converter;

use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * CCC (Código Cuenta Cliente) to IBAN converter.
 * Converts Spanish CCC format to IBAN format.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class CccConverter
{
    public const SERVICE_NAME = 'nowo_sepa_payment.converter.ccc_converter';
    /**
     * Constructor.
     *
     * @param IbanValidator $ibanValidator IBAN validator instance
     */
    public function __construct(
        private IbanValidator $ibanValidator
    ) {
    }

    /**
     * Converts a Spanish CCC (Código Cuenta Cliente) to IBAN.
     * CCC format: EEEE OOOO DD NNNNNNNNNN (20 digits)
     * Where: EEEE = Bank code, OOOO = Branch code, DD = Check digits, NNNNNNNNNN = Account number
     *
     * @param string $ccc The CCC to convert (with or without spaces)
     *
     * @throws \InvalidArgumentException If the CCC format is invalid
     *
     * @return string The IBAN (ES + check digits + CCC)
     */
    public function cccToIban(string $ccc): string
    {
        // Remove spaces and normalize
        $ccc = str_replace(' ', '', trim($ccc));

        // Validate CCC format (20 digits)
        if (!preg_match('/^\d{20}$/', $ccc)) {
            throw new \InvalidArgumentException('Invalid CCC format. Expected 20 digits.');
        }

        // Calculate IBAN check digits
        // IBAN = ES + check digits (00) + CCC
        $ibanWithPlaceholder = 'ES00' . $ccc;
        $checkDigits = $this->ibanValidator->calculateCheckDigits($ibanWithPlaceholder);

        // Return complete IBAN
        return 'ES' . $checkDigits . $ccc;
    }

    /**
     * Validates a Spanish CCC (Código Cuenta Cliente).
     * CCC format: EEEE OOOO DD NNNNNNNNNN (20 digits)
     *
     * @param string $ccc The CCC to validate
     *
     * @return bool True if the CCC is valid, false otherwise
     */
    public function isValidCcc(string $ccc): bool
    {
        // Remove spaces
        $ccc = str_replace(' ', '', trim($ccc));

        // Check format (20 digits)
        if (!preg_match('/^\d{20}$/', $ccc)) {
            return false;
        }

        // Extract components
        $bankCode = substr($ccc, 0, 4);
        $branchCode = substr($ccc, 4, 4);
        $checkDigits = substr($ccc, 8, 2);
        $accountNumber = substr($ccc, 10, 10);

        // Validate check digits
        $calculatedCheckDigits = $this->calculateCccCheckDigits($bankCode, $branchCode, $accountNumber);

        return $calculatedCheckDigits === $checkDigits;
    }

    /**
     * Extracts bank code from CCC.
     *
     * @param string $ccc The CCC
     *
     * @return string The bank code (4 digits)
     */
    public function getBankCode(string $ccc): string
    {
        $normalized = str_replace(' ', '', trim($ccc));

        return substr($normalized, 0, 4);
    }

    /**
     * Extracts branch code from CCC.
     *
     * @param string $ccc The CCC
     *
     * @return string The branch code (4 digits)
     */
    public function getBranchCode(string $ccc): string
    {
        $normalized = str_replace(' ', '', trim($ccc));

        return substr($normalized, 4, 4);
    }

    /**
     * Extracts account number from CCC.
     *
     * @param string $ccc The CCC
     *
     * @return string The account number (10 digits)
     */
    public function getAccountNumber(string $ccc): string
    {
        $normalized = str_replace(' ', '', trim($ccc));

        return substr($normalized, 10, 10);
    }

    /**
     * Calculates CCC check digits.
     *
     * @param string $bankCode      Bank code (4 digits)
     * @param string $branchCode    Branch code (4 digits)
     * @param string $accountNumber Account number (10 digits)
     *
     * @return string The check digits (2 digits)
     */
    private function calculateCccCheckDigits(string $bankCode, string $branchCode, string $accountNumber): string
    {
        // Weights for bank and branch
        $weights1 = [4, 8, 5, 10, 9, 7, 3, 6];
        // Weights for account number
        $weights2 = [1, 2, 4, 8, 5, 10, 9, 7, 3, 6];

        // Calculate first check digit (bank + branch)
        $bankBranch = $bankCode . $branchCode;
        $sum1 = 0;
        for ($i = 0; $i < 8; ++$i) {
            $sum1 += (int) $bankBranch[$i] * $weights1[$i];
        }
        $checkDigit1 = 11 - ($sum1 % 11);
        if ($checkDigit1 === 11) {
            $checkDigit1 = 0;
        } elseif ($checkDigit1 === 10) {
            $checkDigit1 = 1;
        }

        // Calculate second check digit (account number)
        $sum2 = 0;
        for ($i = 0; $i < 10; ++$i) {
            $sum2 += (int) $accountNumber[$i] * $weights2[$i];
        }
        $checkDigit2 = 11 - ($sum2 % 11);
        if ($checkDigit2 === 11) {
            $checkDigit2 = 0;
        } elseif ($checkDigit2 === 10) {
            $checkDigit2 = 1;
        }

        return sprintf('%02d', $checkDigit1) . sprintf('%02d', $checkDigit2);
    }
}
