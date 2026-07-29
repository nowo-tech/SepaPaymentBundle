<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Validator\Constraint;

use Nowo\SepaPaymentBundle\Validator\Constraint\SepaCountry;
use Nowo\SepaPaymentBundle\Validator\Constraint\SepaCountryValidator as ConstraintSepaCountryValidator;
use Nowo\SepaPaymentBundle\Validator\SepaCountryValidator as SepaCountryValidatorService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Test cases for SEPA Country constraint and its validator.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class SepaCountryValidatorTest extends TestCase
{
    private MockObject $sepaCountryValidatorService;

    private ConstraintSepaCountryValidator $validator;

    private MockObject $context;

    protected function setUp(): void
    {
        $this->sepaCountryValidatorService = $this->createMock(SepaCountryValidatorService::class);
        $this->validator                   = new ConstraintSepaCountryValidator($this->sepaCountryValidatorService);
        $this->context                     = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);
    }

    /**
     * Tests SepaCountry constraint instantiation with default message.
     */
    public function testSepaCountryConstraintDefaultMessage(): void
    {
        $constraint = new SepaCountry();
        $this->assertSame('sepa_country.invalid', $constraint->message);
    }

    /**
     * Tests SepaCountry constraint instantiation with custom message.
     */
    public function testSepaCountryConstraintCustomMessage(): void
    {
        $constraint = new SepaCountry(message: 'Custom SEPA country error');
        $this->assertSame('Custom SEPA country error', $constraint->message);
    }

    /**
     * Tests valid SEPA country validation.
     */
    public function testValidSepaCountry(): void
    {
        $constraint = new SepaCountry();
        $value      = 'ES';

        $this->sepaCountryValidatorService
            ->expects($this->once())
            ->method('isSepaCountry')
            ->with($value)
            ->willReturn(true);

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($value, $constraint);
    }

    /**
     * Tests invalid SEPA country validation.
     */
    public function testInvalidSepaCountry(): void
    {
        $constraint = new SepaCountry();
        $value      = 'XX';

        $this->sepaCountryValidatorService
            ->expects($this->once())
            ->method('isSepaCountry')
            ->with($value)
            ->willReturn(false);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('setParameter')
            ->with('{{ value }}', $this->anything())
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('setCode')
            ->with(SepaCountry::INVALID_SEPA_COUNTRY_ERROR)
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($violationBuilder);

        $this->validator->validate($value, $constraint);
    }

    /**
     * Tests null value is skipped.
     */
    public function testNullValueIsSkipped(): void
    {
        $constraint = new SepaCountry();

        $this->sepaCountryValidatorService
            ->expects($this->never())
            ->method('isSepaCountry');

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate(null, $constraint);
    }

    /**
     * Tests empty string value is skipped.
     */
    public function testEmptyStringValueIsSkipped(): void
    {
        $constraint = new SepaCountry();

        $this->sepaCountryValidatorService
            ->expects($this->never())
            ->method('isSepaCountry');

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('', $constraint);
    }

    /**
     * Tests custom message is used in violation.
     */
    public function testCustomMessage(): void
    {
        $customMessage = 'Custom SEPA country error message';
        $constraint    = new SepaCountry(message: $customMessage);
        $value         = 'XX';

        $this->sepaCountryValidatorService
            ->expects($this->once())
            ->method('isSepaCountry')
            ->with($value)
            ->willReturn(false);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('setParameter')
            ->with('{{ value }}', $this->anything())
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('setCode')
            ->with(SepaCountry::INVALID_SEPA_COUNTRY_ERROR)
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($customMessage)
            ->willReturn($violationBuilder);

        $this->validator->validate($value, $constraint);
    }

    /**
     * Tests that wrong constraint type throws UnexpectedTypeException.
     */
    public function testWrongConstraintTypeThrows(): void
    {
        $wrongConstraint = $this->createMock(Constraint::class);
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('ES', $wrongConstraint);
    }

    /**
     * Tests that non-string value throws UnexpectedTypeException.
     */
    public function testNonStringValueThrows(): void
    {
        $constraint = new SepaCountry();
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(123, $constraint);
    }
}
