<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\OrigamiExceptionInterface;
use App\Service\ApplicationContext;
use App\Service\Middleware\Binary\Docker;
use App\Service\Wrapper\OrigamiStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'origami:logs',
    description: 'Shows the logs generated in real-time by the running environment'
)]
class LogsCommand extends AbstractBaseCommand
{
    public function __construct(
        private ApplicationContext $applicationContext,
        private Docker $docker,
        string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->addArgument(
            'service',
            InputArgument::OPTIONAL,
            'Name of the service for which the logs will be shown'
        );

        $this->addOption(
            'tail',
            't',
            InputOption::VALUE_OPTIONAL,
            'Number of lines to show from the end of the logs for each service'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new OrigamiStyle($input, $output);

        try {
            $this->applicationContext->loadEnvironment($input);
            $environment = $this->applicationContext->getActiveEnvironment();

            if ($output->isVerbose()) {
                $this->printEnvironmentDetails($environment, $io);
            }

            $tail = $input->getOption('tail');
            $service = $input->getArgument('service');

            $this->docker->showServicesLogs((int) $tail, $service);
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
