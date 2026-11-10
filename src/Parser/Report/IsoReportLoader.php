<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Parser\Report;

use DateTimeImmutable;
use DOMDocument;
use DOMDocumentType;
use DOMElement;
use DOMNode;
use DOMXPath;
use Exception;
use InvalidArgumentException;
use LibXMLError;
use Nowo\SepaPaymentBundle\Model\Amount;
use Nowo\SepaPaymentBundle\Model\Report\StatusReason;
use Nowo\SepaPaymentBundle\Validator\XsdValidator;

use function array_map;
use function array_slice;
use function implode;
use function in_array;
use function libxml_clear_errors;
use function libxml_get_errors;
use function libxml_use_internal_errors;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;
use function trim;

use const LIBXML_NONET;

/**
 * Safe loading of ISO 20022 bank reports for the report parsers: size cap, no network, no DTD, a supported namespace
 * and validation against the official XSD shipped with the bundle. Stateless (worker safe).
 *
 * @internal
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class IsoReportLoader
{
    /** Namespace prefix registered on the returned XPath. */
    public const PREFIX = 'r';

    /**
     * Loads and validates a report.
     *
     * @param string $xml The XML document
     * @param list<string> $versions Supported versions (e.g. "pain.002.001.10"), each with a shipped XSD
     * @param int $maxBytes Largest accepted document
     *
     * @throws InvalidArgumentException If the document is too large, not well formed, has a DTD, uses another
     *                                  namespace or is not valid against its schema
     *
     * @return array{DOMXPath, string} The XPath (namespace prefix {@see self::PREFIX}) and the version
     */
    public static function load(string $xml, array $versions, int $maxBytes): array
    {
        if (strlen($xml) > $maxBytes) {
            throw new InvalidArgumentException(sprintf('The report is too large (%d bytes, limit %d).', strlen($xml), $maxBytes));
        }
        if (trim($xml) === '') {
            throw new InvalidArgumentException('The report is empty.');
        }

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
        try {
            $dom = new DOMDocument();
            // LIBXML_NONET: never fetch anything; no LIBXML_NOENT / DTDLOAD, so no entity expansion from a DTD
            if (!$dom->loadXML($xml, LIBXML_NONET)) {
                throw new InvalidArgumentException('The report is not well-formed XML: ' . self::errors());
            }
            if ($dom->doctype instanceof DOMDocumentType) {
                throw new InvalidArgumentException('The report must not contain a document type declaration (DTD).');
            }

            $namespace = (string) $dom->documentElement?->namespaceURI;
            $version   = substr($namespace, strlen('urn:iso:std:iso:20022:tech:xsd:'));
            if (!str_starts_with($namespace, 'urn:iso:std:iso:20022:tech:xsd:') || !in_array($version, $versions, true)) {
                throw new InvalidArgumentException(sprintf('Unsupported report namespace "%s": expected one of %s.', $namespace, implode(', ', $versions)));
            }

            libxml_clear_errors();
            if (!$dom->schemaValidate(XsdValidator::getSchemaDirectory() . '/' . $version . '.xsd')) {
                throw new InvalidArgumentException(sprintf('The report is not valid against the %s schema: %s', $version, self::errors()));
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace(self::PREFIX, $namespace);

        return [$xpath, $version];
    }

    /**
     * The elements matching a relative path (each step prefixed with "r:").
     *
     * @return list<DOMElement>
     */
    public static function elements(DOMXPath $xpath, string $path, ?DOMNode $context = null): array
    {
        $nodes = $xpath->query($path, $context);
        $list  = [];
        foreach ($nodes === false ? [] : $nodes as $node) {
            if ($node instanceof DOMElement) {
                $list[] = $node;
            }
        }

        return $list;
    }

    /**
     * The text of the first element matching the path, or null.
     */
    public static function text(DOMXPath $xpath, string $path, ?DOMNode $context = null): ?string
    {
        $element = self::elements($xpath, $path, $context)[0] ?? null;

        return $element?->textContent;
    }

    /**
     * The texts of every element matching the path.
     *
     * @return list<string>
     */
    public static function texts(DOMXPath $xpath, string $path, ?DOMNode $context = null): array
    {
        return array_map(static fn (DOMElement $element): string => $element->textContent, self::elements($xpath, $path, $context));
    }

    /**
     * An ISO date or date-time, or null.
     */
    public static function date(?string $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return new DateTimeImmutable($value);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Cents of a schema-valid decimal amount ("12.5" → 1250; digits beyond the cent are rounded half up).
     */
    public static function cents(?string $value): ?int
    {
        return $value === null ? null : Amount::centsFromDecimal(trim($value));
    }

    /**
     * The reasons under a context (`StsRsnInf` or `RtrInf` elements).
     *
     * @return list<StatusReason>
     */
    public static function reasons(DOMXPath $xpath, string $path, DOMNode $context): array
    {
        $p = self::PREFIX . ':';

        return array_map(static fn (DOMElement $element): StatusReason => new StatusReason(
            self::text($xpath, $p . 'Rsn/' . $p . 'Cd', $element),
            self::text($xpath, $p . 'Rsn/' . $p . 'Prtry', $element),
            self::text($xpath, $p . 'Orgtr/' . $p . 'Nm', $element),
            self::texts($xpath, $p . 'AddtlInf', $element),
        ), self::elements($xpath, $path, $context));
    }

    /**
     * The first libxml errors as one line.
     */
    private static function errors(): string
    {
        $errors = array_map(static fn (LibXMLError $error): string => sprintf('line %d: %s', $error->line, trim($error->message)), array_slice(libxml_get_errors(), 0, 5));

        return $errors === [] ? 'unknown error' : implode('; ', $errors);
    }
}
