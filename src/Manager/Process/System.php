<?php

declare(strict_types=1);

namespace App\Manager\Process;

use App\Traits\CustomProcessTrait;

class System
{
    use CustomProcessTrait;

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
}
