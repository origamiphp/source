<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\EnvironmentException;
use App\Manager\ApplicationLock;
use App\Manager\EnvironmentVariables;
use App\Traits\CustomCommandsTrait;
use App\Traits\SymfonyProcessTrait;
use App\Validator\Constraints\DotEnvExists;
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
     * @param string|null $name
     * @param ApplicationLock $applicationLock
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
            ''
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
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);

        if ($this->project = $this->applicationLock->getCurrentLock()) {
            try {
                $this->checkEnvironmentConfiguration();

                $this->io->success('An environment is currently running.');
                $this->io->listing(
                    [
                        "Project location: {$this->project}",
                        'Environment type:' . getenv('DOCKER_ENVIRONMENT')
                    ]
                );

                $environmentVariables = $this->environmentVariables->getRequiredVariables($this->project);
                $this->showServicesLogs($input, $environmentVariables);
            } catch (\Exception $e) {
                $this->io->error($e->getMessage());
            }
        } else {
            $this->io->error('There is no running environment.');
        }
    }

    /**
     * Checks whether the environment has been installed and correctly configured.
     *
     * @throws EnvironmentException
     */
    private function checkEnvironmentConfiguration(): void
    {
        $dotEnvConstraint = new DotEnvExists();
        $errors = $this->validator->validate($this->project, $dotEnvConstraint);
        if ($errors->has(0) !== true) {
            $this->environmentVariables->loadFromDotEnv("{$this->project}/var/docker/.env");
        } else {
            throw new EnvironmentException($errors[0]->getMessage());
        }

        if (!$environment = getenv('DOCKER_ENVIRONMENT')) {
            throw new EnvironmentException(
                'The environment is not properly configured, consider consider executing the "install" command.'
            );
        }
    }

    /**
     * Shows the logs of the Docker services associated to the current environment.
     *
     * @param InputInterface $input
     * @param array $environmentVariables
     */
    private function showServicesLogs(InputInterface $input, array $environmentVariables): void
    {
        $command = ['docker-compose', 'logs', '--follow', "--tail={$input->getOption('tail')}"];
        if ($service = $input->getArgument('service')) {
            $command[] = $service;
        }

        $process = new Process($command, null, $environmentVariables, null, 3600.00);
        $this->foreground($process);
    }
}
