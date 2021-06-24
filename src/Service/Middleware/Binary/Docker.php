<?php

declare(strict_types=1);

namespace App\Service\Middleware\Binary;

use App\Service\CurrentContext;
use App\Service\Middleware\Wrapper\ProcessFactory;
use App\ValueObject\EnvironmentEntity;

class Docker
{
    private CurrentContext $currentContext;
    private ProcessFactory $processFactory;
    private string $installDir;

    public function __construct(CurrentContext $currentContext, ProcessFactory $processFactory, string $installDir)
    {
        $this->currentContext = $currentContext;
        $this->processFactory = $processFactory;
        $this->installDir = $installDir;
    }

    /**
     * Pulls the Docker images associated to the current environment.
     */
    public function pullServices(): bool
    {
        $environment = $this->currentContext->getActiveEnvironment();

        $action = ['pull'];
        $command = array_merge(['docker', 'compose'], $this->getDefaultComposeOptions($environment), $action);
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcess($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Builds the Docker images associated to the current environment.
     */
    public function buildServices(): bool
    {
        $environment = $this->currentContext->getActiveEnvironment();

        $action = ['build', '--pull', '--parallel'];
        $command = array_merge(['docker', 'compose'], $this->getDefaultComposeOptions($environment), $action);
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcess($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Shows the resources usage of the services associated to the current environment.
     */
    public function showResourcesUsage(): bool
    {
        $environment = $this->currentContext->getActiveEnvironment();

        $defaultOptions = implode(' ', $this->getDefaultComposeOptions($environment));
        $command = "docker compose {$defaultOptions} ps --quiet | xargs docker stats";
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcessFromShellCommandLine($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Shows the logs of the services associated to the current environment.
     */
    public function showServicesLogs(?int $tail = null, ?string $service = null): bool
    {
        $environment = $this->currentContext->getActiveEnvironment();

        $action = ['logs', '--follow', sprintf('--tail=%s', $tail ?? 0)];
        if ($service) {
            $action[] = $service;
        }
        $command = array_merge(['docker', 'compose'], $this->getDefaultComposeOptions($environment), $action);
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcess($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Shows the status of the services associated to the current environment.
     */
    public function showServicesStatus(): bool
    {
        $environment = $this->currentContext->getActiveEnvironment();

        $action = ['ps'];
        $command = array_merge(['docker', 'compose'], $this->getDefaultComposeOptions($environment), $action);
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcess($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Restarts the services of the current environment.
     */
    public function restartServices(): bool
    {
        $environment = $this->currentContext->getActiveEnvironment();

        $action = ['restart'];
        $command = array_merge(['docker', 'compose'], $this->getDefaultComposeOptions($environment), $action);
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcess($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Starts the services after building the associated images.
     */
    public function startServices(): bool
    {
        $environment = $this->currentContext->getActiveEnvironment();

        $action = ['up', '--build', '--detach', '--remove-orphans'];
        $command = array_merge(['docker', 'compose'], $this->getDefaultComposeOptions($environment), $action);
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcess($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Stops the services of the current environment.
     */
    public function stopServices(): bool
    {
        $environment = $this->currentContext->getActiveEnvironment();

        $action = ['stop'];
        $command = array_merge(['docker', 'compose'], $this->getDefaultComposeOptions($environment), $action);
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcess($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Allow "www-data:www-data" to use the shared SSH agent.
     */
    public function fixPermissionsOnSharedSSHAgent(): bool
    {
        $environment = $this->currentContext->getActiveEnvironment();

        $action = ['exec', '-T', 'php', 'bash', '-c', 'chown www-data:www-data /run/host-services/ssh-auth.sock'];
        $command = array_merge(['docker', 'compose'], $this->getDefaultComposeOptions($environment), $action);
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcess($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Opens a terminal on the service associated to the command.
     */
    public function openTerminal(string $service, string $user = ''): bool
    {
        $environment = $this->currentContext->getActiveEnvironment();

        // There is an issue when allocating a TTY with the "docker compose exec" instruction.
        $container = $environment->getType().'_'.$environment->getName()."_{$service}_1";

        $command = $user !== ''
            ? "docker exec -it --user={$user} {$container} bash --login"
            : "docker exec -it {$container} bash --login"
        ;
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcessFromShellCommandLine($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Removes the services of the current environment.
     */
    public function removeServices(): bool
    {
        $environment = $this->currentContext->getActiveEnvironment();

        $action = ['down', '--rmi', 'local', '--volumes', '--remove-orphans'];
        $command = array_merge(['docker', 'compose'], $this->getDefaultComposeOptions($environment), $action);
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcess($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Retrieves the options required by the "docker compose" commands when working in different directories.
     *
     * @return string[]
     */
    private function getDefaultComposeOptions(EnvironmentEntity $environment): array
    {
        $location = $environment->getLocation();

        return [
            '--file='.$location.$this->installDir.'/docker-compose.yml',
            '--project-directory='.$location,
            '--project-name='.$this->currentContext->getProjectName(),
        ];
    }

    /**
     * Retrieves environment variables required to run processes.
     *
     * @return array<string, string>
     */
    private function getEnvironmentVariables(EnvironmentEntity $environment): array
    {
        $projectName = $this->currentContext->getProjectName();

        return [
            'PROJECT_NAME' => $projectName,
            'PROJECT_LOCATION' => $environment->getLocation(),
            // @deprecated
            'COMPOSE_PROJECT_NAME' => $projectName,
        ];
    }
}
