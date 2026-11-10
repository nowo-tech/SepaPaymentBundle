<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Http;

use Nowo\SepaPaymentBundle\Http\SepaXmlResponseFactory;
use PHPUnit\Framework\TestCase;

/**
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class SepaXmlResponseFactoryTest extends TestCase
{
    public function testCreatesAnXmlAttachment(): void
    {
        $response = (new SepaXmlResponseFactory())->createResponse('<Document/>', 'a"b/../adeudo ñ.xml');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('<Document/>', $response->getContent());
        self::assertSame('application/xml', $response->headers->get('Content-Type'));
        $disposition = (string) $response->headers->get('Content-Disposition');
        self::assertStringStartsWith('attachment;', $disposition);
        self::assertStringNotContainsString('/', $disposition);
        self::assertStringContainsString("filename*=utf-8''", $disposition);
    }
}
