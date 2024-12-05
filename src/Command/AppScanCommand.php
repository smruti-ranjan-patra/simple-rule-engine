<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Service\DebrickedService;
use App\Service\RuleEngine;

#[AsCommand(
    name: 'AppScanCommand',
    description: 'Command for notifying the users on the status of the scan',
)]
class AppScanCommand extends Command
{
    private $debrickedService;
    private $ruleEngine;

    public function __construct(DebrickedService $debrickedService, RuleEngine $ruleEngine)
    {
        parent::__construct();
        $this->debrickedService = $debrickedService;
        $this->ruleEngine = $ruleEngine;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');

        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
        }

        if ($input->getOption('option1')) {
            // ...
        }

        // Simulate getting scan results from Debricked API
        $scanResult = $this->debrickedService->getScanResult($arg1);

        // Generate the data to sent for rule checking
        $systemStat = []; // use the scanResult data with DB queries;

        // Evaluate rules based on scan results
        $this->ruleEngine->evaluateRules($systemStat);

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
