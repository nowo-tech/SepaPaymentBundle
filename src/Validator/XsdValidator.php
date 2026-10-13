<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator;

use DOMDocument;
use InvalidArgumentException;
use LibXMLError;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\Translation\TranslatorInterface;

use function dirname;
use function is_file;
use function str_starts_with;

/**
 * XSD Schema validator for SEPA XML files.
 * Validates XML content against official SEPA XSD schemas (ISO 20022).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class XsdValidator
{
    public const SERVICE_NAME = 'nowo_sepa_payment.validator.xsd_validator';

    /**
     * XSD schema path for Credit Transfer (pain.001.001.03).
     */
    public const XSD_CREDIT_TRANSFER = 'pain.001.001.03';

    /**
     * XSD schema path for Direct Debit (pain.008.001.02).
     */
    public const XSD_DIRECT_DEBIT = 'pain.008.001.02';

    /**
     * Constructor.
     *
     * @param TranslatorInterface $translator Translator for internationalized error messages
     */
    public function __construct(
        /**
         * Translator instance.
         */
        private readonly TranslatorInterface $translator
    ) {
    }

    /**
     * Validates XML content against an XSD schema.
     *
     * @param string $xml The XML content to validate
     * @param string|null $xsdPath Path to the XSD schema file (optional, uses the bundled schema if null)
     * @param string $schemaType Type of schema: 'credit_transfer' or 'direct_debit'
     *
     * @throws InvalidArgumentException If the XML is malformed or invalid, or if the schema file is not found
     *
     * @return bool True if the XML is valid against the schema
     */
    public function validate(string $xml, ?string $xsdPath = null, string $schemaType = 'credit_transfer'): bool
    {
        $dom = new DOMDocument();

        // Load XML with error handling
        $previousUseErrors = libxml_use_internal_errors(true);
        try {
            $loaded = @$dom->loadXML($xml);
            $errors = libxml_get_errors();
            libxml_clear_errors();
        } finally {
            libxml_use_internal_errors($previousUseErrors);
        }

        if (!$loaded) {
            $errorMessages = array_map(static fn (LibXMLError $error): string => trim($error->message), $errors);
            $errorsString  = implode('; ', $errorMessages);
            $message       = $this->translator->trans('validation.invalid_xml_format', ['%errors%' => $errorsString], 'NowoSepaPaymentBundle');

            throw new InvalidArgumentException($message);
        }

        // If no XSD path provided, use the bundled schema matching the document namespace (or the type default)
        if ($xsdPath === null) {
            $xsdPath = $this->getDefaultSchemaPath($schemaType, $dom->documentElement?->namespaceURI);
        }

        // Validation was requested: a missing schema must never be reported as "valid"
        if ($xsdPath === null || !is_file($xsdPath)) {
            $message = $this->translator->trans('validation.xsd_schema_not_found', ['%path%' => $xsdPath ?? $schemaType], 'NowoSepaPaymentBundle');

            throw new InvalidArgumentException($message);
        }

        // Validate against XSD schema
        $previousUseErrors = libxml_use_internal_errors(true);
        try {
            $valid  = @$dom->schemaValidate($xsdPath);
            $errors = libxml_get_errors();
            libxml_clear_errors();
        } finally {
            libxml_use_internal_errors($previousUseErrors);
        }

        if (!$valid) {
            $errorMessages = array_map(static fn (LibXMLError $error): string => trim($error->message), $errors);
            $errorsString  = implode('; ', $errorMessages);
            $message       = $this->translator->trans('validation.xsd_validation_failed', ['%errors%' => $errorsString], 'NowoSepaPaymentBundle');

            throw new InvalidArgumentException($message);
        }

        return true;
    }

    /**
     * Validates Credit Transfer XML against pain.001.001.03 schema.
     *
     * @param string $xml The XML content to validate
     * @param string|null $xsdPath Optional path to XSD schema file
     *
     * @throws InvalidArgumentException If the XML is invalid
     *
     * @return bool True if the XML is valid
     */
    public function validateCreditTransfer(string $xml, ?string $xsdPath = null): bool
    {
        return $this->validate($xml, $xsdPath, 'credit_transfer');
    }

    /**
     * Validates Direct Debit XML against pain.008.001.02 schema.
     *
     * @param string $xml The XML content to validate
     * @param string|null $xsdPath Optional path to XSD schema file
     *
     * @throws InvalidArgumentException If the XML is invalid
     *
     * @return bool True if the XML is valid
     */
    public function validateDirectDebit(string $xml, ?string $xsdPath = null): bool
    {
        return $this->validate($xml, $xsdPath, 'direct_debit');
    }

    /**
     * Gets the bundled XSD schema path for a given schema type.
     * When the document namespace names a bundled schema of the same message family
     * (e.g. urn:iso:std:iso:20022:tech:xsd:pain.001.001.09 for credit transfers), that schema is used;
     * otherwise the type default (pain.001.001.03 / pain.008.001.02) is used.
     *
     * @param string $schemaType Type of schema: 'credit_transfer' or 'direct_debit'
     * @param string|null $namespace Namespace URI of the document root element
     *
     * @return string|null Path to the bundled schema file, or null if the schema type is unknown
     */
    private function getDefaultSchemaPath(string $schemaType, ?string $namespace = null): ?string
    {
        $defaults = [
            'credit_transfer' => self::XSD_CREDIT_TRANSFER,
            'direct_debit'    => self::XSD_DIRECT_DEBIT,
        ];

        if (!isset($defaults[$schemaType])) {
            return null;
        }

        $schemaId = $defaults[$schemaType];
        $family   = substr($schemaId, 0, 9); // "pain.001." or "pain.008."
        if ($namespace !== null && preg_match('/^urn:iso:std:iso:20022:tech:xsd:(pain\.\d{3}\.\d{3}\.\d{2})$/', $namespace, $matches) === 1
            && str_starts_with($matches[1], $family)) {
            $schemaId = $matches[1];
        }

        return self::getSchemaDirectory() . '/' . $schemaId . '.xsd';
    }

    /**
     * Gets the directory that holds the bundled ISO 20022 XSD schemas (src/Resources/schemas).
     *
     * @return string Absolute path to the schemas directory
     */
    public static function getSchemaDirectory(): string
    {
        return dirname(__DIR__) . '/Resources/schemas';
    }

    /**
     * Validates XML content against an XSD schema string.
     *
     * @param string $xml The XML content to validate
     * @param string $xsdContent The XSD schema content as string
     *
     * @throws InvalidArgumentException If the XML is invalid
     *
     * @return bool True if the XML is valid against the schema
     */
    public function validateAgainstSchemaString(string $xml, string $xsdContent): bool
    {
        $dom = new DOMDocument();

        // Load XML with error handling
        $previousUseErrors = libxml_use_internal_errors(true);
        try {
            $loaded = @$dom->loadXML($xml);
            $errors = libxml_get_errors();
            libxml_clear_errors();
        } finally {
            libxml_use_internal_errors($previousUseErrors);
        }

        if (!$loaded) {
            $errorMessages = array_map(static fn (LibXMLError $error): string => trim($error->message), $errors);
            $errorsString  = implode('; ', $errorMessages);
            $message       = $this->translator->trans('validation.invalid_xml_format', ['%errors%' => $errorsString], 'NowoSepaPaymentBundle');

            throw new InvalidArgumentException($message);
        }

        // Validate against XSD schema string
        $previousUseErrors = libxml_use_internal_errors(true);
        try {
            $valid  = @$dom->schemaValidateSource($xsdContent);
            $errors = libxml_get_errors();
            libxml_clear_errors();
        } finally {
            libxml_use_internal_errors($previousUseErrors);
        }

        if (!$valid && $errors !== []) {
            $errorMessages = array_map(static fn ($error): string => trim($error->message), $errors);
            $errorsString  = implode('; ', $errorMessages);
            $message       = $this->translator->trans('validation.xsd_validation_failed', ['%errors%' => $errorsString], 'NowoSepaPaymentBundle');

            throw new InvalidArgumentException($message);
        }

        return $valid;
    }
}
