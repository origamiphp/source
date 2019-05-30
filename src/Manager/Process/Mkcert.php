<?php

declare(strict_types=1);

namespace App\Manager\Process;

use App\Traits\CustomProcessTrait;

class Mkcert
{
    use CustomProcessTrait;

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
}
