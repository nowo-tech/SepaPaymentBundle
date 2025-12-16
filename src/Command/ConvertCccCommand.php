<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Command;

use Nowo\SepaPaymentBundle\Converter\CccConverter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console command to convert CCC to IBAN.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsCommand(
    name: 'nowo:sepa:ccc-to-iban',
    description: 'Converts a Spanish CCC (Código Cuenta Cliente) to IBAN'
)]
class ConvertCccCommand extends Command
{
    /**
     * Constructor.
     *
     * @param CccConverter $cccConverter CCC converter instance
     */
    public function __construct(
        private CccConverter $cccConverter
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
            ->addArgument('ccc', InputArgument::REQUIRED, 'The CCC to convert (20 digits)')
            ->setHelp('This command converts a Spanish CCC (Código Cuenta Cliente) to IBAN format.');
    }

    /**
     * Executes the command.
     *
     * @param InputInterface  $input  Input interface
     * @param OutputInterface $output Output interface
     *
     * @return int Command exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $ccc = $input->getArgument('ccc');

        try {
            $iban = $this->cccConverter->cccToIban($ccc);

            $io->success('CCC converted to IBAN successfully');
            $io->table(
                ['Property', 'Value'],
                [
                    ['CCC', $ccc],
                    ['IBAN', $iban],
                    ['Bank Code', $this->cccConverter->getBankCode($ccc)],
                    ['Branch Code', $this->cccConverter->getBranchCode($ccc)],
                    ['Account Number', $this->cccConverter->getAccountNumber($ccc)],
                ]
            );

            return Command::SUCCESS;
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
