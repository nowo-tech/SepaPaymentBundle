<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Formatter;

use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Formatter\IbanMasker;
use PHPUnit\Framework\TestCase;

/**
 * P8: IbanMasker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class IbanMaskerTest extends TestCase
{
    public function testMasksKeepingCountryCheckDigitsAndEnd(): void
    {
        $masker = new IbanMasker();

        self::assertSame('ES91****************1332', $masker->mask('ES9121000418450200051332'));
        self::assertSame('ES91****************1332', $masker->mask('es91 2100 0418 4502 0005 1332'));
        self::assertSame('ES91 **** **** **** **** 1332', $masker->maskFormatted('ES9121000418450200051332'));
        self::assertSame('NL91************00', $masker->mask('NL91ABNA0417164300', 2));
        self::assertSame('BE68************', $masker->mask('BE68539007547034', 0));
        self::assertSame('BE68 **** **** ****', $masker->maskFormatted('BE68539007547034', 0));
    }

    public function testShortValuesAreFullyMasked(): void
    {
        $masker = new IbanMasker();

        self::assertSame('', $masker->mask(''));
        self::assertSame('********', $masker->mask('ES912100'));
        self::assertSame('ES91*1234', $masker->mask('ES9101234'));
    }

    public function testVisibleEndIsBounded(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new IbanMasker())->mask('ES9121000418450200051332', 5);
    }
}
