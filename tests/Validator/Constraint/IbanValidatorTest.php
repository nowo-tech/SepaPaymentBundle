<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Validator\Constraint;

use Nowo\SepaPaymentBundle\Validator\Constraint\Iban;
use Nowo\SepaPaymentBundle\Validator\Constraint\IbanValidator as ConstraintIbanValidator;
use Nowo\SepaPaymentBundle\Validator\IbanValidator as IbanValidatorService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Test cases for Iban constraint validator.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class IbanValidatorTest extends TestCase
{
    /**
     * IBAN validator service mock.
     *
     * @var IbanValidatorService|\PHPUnit\Framework\MockObject\MockObject
     */
    private $ibanValidatorService;

    /**
     * Constraint validator instance.
     *
     * @var ConstraintIbanValidator
     */
    private ConstraintIbanValidator $validator;

    /**
     * Execution context mock.
     *
     * @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $context;

    /**
     * Sets up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->ibanValidatorService = $this->createMock(IbanValidatorService::class);
        $this->validator = new ConstraintIbanValidator($this->ibanValidatorService);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);
    }

    /**
     * Tests valid IBAN validation.
     *
     * @return void
     */
    public function testValidIban(): void
    {
        $constraint = new Iban();
        $iban = 'ES9121000418450200051332';

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
     *
     * @return void
     */
    public function testInvalidIban(): void
    {
        $constraint = new Iban();
        $iban = 'INVALID-IBAN';

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
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
     */
    public function testCustomMessage(): void
    {
        $customMessage = 'Custom IBAN error message';
        $constraint = new Iban(message: $customMessage);
        $iban = 'INVALID-IBAN';

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
     *
     * @return void
     */
    public function testWrongConstraintTypeThrows(): void
    {
        $wrongConstraint = $this->createMock(Constraint::class);
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('ES9121000418450200051332', $wrongConstraint);
    }

    /**
     * Tests that non-string value throws UnexpectedTypeException.
     *
     * @return void
     */
    public function testNonStringValueThrows(): void
    {
        $constraint = new Iban();
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(12345, $constraint);
    }
}
