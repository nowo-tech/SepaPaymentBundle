<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Validator\Constraint;

use Nowo\SepaPaymentBundle\Validator\Constraint\Iban;
use Nowo\SepaPaymentBundle\Validator\Constraint\IbanValidator as ConstraintIbanValidator;
use Nowo\SepaPaymentBundle\Validator\IbanValidator as IbanValidatorService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Test cases for Iban constraint validator.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class IbanValidatorTest extends TestCase
{
    /**
     * IBAN validator service mock.
     *
     * @var IbanValidatorService|MockObject
     */
    private MockObject $ibanValidatorService;

    /**
     * Constraint validator instance.
     */
    private ConstraintIbanValidator $validator;

    /**
     * Execution context mock.
     *
     * @var ExecutionContextInterface|MockObject
     */
    private MockObject $context;

    /**
     * Sets up the test environment.
     */
    protected function setUp(): void
    {
        $this->ibanValidatorService = $this->createMock(IbanValidatorService::class);
        $this->validator            = new ConstraintIbanValidator($this->ibanValidatorService);
        $this->context              = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);
    }

    /**
     * Tests valid IBAN validation.
     */
    public function testValidIban(): void
    {
        $constraint = new Iban();
        $iban       = 'ES9121000418450200051332';

        $this->ibanValidatorService
            ->expects($this->once())
            ->method('isValid')
            ->with($iban)
            ->willReturn(true);

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($iban, $constraint);
    }

    /**
     * Tests invalid IBAN validation.
     */
    public function testInvalidIban(): void
    {
        $constraint = new Iban();
        $iban       = 'INVALID-IBAN';

        $this->ibanValidatorService
            ->expects($this->once())
            ->method('isValid')
            ->with($iban)
            ->willReturn(false);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('setParameter')
            ->with('{{ value }}', $this->anything())
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('setCode')
            ->with(Iban::INVALID_IBAN_ERROR)
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($violationBuilder);

        $this->validator->validate($iban, $constraint);
    }

    /**
     * Tests null value is skipped.
     */
    public function testNullValueIsSkipped(): void
    {
        $constraint = new Iban();

        $this->ibanValidatorService
            ->expects($this->never())
            ->method('isValid');

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
        $constraint = new Iban();

        $this->ibanValidatorService
            ->expects($this->never())
            ->method('isValid');

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('', $constraint);
    }

    /**
     * Tests custom message.
     */
    public function testCustomMessage(): void
    {
        $customMessage = 'Custom IBAN error message';
        $constraint    = new Iban(message: $customMessage);
        $iban          = 'INVALID-IBAN';

        $this->ibanValidatorService
            ->expects($this->once())
            ->method('isValid')
            ->with($iban)
            ->willReturn(false);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('setParameter')
            ->with('{{ value }}', $this->anything())
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('setCode')
            ->with(Iban::INVALID_IBAN_ERROR)
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($customMessage)
            ->willReturn($violationBuilder);

        $this->validator->validate($iban, $constraint);
    }

    /**
     * Tests that wrong constraint type throws UnexpectedTypeException.
     */
    public function testWrongConstraintTypeThrows(): void
    {
        $wrongConstraint = $this->createMock(Constraint::class);
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('ES9121000418450200051332', $wrongConstraint);
    }

    /**
     * Tests that non-string value throws UnexpectedTypeException.
     */
    public function testNonStringValueThrows(): void
    {
        $constraint = new Iban();
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(12345, $constraint);
    }
}
