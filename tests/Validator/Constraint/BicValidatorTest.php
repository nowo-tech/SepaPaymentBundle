<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Validator\Constraint;

use Nowo\SepaPaymentBundle\Validator\BicValidator as BicValidatorService;
use Nowo\SepaPaymentBundle\Validator\Constraint\Bic;
use Nowo\SepaPaymentBundle\Validator\Constraint\BicValidator as ConstraintBicValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Test cases for Bic constraint validator.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class BicValidatorTest extends TestCase
{
    /**
     * BIC validator service mock.
     *
     * @var BicValidatorService|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $bicValidatorService;

    /**
     * Constraint validator instance.
     */
    private ConstraintBicValidator $validator;

    /**
     * Execution context mock.
     *
     * @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $context;

    /**
     * Sets up the test environment.
     */
    protected function setUp(): void
    {
        $this->bicValidatorService = $this->createMock(BicValidatorService::class);
        $this->validator           = new ConstraintBicValidator($this->bicValidatorService);
        $this->context             = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);
    }

    /**
     * Tests valid BIC validation.
     */
    public function testValidBic(): void
    {
        $constraint = new Bic();
        $bic        = 'ESPBESMM';

        $this->bicValidatorService
            ->expects($this->once())
            ->method('isValid')
            ->with($bic)
            ->willReturn(true);

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($bic, $constraint);
    }

    /**
     * Tests invalid BIC validation.
     */
    public function testInvalidBic(): void
    {
        $constraint = new Bic();
        $bic        = 'INVALID-BIC';

        $this->bicValidatorService
            ->expects($this->once())
            ->method('isValid')
            ->with($bic)
            ->willReturn(false);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('setParameter')
            ->with('{{ value }}', $this->anything())
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('setCode')
            ->with(Bic::INVALID_BIC_ERROR)
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($violationBuilder);

        $this->validator->validate($bic, $constraint);
    }

    /**
     * Tests null value is skipped.
     */
    public function testNullValueIsSkipped(): void
    {
        $constraint = new Bic();

        $this->bicValidatorService
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
        $constraint = new Bic();

        $this->bicValidatorService
            ->expects($this->never())
            ->method('isValid');

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('', $constraint);
    }

    /**
     * Tests that wrong constraint type throws UnexpectedTypeException.
     */
    public function testWrongConstraintTypeThrows(): void
    {
        $wrongConstraint = $this->createMock(Constraint::class);
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('ESPBESMM', $wrongConstraint);
    }

    /**
     * Tests that non-string value throws UnexpectedTypeException.
     */
    public function testNonStringValueThrows(): void
    {
        $constraint = new Bic();
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(12345, $constraint);
    }
}
