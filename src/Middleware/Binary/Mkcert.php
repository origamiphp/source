<?php

declare(strict_types=1);

namespace App\Middleware\Binary;

use App\Helper\ProcessFactory;

class Mkcert
{
    /** @var ProcessFactory */
    private $processFactory;

    /**
     * Mkcert constructor.
     */
    public function __construct(ProcessFactory $processFactory)
    {
        $this->processFactory = $processFactory;
    }

    /**
     * Generates a locally-trusted development certificate with mkcert.
     */
    public function generateCertificate(string $certificate, string $privateKey, array $domains): bool
    {
        $command = array_merge(['mkcert', '-cert-file', $certificate, '-key-file', $privateKey], $domains);
        $process = $this->processFactory->runBackgroundProcess($command);

        return $process->isSuccessful();
    }
}
