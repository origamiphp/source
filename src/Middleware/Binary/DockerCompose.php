<?php

declare(strict_types=1);

namespace App\Middleware\Binary;

use App\Entity\Environment;
use App\Exception\InvalidEnvironmentException;
use App\Traits\CustomProcessTrait;
use App\Validator\Constraints\ConfigurationFiles;
use App\Validator\Constraints\DotEnvExists;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Process\Process;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DockerCompose
{
    use CustomProcessTrait;

    /** @var ValidatorInterface */
    private $validator;

    /** @var Environment */
    private $environment;

    /** @var array */
    private $environmentVariables = [];

    /**
     * DockerCompose constructor.
     *
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
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
     * Checks whether the environment has been installed and correctly configured.
     *
     * @throws InvalidEnvironmentException
     */
    protected function checkEnvironmentConfiguration(): void
    {
        $dotEnvConstraint = new DotEnvExists();
        $errors = $this->validator->validate($this->environment, $dotEnvConstraint);
        if ($errors->has(0) !== true) {
            $dotenv = new Dotenv();
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

    /**
     * Builds or rebuilds the Docker services associated to the current environment.
     *
     * @return bool
     */
    public function buildServices(): bool
    {
        $command = ['docker-compose', 'build', '--pull', '--parallel'];
        $process = $this->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Shows the resources usage of the Docker services associated to the current environment.
     *
     * @return bool
     */
    public function showResourcesUsage(): bool
    {
        $process = Process::fromShellCommandline(
            'docker-compose ps -q | xargs docker stats',
            null,
            $this->environmentVariables,
            null,
            3600.00
        );

        $process->run(static function ($type, $buffer) {
            echo Process::ERR === $type ? 'ERR > '.$buffer : $buffer;
        });

        return $process->isSuccessful();
    }

    /**
     * Shows the logs of the Docker services associated to the current environment.
     *
     * @param int         $tail
     * @param string|null $service
     *
     * @return bool
     */
    public function showServicesLogs(int $tail, ?string $service): bool
    {
        $command = ['docker-compose', 'logs', '--follow', "--tail=$tail"];
        if ($service) {
            $command[] = $service;
        }
        $process = $this->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Shows the status of the Docker services associated to the current environment.
     *
     * @return bool
     */
    public function showServicesStatus(): bool
    {
        $command = ['docker-compose', 'ps'];
        $process = $this->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Restarts the Docker services of the current environment.
     *
     * @return bool
     */
    public function restartDockerServices(): bool
    {
        $command = ['docker-compose', 'restart'];
        $process = $this->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Starts the Docker services after building the associated images.
     *
     * @return bool
     */
    public function startDockerServices(): bool
    {
        $command = ['docker-compose', 'up', '--build', '--detach', '--remove-orphans'];
        $process = $this->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Stops the Docker services of the current environment.
     *
     * @return bool
     */
    public function stopDockerServices(): bool
    {
        $command = ['docker-compose', 'stop'];
        $process = $this->runForegroundProcess($command, $this->environmentVariables);

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
    public function openTerminal(string $service, string $user): bool
    {
        $command = ['docker-compose', 'exec', '-u', "$user:$user", $service, 'sh', '-l'];
        $process = $this->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }
}
