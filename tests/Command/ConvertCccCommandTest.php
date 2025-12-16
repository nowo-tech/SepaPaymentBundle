<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Command;

use Nowo\SepaPaymentBundle\Command\ConvertCccCommand;
use Nowo\SepaPaymentBundle\Converter\CccConverter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Test cases for ConvertCccCommand.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class ConvertCccCommandTest extends TestCase
{
    /**
     * Command instance.
     *
     * @var ConvertCccCommand
     */
    private ConvertCccCommand $command;

    /**
     * Sets up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $ibanValidator = new \Nowo\SepaPaymentBundle\Validator\IbanValidator();
        $cccConverter = new CccConverter($ibanValidator);
        $this->command = new ConvertCccCommand($cccConverter);
    }

    /**
     * Tests command execution with valid CCC.
     *
     * @return void
     */
    public function testExecuteWithValidCcc(): void
    {
        $application = new Application();
        $application->add($this->command);

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['ccc' => '21000418450200051332']);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('CCC converted to IBAN successfully', $output);
        $this->assertStringContainsString('21000418450200051332', $output);
        $this->assertStringContainsString('ES91', $output); // IBAN starts with ES91
    }

    /**
     * Tests command execution with invalid CCC.
     *
     * @return void
     */
    public function testExecuteWithInvalidCcc(): void
    {
        $application = new Application();
        $application->add($this->command);

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['ccc' => 'INVALID-CCC']);

        $this->assertEquals(1, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('error', strtolower($output));
    }

    /**
     * Tests command execution displays all CCC information.
     *
     * @return void
     */
    public function testExecuteDisplaysCccInformation(): void
    {
        $application = new Application();
        $application->add($this->command);

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['ccc' => '21000418450200051332']);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('CCC', $output);
        $this->assertStringContainsString('IBAN', $output);
        $this->assertStringContainsString('Bank Code', $output);
        $this->assertStringContainsString('Branch Code', $output);
        $this->assertStringContainsString('Account Number', $output);
    }

    /**
     * Tests command with CCC containing spaces.
     *
     * @return void
     */
    public function testExecuteWithCccContainingSpaces(): void
    {
        $application = new Application();
        $application->add($this->command);

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['ccc' => '2100 0418 4502 0005 1332']);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('CCC converted to IBAN successfully', $output);
    }

    /**
     * Tests command with CCC that has wrong length.
     *
     * @return void
     */
    public function testExecuteWithWrongLengthCcc(): void
    {
        $application = new Application();
        $application->add($this->command);

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['ccc' => '12345']);

        $this->assertEquals(1, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('error', strtolower($output));
    }
}

