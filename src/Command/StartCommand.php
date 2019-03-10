<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\EnvironmentException;
use App\Manager\ApplicationLock;
use App\Manager\EnvironmentVariables;
use App\Traits\CustomCommandsTrait;
use App\Traits\SymfonyProcessTrait;
use App\Validator\Constraints\ConfigurationDefined;
use App\Validator\Constraints\DotEnvExists;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StartCommand extends Command
{
    use SymfonyProcessTrait;
    use CustomCommandsTrait;

    /**
     * StartCommand constructor.
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
        $this->setName('origami:start');
        $this->setAliases(['start']);

        $this->setDescription('Starts an environment previously installed in the current directory');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->project = getcwd();

        if (!$lock = $this->applicationLock->getCurrentLock()) {
            try {
                $this->checkEnvironmentConfiguration();

                $environmentVariables = $this->environmentVariables->getRequiredVariables($this->project);
                $this->startDockerSynchronization($environmentVariables);
                $this->startDockerServices($environmentVariables);

                $this->applicationLock->generateLock($this->project);
            } catch (\Exception $e) {
                $this->io->error($e->getMessage());
            }
        } else {
            $this->io->error(
                $lock === $this->project
                    ? 'The environment is already running.'
                    : 'Unable to start an environment when another is still running.'
            );
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

        $configurationConstraint = new ConfigurationDefined();
        $errors = $this->validator->validate($this->project, $configurationConstraint);
        if ($errors->has(0) === true) {
            throw new EnvironmentException($errors[0]->getMessage());
        }
    }

    /**
     * Starts the Docker synchronization needed to share the project source code.
     *
     * @param array $environmentVariables
     */
    private function startDockerSynchronization(array $environmentVariables): void
    {
        $process = new Process(
            ['docker-sync', 'start', "--config={$this->project}/var/docker/docker-sync.yml", '--dir="${HOME}/.docker-sync'],
            null,
            $environmentVariables,
            null,
            3600.00
        );
        $this->foreground($process);

        if ($process->isSuccessful()) {
            $this->io->success('Docker synchronization successfully started.');
        } else {
            $this->io->error('An error occurred while starting the Docker synchronization.');
        }
    }

    /**
     * Starts the Docker services after building the associated images.
     *
     * @param array $environmentVariables
     */
    private function startDockerServices(array $environmentVariables): void
    {
        $process = new Process(
            ['docker-compose', 'up', '--build', '--detach', '--remove-orphans'],
            null,
            $environmentVariables,
            null,
            3600.00
        );
        $this->foreground($process);

        if ($process->isSuccessful()) {
            $this->io->success('Docker services successfully started.');
        } else {
            $this->io->error('An error occurred while starting the Docker services.');
        }
    }
}
