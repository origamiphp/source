<?php

declare(strict_types=1);

namespace App\Middleware\Binary;

use App\Entity\Environment;
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

    /** @var Environment */
    private $environment;

    /** @var ProcessFactory */
    private $processFactory;

    /** @var array */
    private $environmentVariables = [];

    /**
     * DockerCompose constructor.
     *
     * @param ValidatorInterface $validator
     * @param ProcessFactory     $processFactory
     */
    public function __construct(ValidatorInterface $validator, ProcessFactory $processFactory)
    {
        $this->validator = $validator;
        $this->processFactory = $processFactory;
    }

    /**
     * Defines the currently active environment.
     *
     * @param Environment $environment
     *
     * @throws InvalidEnvironmentException
     */
    public function setActiveEnvironment(Environment $environment): void
    {
        $this->environment = $environment;

        $this->checkEnvironmentConfiguration();
        $this->environmentVariables = $this->getRequiredVariables();
    }

    /**
     * Retrieves environment variables required to run processes.
     *
     * @return array
     */
    public function getRequiredVariables(): array
    {
        return [
            'COMPOSE_FILE' => "{$this->environment->getLocation()}/var/docker/docker-compose.yml",
            'COMPOSE_PROJECT_NAME' => $this->environment->getType().'_'.$this->environment->getName(),
            'DOCKER_PHP_IMAGE' => getenv('DOCKER_PHP_IMAGE'),
            'PROJECT_LOCATION' => $this->environment->getLocation(),
        ];
    }

    /**
     * Pulls/Builds the Docker images associated to the current environment.
     *
     * @return bool
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
     *
     * @return bool
     */
    public function showResourcesUsage(): bool
    {
        $command = 'docker-compose ps -q | xargs docker stats';
        $process = $this->processFactory->runForegroundProcessFromShellCommandLine($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Shows the logs of the services associated to the current environment.
     *
     * @param int         $tail
     * @param null|string $service
     *
     * @return bool
     */
    public function showServicesLogs(?int $tail = 0, ?string $service = ''): bool
    {
        $command = ['docker-compose', 'logs', '--follow', "--tail={$tail}"];
        if ($service) {
            $command[] = $service;
        }
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Shows the status of the services associated to the current environment.
     *
     * @return bool
     */
    public function showServicesStatus(): bool
    {
        $command = ['docker-compose', 'ps'];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Restarts the services of the current environment.
     *
     * @return bool
     */
    public function restartServices(): bool
    {
        $command = ['docker-compose', 'restart'];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Starts the services after building the associated images.
     *
     * @return bool
     */
    public function startServices(): bool
    {
        $command = ['docker-compose', 'up', '--build', '--detach', '--remove-orphans'];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Stops the services of the current environment.
     *
     * @return bool
     */
    public function stopServices(): bool
    {
        $command = ['docker-compose', 'stop'];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Opens a terminal on the service associated to the command.
     *
     * @param string $service
     * @param string $user
     *
     * @return bool
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
     *
     * @return bool
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
    protected function checkEnvironmentConfiguration(): void
    {
        $dotEnvConstraint = new DotEnvExists();
        $errors = $this->validator->validate($this->environment, $dotEnvConstraint);
        if ($errors->has(0) !== true) {
            $dotenv = new Dotenv(true);
            $dotenv->overload("{$this->environment->getLocation()}/var/docker/.env");
        } else {
            throw new InvalidEnvironmentException($errors[0]->getMessage());
        }

        $filesConstraint = new ConfigurationFiles();
        $errors = $this->validator->validate($this->environment, $filesConstraint);
        if ($errors->has(0) === true) {
            throw new InvalidEnvironmentException($errors[0]->getMessage());
        }
    }
}
