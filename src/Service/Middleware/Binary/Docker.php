<?php

declare(strict_types=1);

namespace App\Service\Middleware\Binary;

use App\Service\ApplicationContext;
use App\Service\Middleware\Database;
use App\Service\Wrapper\ProcessFactory;
use App\ValueObject\EnvironmentEntity;

class Docker
{
    public function __construct(
        private ApplicationContext $applicationContext,
        private ProcessFactory $processFactory,
        private string $installDir
    ) {
    }

    /**
     * Retrieves the version of the binary installed on the host.
     */
    public function getVersion(): string
    {
        return $this->processFactory->runBackgroundProcess(['docker', '--version'])->getOutput();
    }

    /**
     * Pulls the Docker images associated to the current environment.
     */
    public function pullServices(): bool
    {
        $environment = $this->applicationContext->getActiveEnvironment();

        $action = ['pull'];
        $command = array_merge(['docker', 'compose'], $action);
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcess($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Builds the Docker images associated to the current environment.
     */
    public function buildServices(): bool
    {
        $environment = $this->applicationContext->getActiveEnvironment();

        $action = ['build', '--pull', '--parallel'];
        $command = array_merge(['docker', 'compose'], $action);
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcess($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Shows the resources usage of the services associated to the current environment.
     */
    public function showResourcesUsage(): bool
    {
        $environment = $this->applicationContext->getActiveEnvironment();

        $command = 'docker compose ps --quiet | xargs docker stats';
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcessFromShellCommandLine($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Shows the logs of the services associated to the current environment.
     */
    public function showServicesLogs(?int $tail = null, ?string $service = null): bool
    {
        $environment = $this->applicationContext->getActiveEnvironment();

        $action = ['logs', '--follow', sprintf('--tail=%s', $tail ?? 0)];
        if ($service) {
            $action[] = $service;
        }
        $command = array_merge(['docker', 'compose'], $action);
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcess($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Shows the status of the services associated to the current environment.
     */
    public function showServicesStatus(): bool
    {
        $environment = $this->applicationContext->getActiveEnvironment();

        $action = ['ps'];
        $command = array_merge(['docker', 'compose'], $action);
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcess($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Starts the services after building the associated images.
     */
    public function startServices(): bool
    {
        $environment = $this->applicationContext->getActiveEnvironment();

        $action = ['up', '--build', '--detach', '--remove-orphans'];
        $command = array_merge(['docker', 'compose'], $action);
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcess($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Stops the services of the current environment.
     */
    public function stopServices(): bool
    {
        $environment = $this->applicationContext->getActiveEnvironment();

        $action = ['stop'];
        $command = array_merge(['docker', 'compose'], $action);
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcess($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Allow "www-data:www-data" to use the shared SSH agent.
     */
    public function fixPermissionsOnSharedSSHAgent(): bool
    {
        $environment = $this->applicationContext->getActiveEnvironment();

        $action = ['exec', '--no-TTY', 'php', 'bash', '-c', 'chown www-data:www-data /run/host-services/ssh-auth.sock'];
        $command = array_merge(['docker', 'compose'], $action);
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcess($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Opens a terminal on the service associated to the command.
     */
    public function openTerminal(string $service, string $user = ''): bool
    {
        $environment = $this->applicationContext->getActiveEnvironment();

        $command = $user !== ''
            ? "docker exec --interactive --tty --user={$user} $(docker compose ps --quiet {$service}) bash --login"
            : "docker exec --interactive --tty $(docker compose ps --quiet {$service}) bash --login"
        ;
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcessFromShellCommandLine($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Removes the services of the current environment.
     */
    public function removeServices(): bool
    {
        $environment = $this->applicationContext->getActiveEnvironment();

        $action = ['down', '--rmi', 'local', '--volumes', '--remove-orphans'];
        $command = array_merge(['docker', 'compose'], $action);
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcess($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Removes only the database service of the current environment.
     */
    public function removeDatabaseService(): bool
    {
        $environment = $this->applicationContext->getActiveEnvironment();

        $action = ['rm', '--stop', '--force', 'database'];
        $command = array_merge(['docker', 'compose'], $action);
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcess($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Removes only the database volume of the current environment.
     */
    public function removeDatabaseVolume(): bool
    {
        $environment = $this->applicationContext->getActiveEnvironment();

        $action = ['rm', "{$environment->getType()}_{$environment->getName()}_database"];
        $command = array_merge(['docker', 'volume'], $action);
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcess($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Executes the native MySQL dump process.
     */
    public function dumpMysqlDatabase(string $username, string $password, string $path): bool
    {
        $environment = $this->applicationContext->getActiveEnvironment();

        $command = str_replace(
            ['{container}', '{username}', '{password}', '{database}', '{filename}'],
            ['$(docker compose ps --quiet database)', $username, $password, Database::DEFAULT_SERVICE_DATABASE, $path],
            'docker exec --interactive {container} mysqldump --user={username} --password={password} {database} > {filename}'
        );
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcessFromShellCommandLine($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Executes the native Postgres dump process.
     */
    public function dumpPostgresDatabase(string $username, string $password, string $path): bool
    {
        $environment = $this->applicationContext->getActiveEnvironment();

        $command = str_replace(
            ['{container}', '{username}', '{password}', '{database}', '{filename}'],
            ['$(docker compose ps --quiet database)', $username, $password, Database::DEFAULT_SERVICE_DATABASE, $path],
            'docker exec --interactive {container} pg_dump --clean --dbname=postgresql://{username}:{password}@127.0.0.1:5432/{database} > {filename}'
        );

        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcessFromShellCommandLine($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Executes the native MySQL restore process.
     */
    public function restoreMysqlDatabase(string $username, string $password, string $path): bool
    {
        $environment = $this->applicationContext->getActiveEnvironment();

        $command = str_replace(
            ['{container}', '{username}', '{password}', '{database}', '{filename}'],
            ['$(docker compose ps --quiet database)', $username, $password, Database::DEFAULT_SERVICE_DATABASE, $path],
            'docker exec --interactive {container} mysql --user={username} --password={password} {database} < {filename}'
        );
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcessFromShellCommandLine($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Executes the native Postgres restore process.
     */
    public function restorePostgresDatabase(string $username, string $password, string $path): bool
    {
        $environment = $this->applicationContext->getActiveEnvironment();

        $command = str_replace(
            ['{container}', '{username}', '{password}', '{database}', '{filename}'],
            ['$(docker compose ps --quiet database)', $username, $password, Database::DEFAULT_SERVICE_DATABASE, $path],
            'docker exec --interactive {container} psql --dbname=postgresql://{username}:{password}@127.0.0.1:5432/{database} < {filename}'
        );
        $environmentVariables = $this->getEnvironmentVariables($environment);

        return $this->processFactory->runForegroundProcessFromShellCommandLine($command, $environmentVariables)->isSuccessful();
    }

    /**
     * Retrieves environment variables required to run processes.
     *
     * @return array{COMPOSE_FILE: string, COMPOSE_PROJECT_NAME: string, PROJECT_NAME: string, PROJECT_LOCATION: string}
     */
    public function getEnvironmentVariables(EnvironmentEntity $environment): array
    {
        $location = $environment->getLocation();
        $projectName = $this->applicationContext->getProjectName();

        $composeFile = is_file($location.$this->installDir.'/docker-compose.override.yml')
            ? $location.$this->installDir.'/docker-compose.yml:'.$location.$this->installDir.'/docker-compose.override.yml'
            : $location.$this->installDir.'/docker-compose.yml'
        ;

        return [
            'COMPOSE_FILE' => $composeFile,
            'COMPOSE_PROJECT_NAME' => $projectName,
            'PROJECT_NAME' => $projectName, // deprecated
            'PROJECT_LOCATION' => $location,
        ];
    }
}
