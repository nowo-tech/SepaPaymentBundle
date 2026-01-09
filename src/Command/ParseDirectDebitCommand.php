<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Command;

use Nowo\SepaPaymentBundle\Parser\DirectDebitParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console command to parse SEPA Direct Debit XML files.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsCommand(
    name: 'nowo:sepa:parse-direct-debit',
    description: 'Parses a SEPA Direct Debit XML file and displays the extracted information'
)]
class ParseDirectDebitCommand extends Command
{
    /**
     * Constructor.
     *
     * @param DirectDebitParser $parser Direct Debit parser instance
     */
    public function __construct(
        private DirectDebitParser $parser
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
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the SEPA Direct Debit XML file')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output as JSON')
            ->setHelp('This command parses a SEPA Direct Debit XML file and displays the extracted information.');
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
        $file = $input->getArgument('file');
        $jsonOutput = $input->getOption('json');

        if (!file_exists($file)) {
            $io->error(sprintf('File not found: %s', $file));

            return Command::FAILURE;
        }

        $xml = file_get_contents($file);
        if (false === $xml) {
            $io->error(sprintf('Could not read file: %s', $file));

            return Command::FAILURE;
        }

        // Validate XML
        if (!$this->parser->isValidDirectDebit($xml)) {
            $io->error('Invalid SEPA Direct Debit XML format');

            return Command::FAILURE;
        }

        try {
            $data = $this->parser->parseDirectDebit($xml);
        } catch (\Exception $e) {
            $io->error(sprintf('Error parsing XML: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        if ($jsonOutput) {
            $output->writeln(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return Command::SUCCESS;
        }

        $io->title('SEPA Direct Debit XML Parser');

        // Display group header
        $io->section('Group Header');
        $io->table(
            ['Property', 'Value'],
            [
                ['Message ID', $data['messageId'] ?? 'N/A'],
                ['Creation Date', $data['creationDate'] ?? 'N/A'],
                ['Initiating Party', $data['initiatingPartyName'] ?? 'N/A'],
            ]
        );

        // Display payment information
        $io->section('Payment Information');
        $io->table(
            ['Property', 'Value'],
            [
                ['Payment Info ID', $data['paymentInfoId'] ?? 'N/A'],
                ['Sequence Type', $data['sequenceType'] ?? 'N/A'],
                ['Due Date', $data['dueDate'] ?? 'N/A'],
                ['Local Instrument Code', $data['localInstrumentCode'] ?? 'N/A'],
                ['Creditor Name', $data['creditorName'] ?? 'N/A'],
                ['Creditor IBAN', $data['creditorIban'] ?? 'N/A'],
                ['Creditor BIC', $data['creditorBic'] ?? 'N/A'],
                ['Creditor ID', $data['creditorId'] ?? 'N/A'],
            ]
        );

        // Display transactions
        $io->section('Transactions');
        $transactions = $data['transactions'] ?? [];
        if (empty($transactions)) {
            $io->warning('No transactions found');
        } else {
            $rows = [];
            foreach ($transactions as $index => $transaction) {
                $amount = $transaction['amount'] ?? 'N/A';
                if (is_numeric($amount)) {
                    $amount = number_format((float) $amount, 2, '.', '');
                }

                $rows[] = [
                    'Transaction ' . ($index + 1),
                    $transaction['endToEndId'] ?? 'N/A',
                    $amount,
                    $transaction['currency'] ?? 'N/A',
                    $transaction['debtorName'] ?? 'N/A',
                    $transaction['debtorIban'] ?? 'N/A',
                    $transaction['debtorBic'] ?? 'N/A',
                    $transaction['debtorMandate'] ?? 'N/A',
                    $transaction['debtorMandateSignDate'] ?? 'N/A',
                ];
            }

            $io->table(
                ['#', 'End-to-End ID', 'Amount', 'Currency', 'Debtor Name', 'Debtor IBAN', 'Debtor BIC', 'Mandate ID', 'Mandate Sign Date'],
                $rows
            );
        }

        $io->success(sprintf('Successfully parsed %d transaction(s)', count($transactions)));

        return Command::SUCCESS;
    }
}
