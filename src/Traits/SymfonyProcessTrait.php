<?php

declare(strict_types=1);

namespace App\Traits;

use Symfony\Component\Process\Process;

trait SymfonyProcessTrait
{
    /**
     * Runs the given process in foreground and prints its logs in real-time.
     *
     * @param Process $process
     */
    private function foreground(Process $process): void
    {
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });
    }
}
