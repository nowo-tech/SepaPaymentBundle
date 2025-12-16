<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Command;

use Nowo\SepaPaymentBundle\Command\ValidateCreditCardCommand;
use Nowo\SepaPaymentBundle\Validator\CreditCardValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Test cases for ValidateCreditCardCommand.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class ValidateCreditCardCommandTest extends TestCase
{
    /**
     * Command instance.
     *
     * @var ValidateCreditCardCommand
     */
    private ValidateCreditCardCommand $command;

    /**
     * Sets up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $creditCardValidator = new CreditCardValidator();
        $this->command = new ValidateCreditCardCommand($creditCardValidator);
    }

    /**
     * Tests command execution with valid credit card.
     *
     * @return void
     */
    public function testExecuteWithValidCreditCard(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['card-number' => '4532015112830366']);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Credit Card Validation', $output);
        $this->assertStringContainsString('The credit card number is valid', $output);
    }

    /**
     * Tests command execution with invalid credit card.
     *
     * @return void
     */
    public function testExecuteWithInvalidCreditCard(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['card-number' => '4532015112830367']);

        $this->assertEquals(1, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('The credit card number is not valid', $output);
    }

    /**
     * Tests command execution displays all card information.
     *
     * @return void
     */
    public function testExecuteDisplaysCardInformation(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['card-number' => '4532015112830366']);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Card Information', $output);
        $this->assertStringContainsString('Card Number (normalized)', $output);
        $this->assertStringContainsString('Card Number (formatted)', $output);
        $this->assertStringContainsString('Card Number (masked)', $output);
        $this->assertStringContainsString('Valid', $output);
        $this->assertStringContainsString('Card Type', $output);
        $this->assertStringContainsString('BIN', $output);
        $this->assertStringContainsString('Last 4 Digits', $output);
        $this->assertStringContainsString('Length', $output);
    }

    /**
     * Tests command with formatted card number (with spaces).
     *
     * @return void
     */
    public function testExecuteWithFormattedCardNumber(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['card-number' => '4532 0151 1283 0366']);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('The credit card number is valid', $output);
    }

    /**
     * Tests command with card number containing dashes.
     *
     * @return void
     */
    public function testExecuteWithCardNumberContainingDashes(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['card-number' => '4532-0151-1283-0366']);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('The credit card number is valid', $output);
    }

    /**
     * Tests command detects card type correctly.
     *
     * @return void
     */
    public function testExecuteDetectsCardType(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['card-number' => '4532015112830366']);

        $output = $commandTester->getDisplay();
        // Should detect Visa card
        $this->assertStringContainsString('Visa', $output);
    }

    /**
     * Tests command with Mastercard.
     *
     * @return void
     */
    public function testExecuteWithMastercard(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['card-number' => '5555555555554444']);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Mastercard', $output);
    }

    /**
     * Tests command with Amex.
     *
     * @return void
     */
    public function testExecuteWithAmex(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['card-number' => '378282246310005']);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('American Express', $output);
    }
}
