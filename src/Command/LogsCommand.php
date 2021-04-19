<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\OrigamiExceptionInterface;
use App\Helper\CurrentContext;
use App\Middleware\Binary\Docker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LogsCommand extends AbstractBaseCommand
{
    /** {@inheritdoc} */
    protected static $defaultName = 'origami:logs';

    private CurrentContext $currentContext;
    private Docker $docker;

    public function __construct(CurrentContext $currentContext, Docker $docker, ?string $name = null)
    {
        parent::__construct($name);

        $this->currentContext = $currentContext;
        $this->docker = $docker;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Shows the logs of an environment previously started');

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
        $io = new SymfonyStyle($input, $output);

        try {
            $environment = $this->currentContext->getEnvironment($input);
            $this->currentContext->setActiveEnvironment($environment);

            if ($output->isVerbose()) {
                $this->printEnvironmentDetails($environment, $io);
            }

            $tail = $input->getOption('tail');
            $service = $input->getArgument('service');

            $this->docker->showServicesLogs((int) $tail, $service);
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());
            $exitCode = Command::FAILURE;
        }

        return $exitCode ?? Command::SUCCESS;
    }
}
