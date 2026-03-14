<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Validator;

use DateTime;
use Nowo\SepaPaymentBundle\Validator\SepaBusinessRulesValidator;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for SepaBusinessRulesValidator.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class SepaBusinessRulesValidatorTest extends TestCase
{
    private SepaBusinessRulesValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new SepaBusinessRulesValidator();
    }

    public function testIsValidTransactionAmount(): void
    {
        $this->assertTrue($this->validator->isValidTransactionAmount(100.50));
        $this->assertTrue($this->validator->isValidTransactionAmount(999999999.99));
        $this->assertFalse($this->validator->isValidTransactionAmount(0));
        $this->assertFalse($this->validator->isValidTransactionAmount(-100));
        $this->assertFalse($this->validator->isValidTransactionAmount(1000000000.00));
    }

    public function testIsValidTransactionCount(): void
    {
        $this->assertTrue($this->validator->isValidTransactionCount(1));
        $this->assertTrue($this->validator->isValidTransactionCount(99999));
        $this->assertFalse($this->validator->isValidTransactionCount(0));
        $this->assertFalse($this->validator->isValidTransactionCount(100000));
    }

    public function testIsValidExecutionDate(): void
    {
        $today     = new DateTime('today');
        $tomorrow  = new DateTime('tomorrow');
        $yesterday = new DateTime('yesterday');

        $this->assertTrue($this->validator->isValidExecutionDate($today));
        $this->assertTrue($this->validator->isValidExecutionDate($tomorrow));
        $this->assertFalse($this->validator->isValidExecutionDate($yesterday));
        $this->assertFalse($this->validator->isValidExecutionDate($yesterday, false));
    }

    public function testIsValidExecutionDateWithAllowTodayFalse(): void
    {
        $today    = new DateTime('today');
        $tomorrow = new DateTime('tomorrow');

        $this->assertFalse($this->validator->isValidExecutionDate($today, false));
        $this->assertTrue($this->validator->isValidExecutionDate($tomorrow, false));
    }

    public function testIsBusinessDay(): void
    {
        $monday   = new DateTime('2024-01-15'); // Monday
        $friday   = new DateTime('2024-01-19'); // Friday
        $saturday = new DateTime('2024-01-20'); // Saturday
        $sunday   = new DateTime('2024-01-21'); // Sunday

        $this->assertTrue($this->validator->isBusinessDay($monday));
        $this->assertTrue($this->validator->isBusinessDay($friday));
        $this->assertFalse($this->validator->isBusinessDay($saturday));
        $this->assertFalse($this->validator->isBusinessDay($sunday));
    }

    public function testIsValidSepaCurrency(): void
    {
        $this->assertTrue($this->validator->isValidSepaCurrency('EUR'));
        $this->assertTrue($this->validator->isValidSepaCurrency('eur'));
        $this->assertTrue($this->validator->isValidSepaCurrency('Eur'));
        $this->assertFalse($this->validator->isValidSepaCurrency('USD'));
        $this->assertFalse($this->validator->isValidSepaCurrency('GBP'));
    }

    public function testIsValidMandateExpirationDate(): void
    {
        $tomorrow  = new DateTime('tomorrow');
        $today     = new DateTime('today');
        $yesterday = new DateTime('yesterday');

        $this->assertTrue($this->validator->isValidMandateExpirationDate($tomorrow));
        $this->assertFalse($this->validator->isValidMandateExpirationDate($today));
        $this->assertFalse($this->validator->isValidMandateExpirationDate($yesterday));
    }

    public function testIsValidSequenceTypeTransition(): void
    {
        // First transaction
        $this->assertTrue($this->validator->isValidSequenceTypeTransition(null, 'FRST'));
        $this->assertTrue($this->validator->isValidSequenceTypeTransition(null, 'OOFF'));
        $this->assertFalse($this->validator->isValidSequenceTypeTransition(null, 'RCUR'));

        // FRST to RCUR
        $this->assertTrue($this->validator->isValidSequenceTypeTransition('FRST', 'RCUR'));
        $this->assertTrue($this->validator->isValidSequenceTypeTransition('FRST', 'FNAL'));
        $this->assertFalse($this->validator->isValidSequenceTypeTransition('FRST', 'FRST'));

        // RCUR to RCUR
        $this->assertTrue($this->validator->isValidSequenceTypeTransition('RCUR', 'RCUR'));
        $this->assertTrue($this->validator->isValidSequenceTypeTransition('RCUR', 'FNAL'));
        $this->assertFalse($this->validator->isValidSequenceTypeTransition('RCUR', 'FRST'));

        // OOFF to OOFF
        $this->assertTrue($this->validator->isValidSequenceTypeTransition('OOFF', 'OOFF'));
        $this->assertFalse($this->validator->isValidSequenceTypeTransition('OOFF', 'RCUR'));
    }

    public function testValidateCreditTransfer(): void
    {
        $errors = $this->validator->validateCreditTransfer(
            100.50,
            1,
            new DateTime('tomorrow'),
            'EUR',
        );

        $this->assertEmpty($errors);
    }

    public function testValidateCreditTransferWithErrors(): void
    {
        $errors = $this->validator->validateCreditTransfer(
            1000000000.00, // Too large
            100000, // Too many
            new DateTime('yesterday'), // Past date
            'USD', // Invalid currency
        );

        $this->assertCount(4, $errors);
    }

    public function testValidateDirectDebit(): void
    {
        $errors = $this->validator->validateDirectDebit(
            100.50,
            1,
            new DateTime('tomorrow'),
            'EUR',
            'FRST',
        );

        $this->assertEmpty($errors);
    }

    public function testValidateDirectDebitWithInvalidSequenceType(): void
    {
        $errors = $this->validator->validateDirectDebit(
            100.50,
            1,
            new DateTime('tomorrow'),
            'EUR',
            'INVALID',
        );

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid sequence type', $errors[0]);
    }

    public function testValidateDirectDebitWithExpiredMandate(): void
    {
        $errors = $this->validator->validateDirectDebit(
            100.50,
            1,
            new DateTime('tomorrow'),
            'EUR',
            'FRST',
            new DateTime('yesterday'),
        );

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Mandate expiration date', $errors[0]);
    }
}
