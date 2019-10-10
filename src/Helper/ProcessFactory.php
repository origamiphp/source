<?php

declare(strict_types=1);

namespace App\Helper;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class ProcessFactory
{
    /** @var LoggerInterface */
    private $logger;

    /**
     * ProcessFactory constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Runs the given command in background and returns the process.
     *
     * @param array $command
     * @param array $environmentVariables
     *
     * @return Process
     */
    public function runBackgroundProcess(array $command, array $environmentVariables = []): Process
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
    public function runForegroundProcess(array $command, array $environmentVariables = []): Process
    {
        $this->logger->debug('Command "{command}" will be executed.', ['command' => implode(' ', $command)]);

        $process = new Process($command, null, $environmentVariables, null, 3600.00);
        $process->run(static function (string $type, string $buffer) {
            echo $buffer;
        });

        return $process;
    }

    /**
     * Runs the given command in foreground as a shell command line and returns the process.
     *
     * @param string $command
     * @param array  $environmentVariables
     *
     * @return Process
     */
    public function runForegroundProcessFromShellCommandLine(string $command, array $environmentVariables = []): Process
    {
        $this->logger->debug('Command "{command}" will be executed.', ['command' => $command]);

        $process = Process::fromShellCommandline($command, null, $environmentVariables, null, 3600.00);
        $process->run(static function (string $type, string $buffer) {
            echo $buffer;
        });

        return $process;
    }
}
