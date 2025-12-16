<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Command;

use Nowo\SepaPaymentBundle\Validator\CreditCardValidator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console command to validate credit card numbers.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsCommand(
    name: 'sepa:validate-credit-card',
    description: 'Validates a credit card number and displays its information'
)]
class ValidateCreditCardCommand extends Command
{
    /**
     * Constructor.
     *
     * @param CreditCardValidator $creditCardValidator Credit card validator instance
     */
    public function __construct(
        private CreditCardValidator $creditCardValidator
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
            ->addArgument('card-number', InputArgument::REQUIRED, 'The credit card number to validate')
            ->setHelp(
                <<<'HELP'
                    The <info>%command.name%</info> command validates a credit card number using the Luhn algorithm
                    and displays information about the card.

                    <info>php %command.full_name% 4532015112830366</info>
                    <info>php %command.full_name% "4532 0151 1283 0366"</info>
                    <info>php %command.full_name% 4532-0151-1283-0366</info>

                    The command accepts card numbers with or without spaces and dashes.
                    HELP
            );
    }

    /**
     * Executes the command.
     *
     * @param InputInterface  $input  The input interface
     * @param OutputInterface $output The output interface
     *
     * @return int The command exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $cardNumber = $input->getArgument('card-number');

        $normalized = $this->creditCardValidator->normalize($cardNumber);
        $isValid = $this->creditCardValidator->isValid($cardNumber);
        $cardType = $this->creditCardValidator->getCardType($cardNumber);
        $bin = $this->creditCardValidator->getBin($cardNumber);
        $lastFour = $this->creditCardValidator->getLastFour($cardNumber);
        $masked = $this->creditCardValidator->mask($cardNumber);

        $io->title('Credit Card Validation');

        $io->section('Card Information');
        $io->definitionList(
            ['Card Number (normalized)' => $normalized],
            ['Card Number (formatted)' => $this->creditCardValidator->format($cardNumber)],
            ['Card Number (masked)' => $masked],
            ['Valid' => $isValid ? '<fg=green>✓ Yes</>' : '<fg=red>✗ No</>'],
            ['Card Type' => $this->formatCardType($cardType)],
            ['BIN (Bank Identification Number)' => $bin],
            ['Last 4 Digits' => $lastFour],
            ['Length' => strlen($normalized) . ' digits']
        );

        if (!$isValid) {
            $io->warning('The credit card number is not valid according to the Luhn algorithm.');

            return Command::FAILURE;
        }

        $io->success('The credit card number is valid.');

        return Command::SUCCESS;
    }

    /**
     * Formats the card type for display.
     *
     * @param string $cardType The card type
     *
     * @return string The formatted card type
     */
    private function formatCardType(string $cardType): string
    {
        $types = [
            CreditCardValidator::TYPE_VISA => 'Visa',
            CreditCardValidator::TYPE_MASTERCARD => 'Mastercard',
            CreditCardValidator::TYPE_AMEX => 'American Express',
            CreditCardValidator::TYPE_DISCOVER => 'Discover',
            CreditCardValidator::TYPE_DINERS_CLUB => 'Diners Club',
            CreditCardValidator::TYPE_JCB => 'JCB',
            CreditCardValidator::TYPE_UNKNOWN => 'Unknown',
        ];

        return $types[$cardType] ?? 'Unknown';
    }
}
