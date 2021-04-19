<?php

declare(strict_types=1);

namespace App\Environment\EnvironmentMaker;

use Symfony\Component\Process\ExecutableFinder;

class RequirementsChecker
{
    private ExecutableFinder $executableFinder;

    public function __construct(ExecutableFinder $executableFinder)
    {
        $this->executableFinder = $executableFinder;
    }

    /**
     * Checks whether the application mandatory requirements are installed.
     */
    public function checkMandatoryRequirements(): array
    {
        return [
            [
                'name' => 'docker',
                'description' => 'A self-sufficient runtime for containers.',
                'status' => $this->isDockerInstalled(),
            ],
            [
                'name' => 'mutagen',
                'description' => 'Fast and efficient way to synchronize code to Docker containers.',
                'status' => $this->isMutagenInstalled(),
            ],
        ];
    }

    /**
     * Checks whether the application non-mandatory requirements are installed.
     */
    public function checkNonMandatoryRequirements(): array
    {
        return [
            [
                'name' => 'mkcert',
                'description' => 'A simple zero-config tool to make locally trusted development certificates.',
                'status' => $this->canMakeLocallyTrustedCertificates(),
            ],
        ];
    }

    /**
     * Checks whether Mkcert is available in the system.
     */
    public function canMakeLocallyTrustedCertificates(): bool
    {
        return $this->executableFinder->find('mkcert') !== null;
    }

    /**
     * Checks whether Docker is available in the system.
     */
    private function isDockerInstalled(): bool
    {
        return $this->executableFinder->find('docker') !== null;
    }

    /**
     * Checks whether Mutagen is available in the system.
     */
    private function isMutagenInstalled(): bool
    {
        return $this->executableFinder->find('mutagen') !== null;
    }
}
