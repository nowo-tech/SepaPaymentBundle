<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Fixtures\Bc;

use DateTimeImmutable;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitData;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitTransaction;

use function sprintf;

/**
 * Direct debit inputs written with the 2.1.0 API only (two-decimal amounts, creditor BIC given). The golden files in
 * `golden-2.1.0/` were produced from these inputs by the v2.1.0 tag (`DirectDebitGenerator` with its default
 * configuration); `DirectDebitBackwardCompatibilityTest` checks that 2.2 writes the same bytes.
 *
 * Regenerate (only from the v2.1.0 sources): see `tests/Fixtures/Bc/README.md`.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class DirectDebitBcCases
{
    /** Valid IBANs used as debtors. */
    private const IBANS = [
        'GB82WEST12345698765432',
        'DE89370400440532013000',
        'FR1420041010050500013M02606',
        'NL91ABNA0417164300',
        'IT60X0542811101000000123456',
        'BE68539007547034',
        'AT611904300234573201',
        'ES7921000813610123456789',
    ];

    /** BICs (null: no BIC, written as NOTPROVIDED by both versions). */
    private const BICS = ['NWBKGB2LXXX', 'COBADEFFXXX', null, 'ABNANL2A', null, 'GEBABEBB', 'BKAUATWW', 'CAIXESBBXXX'];

    /**
     * Object inputs, by case name.
     *
     * @return array<string, DirectDebitData>
     */
    public static function objectCases(): array
    {
        $cases = [];

        $single = self::data('MSG-BC-001', 'PMT-BC-001', 'FRST', 'CORE');
        $single->addTransaction(self::transaction('1500.25', 0, 'E2E-BC-001')->setRemittanceInformation('Invoice 2026-0001'));
        $cases['core_frst_single'] = $single;

        // 150 transactions; two-decimal amounts from 0.01 to the EPC maximum, from a fixed linear congruential sequence
        $many  = self::data('MSG-BC-002', 'PMT-BC-002', 'RCUR', 'CORE');
        $state = 20261110;
        for ($i = 0; $i < 150; ++$i) {
            $state = ($state * 1103515245 + 12345) % 2147483648;
            $cents = match ($i) {
                0       => 1,
                1       => 99_999_999_999,
                2       => 10,
                3       => 100,
                default => ($state % 3 === 0) ? $state % 100_000_000 + 1 : ($state * 46) % 99_999_999_999 + 1,
            };
            $transaction = DirectDebitTransaction::fromCents(
                $cents,
                self::IBANS[$i % 8],
                sprintf('Debtor %03d', $i),
                sprintf('MNDT-%05d', $i),
                new DateTimeImmutable('2025-0' . (1 + $i % 9) . '-15'),
                sprintf('E2E-BC-2-%04d', $i),
            );
            if (self::BICS[$i % 8] !== null) {
                $transaction->setDebtorBic((string) self::BICS[$i % 8]);
            }
            if ($i % 3 === 0) {
                $transaction->setRemittanceInformation(sprintf('Receipt %d of the remittance', $i));
            }
            $many->addTransaction($transaction);
        }
        $cases['core_rcur_150_lcg'] = $many;

        $addresses = self::data('MSG-BC-003', 'PMT-BC-003', 'OOFF', 'B2B');
        $addresses->setCreditorAddress('Calle Mayor 1', 'Madrid', '28001', 'ES');
        $addresses->addTransaction(self::transaction('42', 1, 'E2E-BC-3-1')->setDebtorAddress('Hauptstrasse 5', 'Berlin', '10115', 'DE'));
        $addresses->addTransaction(self::transaction('0.99', 2, 'E2E-BC-3-2'));
        $addresses->addTransaction(self::transaction('1234567.80', 3, 'E2E-BC-3-3')->setDebtorAddress(['street' => 'Damrak 1', 'city' => 'Amsterdam', 'postalCode' => '1012', 'country' => 'NL']));
        $cases['b2b_ooff_addresses'] = $addresses;

        $floats = self::data('MSG-BC-004', 'PMT-BC-004', 'FNAL', 'CORE');
        foreach ([0.1, 19.99, 1234.56, 0.3, 999999999.99, 7.05] as $i => $amount) {
            // Float input (deprecated since 2.1, silenced): two decimals give exactly the same cents
            $floats->addTransaction(self::transaction($amount, $i, 'E2E-BC-4-' . $i));
        }
        $cases['fnal_float_amounts'] = $floats;

        $escaping = self::data('MSG-BC-005', 'PMT-BC-005', 'FRST', 'CORE');
        $escaping->addTransaction(self::transaction('10.00', 4, 'E2E-BC-5-1')->setRemittanceInformation('Fees & charges <2026>'));
        $cases['frst_escaping'] = $escaping;

        return $cases;
    }

    /**
     * Array inputs for generateFromArray(), by case name.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function arrayCases(): array
    {
        return [
            'array_snake_case' => [
                'message_id'            => 'MSG-BC-006',
                'initiating_party_name' => 'Nowo Creditor SL',
                'payment_name'          => 'PMT-BC-006',
                'due_date'              => '2026-12-01',
                'creditor_name'         => 'Nowo Creditor SL',
                'creditor_iban'         => 'ES9121000418450200051332',
                'creditor_bic'          => 'CAIXESBBXXX',
                'sequence_type'         => 'RCUR',
                'creditor_id'           => 'ES59ZZZX1234567',
                'instrument_code'       => 'CORE',
                'items'                 => [
                    ['amount' => '1500.25', 'debtor_iban' => 'GB82WEST12345698765432', 'debtor_name' => 'John Doe', 'debtor_mandate' => 'M-1', 'debtor_mandate_sign_date' => '2025-01-01', 'instruction_id' => 'E2E-BC-6-1', 'information' => 'Invoice 1'],
                    ['amount' => 19.99, 'debtorBic' => 'COBADEFFXXX', 'debtor_iban' => 'DE89370400440532013000', 'debtor_name' => 'Erika Mustermann', 'debtor_mandate' => 'M-2', 'debtor_mandate_sign_date' => '2025-02-01', 'instruction_id' => 'E2E-BC-6-2'],
                    ['amount_cents' => 1, 'debtor_iban' => 'FR1420041010050500013M02606', 'debtor_name' => 'Jean Dupont', 'debtor_mandate' => 'M-3', 'debtor_mandate_sign_date' => '2025-03-01', 'instruction_id' => 'E2E-BC-6-3', 'debtor_address' => ['street' => 'Rue de Rivoli 1', 'city' => 'Paris', 'postalCode' => '75001', 'country' => 'FR']],
                    ['amount' => 250, 'debtor_iban' => 'NL91ABNA0417164300', 'debtor_name' => 'Jan Jansen', 'debtor_mandate' => 'M-4', 'debtor_mandate_sign_date' => '2025-04-01', 'instruction_id' => 'E2E-BC-6-4'],
                ],
            ],
        ];
    }

    /**
     * Replaces the creation date and time (always "now" in 2.1.0) with a fixed marker.
     */
    public static function normalise(string $xml): string
    {
        return (string) preg_replace('#<CreDtTm>[^<]+</CreDtTm>#', '<CreDtTm>NORMALISED</CreDtTm>', $xml);
    }

    private static function data(string $messageId, string $paymentInfoId, string $sequenceType, string $instrument): DirectDebitData
    {
        $data = new DirectDebitData(
            $messageId,
            'Nowo Creditor SL',
            $paymentInfoId,
            new DateTimeImmutable('2026-12-01'),
            'Nowo Creditor SL',
            'ES9121000418450200051332',
            $sequenceType,
            'ES59ZZZX1234567',
            $instrument,
        );
        $data->setCreditorBic('CAIXESBBXXX');

        return $data;
    }

    private static function transaction(float|int|string $amount, int $debtor, string $endToEndId): DirectDebitTransaction
    {
        $transaction = new DirectDebitTransaction(
            $amount,
            self::IBANS[$debtor],
            'Debtor ' . $debtor,
            'MNDT-' . $endToEndId,
            new DateTimeImmutable('2025-06-30'),
            $endToEndId,
        );
        if (self::BICS[$debtor] !== null) {
            $transaction->setDebtorBic((string) self::BICS[$debtor]);
        }

        return $transaction;
    }
}
