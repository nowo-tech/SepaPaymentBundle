<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Validator\Constraint;

use Nowo\SepaPaymentBundle\Validator\Constraint\CreditCard;
use Nowo\SepaPaymentBundle\Validator\Constraint\CreditCardValidator as ConstraintCreditCardValidator;
use Nowo\SepaPaymentBundle\Validator\CreditCardValidator as CreditCardValidatorService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Test cases for Credit Card constraint and its validator.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class CreditCardValidatorTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $creditCardValidatorService;

    private ConstraintCreditCardValidator $validator;

    private \PHPUnit\Framework\MockObject\MockObject $context;

    protected function setUp(): void
    {
        $this->creditCardValidatorService = $this->createMock(CreditCardValidatorService::class);
        $this->validator                  = new ConstraintCreditCardValidator($this->creditCardValidatorService);
        $this->context                    = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);
    }

    /**
     * Tests CreditCard constraint instantiation with default message.
     */
    public function testCreditCardConstraintDefaultMessage(): void
    {
        $constraint = new CreditCard();
        $this->assertSame('credit_card.invalid', $constraint->message);
    }

    /**
     * Tests CreditCard constraint instantiation with custom message.
     */
    public function testCreditCardConstraintCustomMessage(): void
    {
        $constraint = new CreditCard(message: 'Custom credit card error');
        $this->assertSame('Custom credit card error', $constraint->message);
    }

    /**
     * Tests valid credit card validation.
     */
    public function testValidCreditCard(): void
    {
        $constraint = new CreditCard();
        $value      = '4111111111111111';

        $this->creditCardValidatorService
            ->expects($this->once())
            ->method('isValid')
            ->with($value)
            ->willReturn(true);

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($value, $constraint);
    }

    /**
     * Tests invalid credit card validation.
     */
    public function testInvalidCreditCard(): void
    {
        $constraint = new CreditCard();
        $value      = '1234567890123456';

        $this->creditCardValidatorService
            ->expects($this->once())
            ->method('isValid')
            ->with($value)
            ->willReturn(false);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('setParameter')
            ->with('{{ value }}', $this->anything())
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('setCode')
            ->with(CreditCard::INVALID_CREDIT_CARD_ERROR)
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
        $constraint = new CreditCard();

        $this->creditCardValidatorService
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
        $constraint = new CreditCard();

        $this->creditCardValidatorService
            ->expects($this->never())
            ->method('isValid');

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
        $customMessage = 'Custom credit card error message';
        $constraint    = new CreditCard(message: $customMessage);
        $value         = 'invalid';

        $this->creditCardValidatorService
            ->expects($this->once())
            ->method('isValid')
            ->with($value)
            ->willReturn(false);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('setParameter')
            ->with('{{ value }}', $this->anything())
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('setCode')
            ->with(CreditCard::INVALID_CREDIT_CARD_ERROR)
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
        $this->validator->validate('4111111111111111', $wrongConstraint);
    }

    /**
     * Tests that non-string value throws UnexpectedTypeException.
     */
    public function testNonStringValueThrows(): void
    {
        $constraint = new CreditCard();
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(4111111111111111, $constraint);
    }
}
