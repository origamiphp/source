<?php

declare(strict_types=1);

namespace App\Middleware\Binary;

use App\Traits\CustomProcessTrait;

class Mutagen
{
    use CustomProcessTrait;

    private const DEFAULT_CONTAINER_UID = 'id:1000';
    private const DEFAULT_CONTAINER_GID = 'id:1000';

    /**
     * Starts the Docker synchronization needed to share the project source code.
     *
     * @param array $environmentVariables
     *
     * @return bool
     */
    public function startDockerSynchronization(array $environmentVariables): bool
    {
        $environmentName = $environmentVariables['COMPOSE_PROJECT_NAME'] ?? '';
        $environmentLocation = $environmentVariables['PROJECT_LOCATION'] ?? '';

        if ($this->canResumeSynchronization($environmentName, $environmentVariables) === false) {
            $command = [
                'mutagen',
                'create',
                '--default-owner-beta='.self::DEFAULT_CONTAINER_UID,
                '--default-group-beta='.self::DEFAULT_CONTAINER_GID,
                '--sync-mode=two-way-resolved',
                '--ignore-vcs',
                '--ignore=".idea"',
                "--label=name=$environmentName",
                $environmentLocation,
                $environmentName
                    ? "docker://${environmentVariables['COMPOSE_PROJECT_NAME']}_synchro/var/www/html/"
                    : '',
            ];
        } else {
            $command = ['mutagen', 'resume', "--label-selector=name=$environmentName"];
        }
        $process = $this->runForegroundProcess($command, $environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Stops the Docker synchronization needed to share the project source code.
     *
     * @param array $environmentVariables
     *
     * @return bool
     */
    public function stopDockerSynchronization(array $environmentVariables): bool
    {
        $command = ['mutagen', 'pause', "--label-selector=name=${environmentVariables['COMPOSE_PROJECT_NAME']}"];
        $process = $this->runForegroundProcess($command, $environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Checks whether an existing session is associated with the given environment.
     *
     * @param string $environmentName
     * @param array  $environmentVariables
     *
     * @return bool
     */
    private function canResumeSynchronization(string $environmentName, array $environmentVariables): bool
    {
        $command = ['mutagen', 'list', "--label-selector=name=$environmentName"];
        $process = $this->runBackgroundProcess($command, $environmentVariables);

        return $process->getOutput() !== '';
    }
}
