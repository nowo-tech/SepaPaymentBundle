<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Helper;

/**
 * Helper class for translating messages in tests.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class TranslationHelper
{
    /**
     * Translations mapping for English (GB).
     *
     * @var array<string, string>
     */
    private static array $translations = [
        'validation.missing_required_field'             => 'Missing required field: %field%',
        'validation.missing_required_transaction_field' => 'Missing required transaction field: %field%',
        'validation.invalid_creditor_iban'              => 'Invalid creditor IBAN: %iban%',
        'validation.invalid_debtor_iban'                => 'Invalid debtor IBAN: %iban%',
        'validation.invalid_iban'                       => 'Invalid IBAN: %iban%',
        'validation.invalid_bic'                        => 'Invalid BIC: %bic%',
        'validation.invalid_creditor_identifier'        => 'Invalid SEPA Creditor Identifier: %identifier%',
        'validation.invalid_xml_format'                 => 'Invalid XML format: %errors%',
        'validation.xsd_validation_failed'              => 'XSD validation failed: %errors%',
        'validation.generated_xml_failed_xsd'           => 'Generated XML failed XSD validation: %error%',
        'validation.invalid_date_type'                  => '%field% must be a string or DateTimeInterface',
        'validation.invalid_creation_date'              => 'creationDate must be a string or DateTimeInterface',
        'validation.invalid_execution_date'             => 'requestedExecutionDate must be a string or DateTimeInterface',
        'validation.invalid_keys_top_level'             => 'Invalid key(s) at top level: %keys%. At the top level (payment information), you must use "debtor*" keys (e.g., debtorIban, debtorName, debtorBic) to represent the company that pays. Suggested keys: %suggestions%. Note: "creditor*" keys should only be used within the "transactions" array (for beneficiaries that receive payments).',
        'validation.invalid_keys_transaction'           => 'Invalid key(s) in transaction: %keys%. Within transactions array, you must use "creditor*" keys (e.g., creditorIban, creditorName, creditorBic) to represent the beneficiary that receives the payment. Suggested keys: %suggestions%. Note: "debtor*" keys should only be used at the top level (for the company that pays).',
    ];

    /**
     * Translates a message ID with parameters.
     *
     * @param string $id Message ID
     * @param array<string, string> $parameters Parameters to replace
     * @param string|null $domain Translation domain (ignored for tests)
     *
     * @return string Translated message with parameters replaced
     */
    public static function translate(string $id, array $parameters = [], ?string $domain = null): string
    {
        $message = self::$translations[$id] ?? $id;

        foreach ($parameters as $key => $value) {
            // Symfony Translator passes parameters with % around the key (e.g., '%field%' => 'value')
            // So we need to replace both the key as-is and with % around it
            $message = str_replace($key, (string) $value, $message);
            // Also handle keys without % (just in case)
            $keyWithoutPercent = trim($key, '%');
            if ($keyWithoutPercent !== $key) {
                $message = str_replace('%' . $keyWithoutPercent . '%', (string) $value, $message);
            }
        }

        return $message;
    }

    /**
     * Creates a callback function for mocking TranslatorInterface.
     *
     * @return callable Callback function
     */
    public static function createTranslatorCallback(): callable
    {
        return static fn (string $id, array $parameters = [], ?string $domain = null): string => self::translate($id, $parameters, $domain);
    }
}
