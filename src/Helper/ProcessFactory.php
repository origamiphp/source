<?php

declare(strict_types=1);

namespace App\Helper;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class ProcessFactory
{
    private const DEFAULT_PROCESS_TIMEOUT = null;

    private ProcessProxy $processProxy;
    private LoggerInterface $logger;

    public function __construct(ProcessProxy $processProxy, LoggerInterface $logger)
    {
        $this->processProxy = $processProxy;
        $this->logger = $logger;
    }

    /**
     * Runs the given command in background and returns the process.
     */
    public function runBackgroundProcess(array $command, array $environmentVariables = []): Process
    {
        $this->logger->debug('Command "{command}" will be executed.', ['command' => implode(' ', $command)]);

        $process = new Process($command, null, $environmentVariables, null, self::DEFAULT_PROCESS_TIMEOUT);
        $process->run();

        return $process;
    }

    /**
     * Runs the given command in foreground and returns the process.
     */
    public function runForegroundProcess(array $command, array $environmentVariables = []): Process
    {
        $this->logger->debug('Command "{command}" will be executed.', ['command' => implode(' ', $command)]);

        $process = new Process($command, null, $environmentVariables, null, self::DEFAULT_PROCESS_TIMEOUT);
        $process->setTty($this->processProxy->isTtySupported());

        $process->run(static function (string $type, string $buffer) {
            echo Process::ERR === $type ? "ERR > {$buffer}" : $buffer;
        });

        return $process;
    }

    /**
     * Runs the given command in foreground as a shell command line and returns the process.
     */
    public function runForegroundProcessFromShellCommandLine(string $command, array $environmentVariables = []): Process
    {
        $this->logger->debug('Command "{command}" will be executed.', ['command' => $command]);

        $process = Process::fromShellCommandline($command, null, $environmentVariables, null, self::DEFAULT_PROCESS_TIMEOUT);
        $process->setTty($this->processProxy->isTtySupported());

        $process->run(static function (string $type, string $buffer) {
            echo Process::ERR === $type ? "ERR > {$buffer}" : $buffer;
        });

        return $process;
    }
}
