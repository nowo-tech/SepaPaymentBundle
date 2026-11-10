<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Model;

use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Model\PartyIdentification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class PartyIdentificationTest extends TestCase
{
    public function testOrganisationAndPrivate(): void
    {
        $org = PartyIdentification::organisation('ES59000B12345678', 'SEPA', null, 'AEAT');
        self::assertTrue($org->isOrganisation());
        self::assertSame('ES59000B12345678', $org->getId());
        self::assertSame('SEPA', $org->getSchemeProprietary());
        self::assertNull($org->getSchemeCode());
        self::assertSame('AEAT', $org->getIssuer());

        $private = PartyIdentification::privatePerson('12345678Z', null, 'NIDN');
        self::assertFalse($private->isOrganisation());
        self::assertSame('NIDN', $private->getSchemeCode());
        self::assertNull($private->getIssuer());
    }

    /**
     * @return iterable<string, array{callable(): PartyIdentification, string}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'empty id' => [static fn (): PartyIdentification => PartyIdentification::organisation(''), 'Othr/Id'];
        yield 'long id' => [static fn (): PartyIdentification => PartyIdentification::organisation(str_repeat('A', 36)), 'Othr/Id'];
        yield 'both schemes' => [static fn (): PartyIdentification => PartyIdentification::organisation('A', 'SEPA', 'TXID'), 'not both'];
        yield 'bad code' => [static fn (): PartyIdentification => PartyIdentification::organisation('A', null, 'toolong'), 'scheme code'];
        yield 'long proprietary' => [static fn (): PartyIdentification => PartyIdentification::organisation('A', str_repeat('P', 36)), 'SchmeNm/Prtry'];
        yield 'invalid UTF-8' => [static fn (): PartyIdentification => PartyIdentification::organisation("B\xFF1"), 'not valid UTF-8'];
        yield 'empty issuer' => [static fn (): PartyIdentification => PartyIdentification::privatePerson('A', null, null, ''), 'Issr'];
    }

    #[DataProvider('invalidProvider')]
    public function testInvalidValuesAreRejected(callable $factory, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $factory();
    }
}
