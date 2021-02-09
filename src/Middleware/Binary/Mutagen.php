<?php

declare(strict_types=1);

namespace App\Middleware\Binary;

use App\Helper\ProcessFactory;

class Mutagen
{
    private const DEFAULT_CONTAINER_UID = 'id:1000';
    private const DEFAULT_CONTAINER_GID = 'id:1000';

    private ProcessFactory $processFactory;

    public function __construct(ProcessFactory $processFactory)
    {
        $this->processFactory = $processFactory;
    }

    /**
     * Starts the Docker synchronization needed to share the project source code.
     */
    public function startDockerSynchronization(array $environmentVariables): bool
    {
        $environmentName = $environmentVariables['COMPOSE_PROJECT_NAME'] ?? '';
        $environmentLocation = $environmentVariables['PROJECT_LOCATION'] ?? '';

        if (!$this->canResumeSynchronization($environmentName, $environmentVariables)) {
            $command = [
                'mutagen',
                'create',
                '--default-owner-beta='.self::DEFAULT_CONTAINER_UID,
                '--default-group-beta='.self::DEFAULT_CONTAINER_GID,
                '--sync-mode=two-way-resolved',
                '--ignore-vcs',
                '--symlink-mode=posix-raw',
                sprintf('--label=name=%s', $environmentName),
                $environmentLocation,
                $environmentName
                    ? sprintf('docker://%s_synchro/var/www/html/', $environmentVariables['COMPOSE_PROJECT_NAME'])
                    : '',
            ];
        } else {
            $command = ['mutagen', 'resume', sprintf('--label-selector=name=%s', $environmentName)];
        }
        $process = $this->processFactory->runForegroundProcess($command, $environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Stops the Docker synchronization needed to share the project source code.
     */
    public function stopDockerSynchronization(array $environmentVariables): bool
    {
        $command = ['mutagen', 'pause', sprintf('--label-selector=name=%s', $environmentVariables['COMPOSE_PROJECT_NAME'])];
        $process = $this->processFactory->runForegroundProcess($command, $environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Removes the Docker synchronization needed to share the project source code.
     */
    public function removeDockerSynchronization(array $environmentVariables): bool
    {
        $command = ['mutagen', 'terminate', sprintf('--label-selector=name=%s', $environmentVariables['COMPOSE_PROJECT_NAME'])];
        $process = $this->processFactory->runForegroundProcess($command, $environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Checks whether an existing session is associated with the given environment.
     */
    private function canResumeSynchronization(string $environmentName, array $environmentVariables): bool
    {
        $command = ['mutagen', 'list', sprintf('--label-selector=name=%s', $environmentName)];
        $process = $this->processFactory->runBackgroundProcess($command, $environmentVariables);

        return strpos($process->getOutput(), $environmentVariables['COMPOSE_PROJECT_NAME']) !== false;
    }
}
