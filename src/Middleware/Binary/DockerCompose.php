<?php

declare(strict_types=1);

namespace App\Middleware\Binary;

use App\Environment\Configuration\AbstractConfiguration;
use App\Environment\EnvironmentEntity;
use App\Helper\ProcessFactory;

class DockerCompose
{
    private const DATABASE_BACKUP_CMD = 'tar cvf /tmp/database_backup.tar /var/lib/mysql/';
    private const DATABASE_FLUSH_CMD = 'rm -rf /var/lib/mysql/*';
    private const DATABASE_RESTORE_CMD = 'tar xvf /tmp/database_backup.tar /var/lib/mysql/';

    /** @var ProcessFactory */
    private $processFactory;

    /** @var array */
    private $environmentVariables = [];

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
        $command = ['mutagen', 'compose', 'pull'];
        $pullProcess = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        $command = ['mutagen', 'compose', 'build', '--pull', '--parallel'];
        $buildProcess = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $pullProcess->isSuccessful() && $buildProcess->isSuccessful();
    }

    /**
     * Shows the resources usage of the services associated to the current environment.
     */
    public function showResourcesUsage(): bool
    {
        $command = 'mutagen compose ps -q | xargs docker stats';
        $process = $this->processFactory->runForegroundProcessFromShellCommandLine($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Shows the logs of the services associated to the current environment.
     */
    public function showServicesLogs(?int $tail = null, ?string $service = null): bool
    {
        $command = ['mutagen', 'compose', 'logs', '--follow', sprintf('--tail=%s', $tail ?? 0)];

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
        $command = ['mutagen', 'compose', 'ps'];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Restarts the services of the current environment.
     */
    public function restartServices(): bool
    {
        $command = ['mutagen', 'compose', 'restart'];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Starts the services after building the associated images.
     */
    public function startServices(): bool
    {
        $command = ['mutagen', 'compose', 'up', '--build', '--detach', '--remove-orphans'];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Stops the services of the current environment.
     */
    public function stopServices(): bool
    {
        $command = ['mutagen', 'compose', 'stop'];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Allow "www-data:www-data" to use the shared SSH agent.
     */
    public function fixPermissionsOnSharedSSHAgent(): bool
    {
        $command = ['mutagen', 'compose', 'exec', 'php', 'sh', '-c', 'chown www-data:www-data /run/host-services/ssh-auth.sock'];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Opens a terminal on the service associated to the command.
     */
    public function openTerminal(string $service, string $user = ''): bool
    {
        $command = ['mutagen', 'compose', 'exec'];

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
        $command = ['mutagen', 'compose', 'down', '--rmi', 'local', '--volumes', '--remove-orphans'];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Backups the database volume into a TAR archive in the environment configuration.
     */
    public function backupDatabaseVolume(): bool
    {
        $source = $this->getDatabaseContainerId();
        $destination = $this->formatBackupAndRestoreBindMount();

        $command = ['docker', 'run', '--rm', "--volumes-from={$source}", "--mount={$destination}", 'busybox', 'sh', '-c', self::DATABASE_BACKUP_CMD];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Removes everything located in the database volume.
     */
    public function resetDatabaseVolume(): bool
    {
        $source = $this->getDatabaseContainerId();
        $destination = $this->formatBackupAndRestoreBindMount();

        $command = ['docker', 'run', '--rm', "--volumes-from={$source}", "--mount={$destination}", 'busybox', 'sh', '-c', self::DATABASE_FLUSH_CMD];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Restores the database volume from a TAR archive in the environment configuration.
     */
    public function restoreDatabaseVolume(): bool
    {
        $source = $this->getDatabaseContainerId();
        $destination = $this->formatBackupAndRestoreBindMount();

        $command = ['docker', 'run', '--rm', "--volumes-from={$source}", "--mount={$destination}", 'busybox', 'sh', '-c', self::DATABASE_RESTORE_CMD];
        $process = $this->processFactory->runForegroundProcess($command, $this->environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Retrieves the ID of the current database container.
     */
    private function getDatabaseContainerId(): string
    {
        $command = ['docker-compose', 'ps', '--quiet', 'database'];
        $output = $this->processFactory->runBackgroundProcess($command, $this->environmentVariables)->getOutput();

        return trim($output);
    }

    /**
     * Formats the bind-mount configuration used for the backup/restore process.
     */
    private function formatBackupAndRestoreBindMount(): string
    {
        return sprintf(
            'type=bind,source=%s,target=/tmp',
            $this->environmentVariables['PROJECT_LOCATION'].AbstractConfiguration::INSTALLATION_DIRECTORY
        );
    }
}
