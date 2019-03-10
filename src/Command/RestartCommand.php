<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\EnvironmentException;
use App\Manager\ApplicationLock;
use App\Manager\EnvironmentVariables;
use App\Traits\SymfonyProcessTrait;
use App\Validator\Constraints\DotEnvExists;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RestartCommand extends Command
{
    use SymfonyProcessTrait;

    /** @var ApplicationLock */
    private $applicationLock;

    /** @var EnvironmentVariables */
    private $environmentVariables;

    /** @var ValidatorInterface */
    private $validator;

    /** @var SymfonyStyle */
    private $io;

    /** @var string */
    private $project;

    /**
     * RestartCommand constructor.
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
        $this->setName('origami:restart');
        $this->setAliases(['restart']);

        $this->setDescription('Restarts an environment previously started');
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

                $environmentVariables = $this->environmentVariables->getRequiredVariables($this->project);
                $this->restartDockerServices($environmentVariables);
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
    }

    /**
     * Restarts the Docker services of the current environment.
     *
     * @param array $environmentVariables
     */
    private function restartDockerServices(array $environmentVariables): void
    {
        $process = new Process(
            ['docker-compose', 'restart'],
            null,
            $environmentVariables,
            null,
            3600.00
        );
        $this->foreground($process);

        if ($process->isSuccessful()) {
            $this->io->success('Docker services successfully restarted.');
        } else {
            $this->io->error('An error occurred while restarting the Docker services.');
        }
    }
}
