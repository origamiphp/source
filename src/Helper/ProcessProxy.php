<?php

declare(strict_types=1);

namespace App\Helper;

use App\Exception\InvalidEnvironmentException;
use Symfony\Component\Process\Process;

/**
 * @codeCoverageIgnore
 */
class ProcessProxy
{
    /**
     * Retrieves the current working directory, or throws \App\Exception\InvalidEnvironmentException on failure.
     *
     * @throws InvalidEnvironmentException
     */
    public function getWorkingDirectory(): string
    {
        if (!$cwd = getcwd()) {
            throw new InvalidEnvironmentException('Unable to determine the current working directory.');
        }

        return $cwd;
    }

    /**
     * Checks whether TTY is supported on the current operating system.
     */
    public function isTtySupported(): bool
    {
        return Process::isTtySupported();
    }
}
