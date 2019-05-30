<?php

declare(strict_types=1);

namespace App\Manager\Process;

use App\Traits\CustomProcessTrait;
use Symfony\Component\Process\Process;

class DockerCompose
{
    use CustomProcessTrait;

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
}
