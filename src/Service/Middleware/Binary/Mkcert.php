<?php

declare(strict_types=1);

namespace App\Service\Middleware\Binary;

use App\Exception\MkcertException;
use App\Service\Wrapper\ProcessFactory;

class Mkcert
{
    private const DEFAULT_DOMAINS = ['localhost', '127.0.0.1', '::1'];

    public function __construct(private ProcessFactory $processFactory)
    {
    }

    /**
     * Retrieves the version of the binary installed on the host.
     */
    public function getVersion(): string
    {
        return $this->processFactory->runBackgroundProcess(['mkcert', '--version'])->getOutput();
    }

    /**
     * Generates a locally-trusted development certificate with mkcert.
     *
     * @throws MkcertException
     */
    public function generateCertificate(string $destination): bool
    {
        $this->installCertificateAuthority();

        $certificate = "{$destination}/nginx/certs/custom.pem";
        $privateKey = "{$destination}/nginx/certs/custom.key";

        $command = array_merge(
            ['mkcert', '-cert-file', $certificate, '-key-file', $privateKey],
            self::DEFAULT_DOMAINS,
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
