<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Validator;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * XSD Schema validator for SEPA XML files.
 * Validates XML content against official SEPA XSD schemas (ISO 20022).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class XsdValidator
{
    public const SERVICE_NAME = 'nowo_sepa_payment.validator.xsd_validator';

    /**
     * Translator instance.
     *
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

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
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Validates XML content against an XSD schema.
     *
     * @param string      $xml        The XML content to validate
     * @param string|null $xsdPath    Path to the XSD schema file (optional, will use default if null)
     * @param string      $schemaType Type of schema: 'credit_transfer' or 'direct_debit'
     *
     * @throws \InvalidArgumentException If the XML is invalid or schema file is not found
     *
     * @return bool True if the XML is valid against the schema
     */
    public function validate(string $xml, ?string $xsdPath = null, string $schemaType = 'credit_transfer'): bool
    {
        $dom = new \DOMDocument();

        // Load XML with error handling
        libxml_use_internal_errors(true);
        $loaded = @$dom->loadXML($xml);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        if (!$loaded) {
            $errorMessages = array_map(fn ($error) => trim($error->message), $errors);
            $errorsString = implode('; ', $errorMessages);
            $message = $this->translator->trans('validation.invalid_xml_format', ['%errors%' => $errorsString], 'nowo_sepa_payment');

            throw new \InvalidArgumentException($message);
        }

        // If no XSD path provided, try to use default schema
        if (null === $xsdPath) {
            $xsdPath = $this->getDefaultSchemaPath($schemaType);
        }

        // Validate against XSD schema
        if (null !== $xsdPath && file_exists($xsdPath)) {
            libxml_use_internal_errors(true);
            $valid = @$dom->schemaValidate($xsdPath);
            $errors = libxml_get_errors();
            libxml_clear_errors();
            libxml_use_internal_errors(false);

            if (!$valid && !empty($errors)) {
                $errorMessages = array_map(fn ($error) => trim($error->message), $errors);
                $errorsString = implode('; ', $errorMessages);
                $message = $this->translator->trans('validation.xsd_validation_failed', ['%errors%' => $errorsString], 'nowo_sepa_payment');

                throw new \InvalidArgumentException($message);
            }

            return $valid;
        }

        // If schema file doesn't exist, return true (validation skipped)
        // This allows the validator to work even if XSD files are not available
        return true;
    }

    /**
     * Validates Credit Transfer XML against pain.001.001.03 schema.
     *
     * @param string      $xml     The XML content to validate
     * @param string|null $xsdPath Optional path to XSD schema file
     *
     * @throws \InvalidArgumentException If the XML is invalid
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
     * @param string      $xml     The XML content to validate
     * @param string|null $xsdPath Optional path to XSD schema file
     *
     * @throws \InvalidArgumentException If the XML is invalid
     *
     * @return bool True if the XML is valid
     */
    public function validateDirectDebit(string $xml, ?string $xsdPath = null): bool
    {
        return $this->validate($xml, $xsdPath, 'direct_debit');
    }

    /**
     * Gets the default XSD schema path for a given schema type.
     *
     * @param string $schemaType Type of schema: 'credit_transfer' or 'direct_debit'
     *
     * @return string|null Path to the schema file, or null if not found
     */
    private function getDefaultSchemaPath(string $schemaType): ?string
    {
        $basePath = __DIR__ . '/../../Resources/schemas/';

        $schemas = [
            'credit_transfer' => $basePath . 'pain.001.001.03.xsd',
            'direct_debit' => $basePath . 'pain.008.001.02.xsd',
        ];

        $path = $schemas[$schemaType] ?? null;

        return ($path && file_exists($path)) ? $path : null;
    }

    /**
     * Validates XML content against an XSD schema string.
     *
     * @param string $xml        The XML content to validate
     * @param string $xsdContent The XSD schema content as string
     *
     * @throws \InvalidArgumentException If the XML is invalid
     *
     * @return bool True if the XML is valid against the schema
     */
    public function validateAgainstSchemaString(string $xml, string $xsdContent): bool
    {
        $dom = new \DOMDocument();

        // Load XML with error handling
        libxml_use_internal_errors(true);
        $loaded = @$dom->loadXML($xml);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        if (!$loaded) {
            $errorMessages = array_map(fn ($error) => trim($error->message), $errors);
            $errorsString = implode('; ', $errorMessages);
            $message = $this->translator->trans('validation.invalid_xml_format', ['%errors%' => $errorsString], 'nowo_sepa_payment');

            throw new \InvalidArgumentException($message);
        }

        // Validate against XSD schema string
        libxml_use_internal_errors(true);
        $valid = @$dom->schemaValidateSource($xsdContent);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        if (!$valid && !empty($errors)) {
            $errorMessages = array_map(fn ($error) => trim($error->message), $errors);
            $errorsString = implode('; ', $errorMessages);
            $message = $this->translator->trans('validation.xsd_validation_failed', ['%errors%' => $errorsString], 'nowo_sepa_payment');

            throw new \InvalidArgumentException($message);
        }

        return $valid;
    }
}
