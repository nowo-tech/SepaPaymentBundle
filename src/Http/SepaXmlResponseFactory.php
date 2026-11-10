<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Http;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

use function preg_replace;
use function str_replace;

/**
 * Wraps generated SEPA XML in a download response (standard Symfony controllers). The generator ports return plain
 * strings; this is the only HTTP-aware piece, so hexagonal hosts never need it.
 *
 *     return $responseFactory->createResponse($generator->generate($data), 'direct-debit.xml');
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
final class SepaXmlResponseFactory
{
    public const SERVICE_NAME = 'nowo_sepa_payment.http.sepa_xml_response_factory';

    /**
     * Creates an HTTP response with the XML as an attachment.
     *
     * @param string $xml The XML content
     * @param string $filename The download filename (e.g. "direct-debit.xml"); path separators are replaced
     */
    public function createResponse(string $xml, string $filename): Response
    {
        return new Response($xml, Response::HTTP_OK, [
            'Content-Type'        => 'application/xml',
            'Content-Disposition' => self::attachmentDisposition($filename),
        ]);
    }

    /**
     * Builds a safe Content-Disposition attachment header value (RFC 6266) for the given filename.
     * Path separators are replaced and a plain-ASCII fallback is added for non-ASCII names.
     *
     * @param string $filename The requested download filename
     */
    public static function attachmentDisposition(string $filename): string
    {
        $filename = str_replace(['/', '\\'], '_', $filename);
        $fallback = (string) preg_replace('/[^\x20-\x7e]|[%"]/', '_', $filename);

        return HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename, $fallback);
    }
}
