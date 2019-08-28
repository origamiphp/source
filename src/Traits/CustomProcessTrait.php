<?php

declare(strict_types=1);

namespace App\Traits;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

trait CustomProcessTrait
{
    /** @var LoggerInterface */
    private $logger;

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
        $this->logger->debug('Command "{command}" will be executed.', ['command' => implode(' ', $command)]);

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
        $this->logger->debug('Command "{command}" will be executed.', ['command' => implode(' ', $command)]);

        $process = new Process($command, null, $environmentVariables, null, 3600.00);
        $process->setTty(Process::isTtySupported());

        $process->run(static function ($type, $buffer) {
            echo Process::ERR === $type ? 'ERR > '.$buffer : $buffer;
        });

        return $process;
    }
}
