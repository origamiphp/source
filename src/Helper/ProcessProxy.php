<?php

declare(strict_types=1);

namespace App\Helper;

use Symfony\Component\Process\Process;

/**
 * @codeCoverageIgnore
 */
class ProcessProxy
{
    /**
     * Checks whether TTY is supported on the current operating system.
     */
    public function isTtySupported(): bool
    {
        return Process::isTtySupported();
    }
}
