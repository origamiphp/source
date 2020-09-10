<?php

declare(strict_types=1);

namespace App\Environment\EnvironmentMaker;

use App\Helper\ProcessFactory;

class RequirementsChecker
{
    private const MUTAGEN_MINIMUM_VERSION = '0.12.0-beta1';

    /** @var ProcessFactory */
    private $processFactory;

    public function __construct(ProcessFactory $processFactory)
    {
        $this->processFactory = $processFactory;
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
                'name' => 'docker-compose',
                'description' => 'Define and run multi-container applications with Docker.',
                'status' => $this->isDockerComposeInstalled(),
            ],
            [
                'name' => 'mutagen',
                'description' => 'Fast and efficient way to synchronize code to Docker containers.',
                'status' => $this->isMutagenBetaInstalled(),
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
        return $this->processFactory->runBackgroundProcess(['which', 'mkcert'])->isSuccessful();
    }

    /**
     * Checks whether Docker is available in the system.
     */
    private function isDockerInstalled(): bool
    {
        return $this->processFactory->runBackgroundProcess(['which', 'docker'])->isSuccessful();
    }

    /**
     * Checks whether Docker Composer is available in the system.
     */
    private function isDockerComposeInstalled(): bool
    {
        return $this->processFactory->runBackgroundProcess(['which', 'docker-compose'])->isSuccessful();
    }

    /**
     * Checks whether Mutagen is available with the correct version in the system.
     */
    private function isMutagenBetaInstalled(): bool
    {
        $process = $this->processFactory->runBackgroundProcess(['mutagen', 'version']);
        $version = trim($process->getOutput());

        return $process->isSuccessful() && version_compare($version, self::MUTAGEN_MINIMUM_VERSION) !== -1;
    }
}
