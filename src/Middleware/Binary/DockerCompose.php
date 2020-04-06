<?php

declare(strict_types=1);

namespace App\Middleware\Binary;

use App\Environment\EnvironmentEntity;
use App\Exception\InvalidEnvironmentException;
use App\Helper\ProcessFactory;
use App\Validator\Constraints\ConfigurationFiles;
use App\Validator\Constraints\DotEnvExists;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DockerCompose
{
    /** @var ValidatorInterface */
    private $validator;

    /** @var EnvironmentEntity */
    private $environment;

    /** @var ProcessFactory */
    private $processFactory;

    /** @var array */
    private $environmentVariables = [];

    public function __construct(ValidatorInterface $validator, ProcessFactory $processFactory)
    {
        $this->validator = $validator;
        $this->processFactory = $processFactory;
    }

    /**
     * Defines the currently active environment.
     *
     * @throws InvalidEnvironmentException
     */
    public function setActiveEnvironment(EnvironmentEntity $environment): void
    {
        $this->environment = $environment;

        if ($this->environment->getType() !== EnvironmentEntity::TYPE_CUSTOM) {
            $this->checkEnvironmentConfiguration();
        }
        $this->environmentVariables = $this->getRequiredVariables();
    }

    /**
     * Retrieves environment variables required to run processes.
     */
    public function getRequiredVariables(): array
    {
        if ($this->environment->getType() !== EnvironmentEntity::TYPE_CUSTOM) {
            $result = [
                'COMPOSE_FILE' => sprintf('%s/var/docker/docker-compose.yml', $this->environment->getLocation()),
                'COMPOSE_PROJECT_NAME' => $this->environment->getType().'_'.$this->environment->getName(),
                'DOCKER_PHP_IMAGE' => getenv('DOCKER_PHP_IMAGE'),
                'PROJECT_LOCATION' => $this->environment->getLocation(),
            ];
        } else {
            $result = [
                'COMPOSE_FILE' => sprintf('%s/docker-compose.yml', $this->environment->getLocation()),
                'COMPOSE_PROJECT_NAME' => $this->environment->getType().'_'.$this->environment->getName(),
                'PROJECT_LOCATION' => $this->environment->getLocation(),
            ];
        }

        return $result;
    }

    /**
     * Pulls/Builds the Docker images associated to the current environment.
     */
    public function prepareServices(): bool
    {
        $command = ['docker-compose', 'pull'];
        $pullProcess = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        $command = ['docker-compose', 'build', '--pull', '--parallel'];
        $buildProcess = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $pullProcess->isSuccessful() && $buildProcess->isSuccessful();
    }

    /**
     * Shows the resources usage of the services associated to the current environment.
     */
    public function showResourcesUsage(): bool
    {
        $command = 'docker-compose ps -q | xargs docker stats';
        $process = $this->processFactory->runForegroundProcessFromShellCommandLine($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Shows the logs of the services associated to the current environment.
     */
    public function showServicesLogs(?int $tail = 0, ?string $service = ''): bool
    {
        $command = ['docker-compose', 'logs', '--follow', sprintf('--tail=%s', $tail)];
        if ($service) {
            $command[] = $service;
        }
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Shows the status of the services associated to the current environment.
     */
    public function showServicesStatus(): bool
    {
        $command = ['docker-compose', 'ps'];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Restarts the services of the current environment.
     */
    public function restartServices(): bool
    {
        $command = ['docker-compose', 'restart'];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Starts the services after building the associated images.
     */
    public function startServices(): bool
    {
        $command = ['docker-compose', 'up', '--build', '--detach', '--remove-orphans'];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Stops the services of the current environment.
     */
    public function stopServices(): bool
    {
        $command = ['docker-compose', 'stop'];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Allow "www-data:www-data" to use the shared SSH agent.
     */
    public function fixPermissionsOnSharedSSHAgent(): bool
    {
        $command = ['docker-compose', 'exec', 'php', 'sh', '-c', 'chown www-data:www-data /run/host-services/ssh-auth.sock'];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Opens a terminal on the service associated to the command.
     */
    public function openTerminal(string $service, string $user = ''): bool
    {
        $command = ['docker-compose', 'exec'];

        if ($user !== '') {
            $command = array_merge($command, ['-u', $user, $service, 'sh', '-l']);
        } else {
            $command = array_merge($command, [$service, 'sh', '-l']);
        }

        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Removes the services of the current environment.
     */
    public function removeServices(): bool
    {
        $command = ['docker-compose', 'down', '--rmi', 'local', '--volumes', '--remove-orphans'];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Checks whether the environment has been installed and correctly configured.
     *
     * @throws InvalidEnvironmentException
     */
    private function checkEnvironmentConfiguration(): void
    {
        $dotEnvConstraint = new DotEnvExists();
        $errors = $this->validator->validate($this->environment, $dotEnvConstraint);
        if (!$errors->has(0)) {
            $dotenv = new Dotenv(true);
            $dotenv->overload(sprintf('%s/var/docker/.env', $this->environment->getLocation()));
        } else {
            throw new InvalidEnvironmentException($errors[0]->getMessage());
        }

        $filesConstraint = new ConfigurationFiles();
        $errors = $this->validator->validate($this->environment, $filesConstraint);
        if ($errors->has(0)) {
            throw new InvalidEnvironmentException($errors[0]->getMessage());
        }
    }
}
