<?php

declare(strict_types=1);

namespace App\Command;

use App\Helper\CommandExitCode;
use App\Manager\ApplicationLock;
use App\Manager\EnvironmentVariables;
use App\Traits\CustomCommandsTrait;
use App\Traits\SymfonyProcessTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LogsCommand extends Command
{
    use SymfonyProcessTrait;
    use CustomCommandsTrait;

    /**
     * LogsCommand constructor.
     *
     * @param string|null          $name
     * @param ApplicationLock      $applicationLock
     * @param EnvironmentVariables $environmentVariables
     */
    public function __construct(?string $name = null, ApplicationLock $applicationLock, EnvironmentVariables $environmentVariables, ValidatorInterface $validator)
    {
        parent::__construct($name);

        $this->applicationLock = $applicationLock;
        $this->environmentVariables = $environmentVariables;
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('origami:logs');
        $this->setAliases(['logs']);

        $this->addArgument(
            'service',
            InputArgument::OPTIONAL,
            'Name of the service for which the logs will be shown'
        );

        $this->addOption(
            'tail',
            't',
            InputOption::VALUE_OPTIONAL,
            'Number of lines to show from the end of the logs for each service',
            10
        );

        $this->setDescription('Shows the logs of an environment previously started');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);

        if ($this->project = $this->applicationLock->getCurrentLock()) {
            try {
                $this->checkEnvironmentConfiguration();

                if ($output->isVerbose()) {
                    $this->printEnvironmentDetails();
                }

                $environmentVariables = $this->environmentVariables->getRequiredVariables($this->project);
                $this->showServicesLogs($input, $environmentVariables);
            } catch (\Exception $e) {
                $this->io->error($e->getMessage());
                $exitCode = CommandExitCode::EXCEPTION;
            }
        } else {
            $this->io->error('There is no running environment.');
            $exitCode = CommandExitCode::INVALID;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }

    /**
     * Shows the logs of the Docker services associated to the current environment.
     *
     * @param InputInterface $input
     * @param array          $environmentVariables
     */
    private function showServicesLogs(InputInterface $input, array $environmentVariables): void
    {
        $tail = $input->getOption('tail');
        $command = ['docker-compose', 'logs', '--follow', '--tail='.(\is_string($tail) ? $tail : '0')];
        if ($service = $input->getArgument('service')) {
            $command[] = $service;
        }

        $process = new Process($command, null, $environmentVariables, null, 3600.00);
        $this->foreground($process);
    }
}
