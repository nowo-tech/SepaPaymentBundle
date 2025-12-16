<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Command;

use Nowo\SepaPaymentBundle\Command\ValidateIbanCommand;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Test cases for ValidateIbanCommand.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class ValidateIbanCommandTest extends TestCase
{
    /**
     * Command instance.
     *
     * @var ValidateIbanCommand
     */
    private ValidateIbanCommand $command;

    /**
     * Sets up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $ibanValidator = new IbanValidator();
        $this->command = new ValidateIbanCommand($ibanValidator);
    }

    /**
     * Tests command execution with valid IBAN.
     *
     * @return void
     */
    public function testExecuteWithValidIban(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['iban' => 'ES9121000418450200051332']);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('IBAN is valid', $output);
        $this->assertStringContainsString('ES9121000418450200051332', $output);
        $this->assertStringContainsString('ES', $output); // Country code
    }

    /**
     * Tests command execution with invalid IBAN.
     *
     * @return void
     */
    public function testExecuteWithInvalidIban(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['iban' => 'INVALID-IBAN']);

        $this->assertEquals(1, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('IBAN is invalid', $output);
    }

    /**
     * Tests command execution displays all IBAN information.
     *
     * @return void
     */
    public function testExecuteDisplaysIbanInformation(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['iban' => 'ES9121000418450200051332']);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Normalized', $output);
        $this->assertStringContainsString('Formatted', $output);
        $this->assertStringContainsString('Country Code', $output);
        $this->assertStringContainsString('Check Digits', $output);
        $this->assertStringContainsString('BBAN', $output);
        $this->assertStringContainsString('Valid', $output);
    }

    /**
     * Tests command with formatted IBAN (with spaces).
     *
     * @return void
     */
    public function testExecuteWithFormattedIban(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['iban' => 'ES91 2100 0418 4502 0005 1332']);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('IBAN is valid', $output);
    }

    /**
     * Tests command with lowercase IBAN.
     *
     * @return void
     */
    public function testExecuteWithLowercaseIban(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['iban' => 'es9121000418450200051332']);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('IBAN is valid', $output);
    }
}
