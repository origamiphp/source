<?php

declare(strict_types=1);

namespace App\Command\Contextual;

use App\Command\AbstractBaseCommand;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LogsCommand extends AbstractBaseCommand
{
    protected static $defaultName = 'origami:logs';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setAliases(['logs']);
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
        try {
            $this->getEnvironment($input);

            if ($output->isVerbose()) {
                $this->printEnvironmentDetails();
            }

            /** @var null|string $tail */
            $tail = $input->getOption('tail');
            /** @var null|string $service */
            $service = $input->getArgument('service');

            $this->dockerCompose->showServicesLogs((int) $tail, $service);
        } catch (OrigamiExceptionInterface $exception) {
            $this->io->error($exception->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
