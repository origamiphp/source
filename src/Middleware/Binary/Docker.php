<?php

declare(strict_types=1);

namespace App\Middleware\Binary;

use App\Environment\Configuration\AbstractConfiguration;
use App\Environment\EnvironmentEntity;
use App\Helper\ProcessFactory;

class Docker
{
    private ProcessFactory $processFactory;
    private array $environmentVariables = [];

    public function __construct(ProcessFactory $processFactory)
    {
        $this->processFactory = $processFactory;
    }

    /**
     * Loads the environment variables associated to the given environment.
     */
    public function refreshEnvironmentVariables(EnvironmentEntity $environment): void
    {
        $this->environmentVariables = $this->getRequiredVariables($environment);
    }

    /**
     * Retrieves environment variables required to run processes.
     */
    public function getRequiredVariables(EnvironmentEntity $environment): array
    {
        return [
            'COMPOSE_FILE' => $environment->getLocation().AbstractConfiguration::INSTALLATION_DIRECTORY.'docker-compose.yml',
            'COMPOSE_PROJECT_NAME' => "{$environment->getType()}_{$environment->getName()}",
            'DOCKER_DATABASE_IMAGE' => getenv('DOCKER_DATABASE_IMAGE'),
            'DOCKER_PHP_IMAGE' => getenv('DOCKER_PHP_IMAGE'),
            'PROJECT_LOCATION' => $environment->getLocation(),
        ];
    }

    /**
     * Pulls/Builds the Docker images associated to the current environment.
     */
    public function prepareServices(): bool
    {
        $command = ['docker', 'compose', 'pull'];
        $pullProcess = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        $command = ['docker', 'compose', 'build', '--pull', '--parallel'];
        $buildProcess = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $pullProcess->isSuccessful() && $buildProcess->isSuccessful();
    }

    /**
     * Shows the resources usage of the services associated to the current environment.
     */
    public function showResourcesUsage(): bool
    {
        $command = 'docker compose ps -q | xargs docker stats';
        $process = $this->processFactory->runForegroundProcessFromShellCommandLine($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Shows the logs of the services associated to the current environment.
     */
    public function showServicesLogs(?int $tail = null, ?string $service = null): bool
    {
        $command = ['docker', 'compose', 'logs', '--follow', sprintf('--tail=%s', $tail ?? 0)];

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
        $command = ['docker', 'compose', 'ps'];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Restarts the services of the current environment.
     */
    public function restartServices(): bool
    {
        $command = ['docker', 'compose', 'restart'];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Starts the services after building the associated images.
     */
    public function startServices(): bool
    {
        $command = ['docker', 'compose', 'up', '--build', '--detach', '--remove-orphans'];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Stops the services of the current environment.
     */
    public function stopServices(): bool
    {
        $command = ['docker', 'compose', 'stop'];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Allow "www-data:www-data" to use the shared SSH agent.
     */
    public function fixPermissionsOnSharedSSHAgent(): bool
    {
        $command = ['docker', 'compose', 'exec', '-T', 'php', 'bash', '-c', 'chown www-data:www-data /run/host-services/ssh-auth.sock'];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Opens a terminal on the service associated to the command.
     */
    public function openTerminal(string $service, string $user = ''): bool
    {
        // There is an issue when allocating a TTY with the "docker compose exec" instruction.
        $container = $this->environmentVariables['COMPOSE_PROJECT_NAME']."_{$service}_1";

        $command = $user !== ''
            ? "docker exec -it --user={$user} {$container} bash --login"
            : "docker exec -it {$container} bash --login"
        ;
        $process = $this->processFactory->runForegroundProcessFromShellCommandLine($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Removes the services of the current environment.
     */
    public function removeServices(): bool
    {
        $command = ['docker', 'compose', 'down', '--rmi', 'local', '--volumes', '--remove-orphans'];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }
}
