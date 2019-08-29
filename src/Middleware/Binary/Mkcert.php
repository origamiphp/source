<?php

declare(strict_types=1);

namespace App\Middleware\Binary;

use App\Traits\CustomProcessTrait;
use Psr\Log\LoggerInterface;

class Mkcert
{
    use CustomProcessTrait;

    /**
     * Mkcert constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

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
        $process = $this->runBackgroundProcess($command);

        return $process->isSuccessful();
    }
}
