<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Command;

use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console command to validate IBAN.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsCommand(
    name: 'nowo:sepa:validate-iban',
    description: 'Validates an IBAN and shows detailed information'
)]
class ValidateIbanCommand extends Command
{
    /**
     * Constructor.
     *
     * @param IbanValidator $ibanValidator IBAN validator instance
     */
    public function __construct(
        private IbanValidator $ibanValidator
    ) {
        parent::__construct();
    }

    /**
     * Configures the command.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addArgument('iban', InputArgument::REQUIRED, 'The IBAN to validate')
            ->setHelp('This command validates an IBAN and displays detailed information about it.');
    }

    /**
     * Executes the command.
     *
     * @param InputInterface  $input  Input interface
     * @param OutputInterface $output Output interface
     * @return int Command exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $iban = $input->getArgument('iban');

        $io->title('IBAN Validation');

        $isValid = $this->ibanValidator->isValid($iban);

        if ($isValid) {
            $io->success('IBAN is valid');
        } else {
            $io->error('IBAN is invalid');
        }

        $io->table(
            ['Property', 'Value'],
            [
                ['IBAN', $iban],
                ['Normalized', $this->ibanValidator->normalize($iban)],
                ['Formatted', $this->ibanValidator->format($iban)],
                ['Country Code', $this->ibanValidator->getCountryCode($iban)],
                ['Check Digits', $this->ibanValidator->getCheckDigits($iban)],
                ['BBAN', $this->ibanValidator->getBban($iban)],
                ['Valid', $isValid ? 'Yes' : 'No'],
            ]
        );

        return $isValid ? Command::SUCCESS : Command::FAILURE;
    }
}

