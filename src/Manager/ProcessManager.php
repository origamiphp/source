<?php

declare(strict_types=1);

namespace App\Manager;

use Symfony\Component\Process\Process;

class ProcessManager
{
    private const PROCESS_TIMEOUT_VALUE = 3600.00;
    private const DEFAULT_CONTAINER_UID = 'id:1000';
    private const DEFAULT_CONTAINER_GID = 'id:1000';

    /**
     * Checks whether the given binary is available.
     *
     * @param string $binary
     *
     * @return bool
     */
    public function isBinaryInstalled(string $binary): bool
    {
        if (strpos($binary, '/') === false) {
            $process = $this->runBackgroundProcess(['which', $binary]);
            $result = $process->isSuccessful();
        } else {
            $process = $this->runBackgroundProcess(['brew', 'list']);
            $result = strpos($process->getOutput(), substr($binary, strrpos($binary, '/') + 1)) !== false;
        }

        return $result;
    }

    /**
     * Builds or rebuilds the Docker services associated to the current environment.
     *
     * @param array $environmentVariables
     *
     * @return bool
     */
    public function buildServices(array $environmentVariables): bool
    {
        $command = ['docker-compose', 'build', '--pull', '--parallel'];
        $process = $this->runForegroundProcess($command, $environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Shows the resources usage of the Docker services associated to the current environment.
     *
     * @param array $environmentVariables
     *
     * @return bool
     */
    public function showResourcesUsage(array $environmentVariables): bool
    {
        $process = Process::fromShellCommandline(
            'docker-compose ps -q | xargs docker stats',
            null,
            $environmentVariables,
            null,
            self::PROCESS_TIMEOUT_VALUE
        );

        $process->run(static function ($type, $buffer) {
            echo Process::ERR === $type ? 'ERR > ' . $buffer : $buffer;
        });

        return $process->isSuccessful();
    }

    /**
     * Generates a locally-trusted development certificate with mkcert.
     *
     * @param string $certificate
     * @param string $privateKey
     * @param array  $domains
     *
     * @return bool
     */
    public function generateCertificate(string $certificate, string $privateKey, array $domains): bool
    {
        $command = array_merge(['mkcert', '-cert-file', $certificate, '-key-file', $privateKey], $domains);
        $process = $this->runForegroundProcess($command);

        return $process->isSuccessful();
    }

    /**
     * Shows the logs of the Docker services associated to the current environment.
     *
     * @param int         $tail
     * @param string|null $service
     * @param array       $environmentVariables
     *
     * @return bool
     */
    public function showServicesLogs(int $tail, ?string $service, array $environmentVariables): bool
    {
        $command = ['docker-compose', 'logs', '--follow', "--tail=$tail"];
        if ($service) {
            $command[] = $service;
        }
        $process = $this->runForegroundProcess($command, $environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Shows the status of the Docker services associated to the current environment.
     *
     * @param array $environmentVariables
     *
     * @return bool
     */
    public function showServicesStatus(array $environmentVariables): bool
    {
        $command = ['docker-compose', 'ps'];
        $process = $this->runForegroundProcess($command, $environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Restarts the Docker services of the current environment.
     *
     * @param array $environmentVariables
     *
     * @return bool
     */
    public function restartDockerServices(array $environmentVariables): bool
    {
        $command = ['docker-compose', 'restart'];
        $process = $this->runForegroundProcess($command, $environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Starts the Docker synchronization needed to share the project source code.
     *
     * @param array $environmentVariables
     *
     * @return bool
     */
    public function startDockerSynchronization(array $environmentVariables): bool
    {
        $projectName = $environmentVariables['COMPOSE_PROJECT_NAME'] ?? '';
        $projectLocation = $environmentVariables['PROJECT_LOCATION'] ?? '';

        if ($this->canResumeSynchronization($projectName, $environmentVariables) === false) {
            $command = [
                'mutagen',
                'create',
                '--default-owner-beta='.self::DEFAULT_CONTAINER_UID,
                '--default-group-beta='.self::DEFAULT_CONTAINER_GID,
                '--sync-mode=two-way-resolved',
                '--ignore-vcs',
                '--ignore=".idea"',
                "--label=name=$projectName",
                $projectLocation,
                $projectName ? "docker://${environmentVariables['COMPOSE_PROJECT_NAME']}_synchro/var/www/html/" : ''
            ];
        } else {
            $command = ['mutagen', 'resume', "--label-selector=name=$projectName"];
        }
        $process = $this->runForegroundProcess($command, $environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Starts the Docker services after building the associated images.
     *
     * @param array $environmentVariables
     *
     * @return bool
     */
    public function startDockerServices(array $environmentVariables): bool
    {
        $command = ['docker-compose', 'up', '--build', '--detach', '--remove-orphans'];
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
     * Stops the Docker services of the current environment.
     *
     * @param array $environmentVariables
     *
     * @return bool
     */
    public function stopDockerServices(array $environmentVariables): bool
    {
        $command = ['docker-compose', 'stop'];
        $process = $this->runForegroundProcess($command, $environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Shows a dynamic status display of the current sessions.
     *
     * @param array $environmentVariables
     *
     * @return bool
     */
    public function monitorDockerSynchronization(array $environmentVariables): bool
    {
        $command = ['mutagen', 'monitor'];
        $process = $this->runForegroundProcess($command, $environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Opens a terminal on the service associated to the command.
     *
     * @param string $service
     * @param string $user
     * @param array  $environmentVariables
     *
     * @return bool
     */
    public function openTerminal(string $service, string $user, array $environmentVariables): bool
    {
        $command = ['docker-compose', 'exec', '-u', "$user:$user", $service, 'sh', '-l'];
        $process = $this->runForegroundProcess($command, $environmentVariables);

        return $process->isSuccessful();
    }

    /**
     * Runs the given command in background and returns the process.
     *
     * @param array $command
     * @param array $environmentVariables
     *
     * @return Process
     */
    private function runBackgroundProcess(array $command, array $environmentVariables = []): Process
    {
        $process = new Process($command, null, $environmentVariables, null, self::PROCESS_TIMEOUT_VALUE);
        $process->run();

        return $process;
    }

    /**
     * Runs the given command in foreground and returns the process.
     *
     * @param array $command
     * @param array $environmentVariables
     *
     * @return Process
     */
    private function runForegroundProcess(array $command, array $environmentVariables = []): Process
    {
        $process = new Process($command, null, $environmentVariables, null, self::PROCESS_TIMEOUT_VALUE);
        $process->setTty(Process::isTtySupported());

        $process->run(static function ($type, $buffer) {
            echo Process::ERR === $type ? 'ERR > ' . $buffer : $buffer;
        });

        return $process;
    }

    /**
     * Checks whether an existing session is associated with the given project.
     *
     * @param string $projectName
     * @param array $environmentVariables
     *
     * @return bool
     */
    private function canResumeSynchronization(string $projectName, array $environmentVariables): bool
    {
        $command = ['mutagen', 'list', "--label-selector=name=$projectName"];
        $process = $this->runBackgroundProcess($command, $environmentVariables);

        return $process->getOutput() !== '';
    }
}
