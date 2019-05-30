<?php

declare(strict_types=1);

namespace App\Traits;

use Symfony\Component\Process\Process;

trait CustomProcessTrait
{
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
        $process = new Process($command, null, $environmentVariables, null, 3600.00);
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
        $process = new Process($command, null, $environmentVariables, null, 3600.00);
        $process->setTty(Process::isTtySupported());

        $process->run(static function ($type, $buffer) {
            echo Process::ERR === $type ? 'ERR > '.$buffer : $buffer;
        });

        return $process;
    }
}
