<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Validator\Constraint;

use Nowo\SepaPaymentBundle\Validator\Constraint\SepaCreditorIdentifier;
use Nowo\SepaPaymentBundle\Validator\Constraint\SepaCreditorIdentifierValidator as ConstraintSepaCreditorIdentifierValidator;
use Nowo\SepaPaymentBundle\Validator\SepaCreditorIdentifierValidator as SepaCreditorIdentifierValidatorService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Test cases for SepaCreditorIdentifier constraint validator.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class SepaCreditorIdentifierValidatorTest extends TestCase
{
    /**
     * SEPA Creditor Identifier validator service mock.
     *
     * @var SepaCreditorIdentifierValidatorService|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sepaCreditorIdentifierValidatorService;

    /**
     * Constraint validator instance.
     *
     * @var ConstraintSepaCreditorIdentifierValidator
     */
    private ConstraintSepaCreditorIdentifierValidator $validator;

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
        $this->sepaCreditorIdentifierValidatorService = $this->createMock(SepaCreditorIdentifierValidatorService::class);
        $this->validator = new ConstraintSepaCreditorIdentifierValidator($this->sepaCreditorIdentifierValidatorService);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);
    }

    /**
     * Tests valid SEPA Creditor Identifier validation.
     *
     * @return void
     */
    public function testValidSepaCreditorIdentifier(): void
    {
        $constraint = new SepaCreditorIdentifier();
        $identifier = 'ES97ZZZM12345678';

        $this->sepaCreditorIdentifierValidatorService
            ->expects($this->once())
            ->method('isValid')
            ->with($identifier)
            ->willReturn(true);

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($identifier, $constraint);
    }

    /**
     * Tests invalid SEPA Creditor Identifier validation.
     *
     * @return void
     */
    public function testInvalidSepaCreditorIdentifier(): void
    {
        $constraint = new SepaCreditorIdentifier();
        $identifier = 'INVALID-IDENTIFIER';

        $this->sepaCreditorIdentifierValidatorService
            ->expects($this->once())
            ->method('isValid')
            ->with($identifier)
            ->willReturn(false);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('setParameter')
            ->with('{{ value }}', $this->anything())
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('setCode')
            ->with(SepaCreditorIdentifier::INVALID_SEPA_CREDITOR_IDENTIFIER_ERROR)
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($violationBuilder);

        $this->validator->validate($identifier, $constraint);
    }

    /**
     * Tests null value is skipped.
     *
     * @return void
     */
    public function testNullValueIsSkipped(): void
    {
        $constraint = new SepaCreditorIdentifier();

        $this->sepaCreditorIdentifierValidatorService
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
        $constraint = new SepaCreditorIdentifier();

        $this->sepaCreditorIdentifierValidatorService
            ->expects($this->never())
            ->method('isValid');

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('', $constraint);
    }
}
