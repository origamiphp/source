<?php

declare(strict_types=1);

namespace App\Middleware\Binary;

use App\Exception\MkcertException;
use App\Helper\ProcessFactory;

class Mkcert
{
    private const DEFAULT_DOMAINS = ['localhost', '127.0.0.1', '::1'];

    private ProcessFactory $processFactory;

    public function __construct(ProcessFactory $processFactory)
    {
        $this->processFactory = $processFactory;
    }

    /**
     * Generates a locally-trusted development certificate with mkcert.
     *
     * @throws MkcertException
     */
    public function generateCertificate(string $certificate, string $privateKey, array $domains): bool
    {
        $this->installCertificateAuthority();

        $command = array_merge(
            ['mkcert', '-cert-file', $certificate, '-key-file', $privateKey],
            self::DEFAULT_DOMAINS,
            $domains
        );
        $process = $this->processFactory->runBackgroundProcess($command);

        return $process->isSuccessful();
    }

    /**
     * Installs the local certificate authority in the system trust store.
     *
     * @throws MkcertException
     */
    private function installCertificateAuthority(): void
    {
        $command = ['mkcert', '-install'];
        $process = $this->processFactory->runBackgroundProcess($command);

        if (!$process->isSuccessful()) {
            throw new MkcertException(sprintf("Unable to install the local certificate authority.\n%s", $process->getOutput()));
        }
    }
}
