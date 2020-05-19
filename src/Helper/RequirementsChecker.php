<?php

declare(strict_types=1);

namespace App\Helper;

class RequirementsChecker
{
    private const CONTAINERIZATION = [
        'name' => 'docker',
        'description' => 'A self-sufficient runtime for containers.',
    ];

    private const ORCHESTRATION = [
        'name' => 'docker-compose',
        'description' => 'Define and run multi-container applications with Docker.',
    ];

    private const PERFORMANCE = [
        'name' => 'mutagen',
        'description' => 'Fast and efficient way to synchronize code to Docker containers.',
    ];

    private const CERTIFICATES = [
        'name' => 'mkcert',
        'description' => 'A simple zero-config tool to make locally trusted development certificates.',
    ];

    private const MANDATORY_REQUIREMENTS = [self::CONTAINERIZATION, self::ORCHESTRATION];
    private const NON_MANDATORY_REQUIREMENTS = [self::PERFORMANCE, self::CERTIFICATES];

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
        $result = [];

        foreach (self::MANDATORY_REQUIREMENTS as $index => $requirement) {
            $result[$index] = $requirement;
            $result[$index]['status'] = $this->isInstalled($requirement['name']);
        }

        return $result;
    }

    /**
     * Checks whether the application non-mandatory requirements are installed.
     */
    public function checkNonMandatoryRequirements(): array
    {
        $result = [];

        foreach (self::NON_MANDATORY_REQUIREMENTS as $index => $requirement) {
            $result[$index] = $requirement;
            $result[$index]['status'] = $this->isInstalled($requirement['name']);
        }

        return $result;
    }

    /**
     * Checks whether the application can optimize the synchronization performance with a third-party tool.
     */
    public function canOptimizeSynchronizationPerformance(): bool
    {
        return $this->processFactory->runBackgroundProcess(['which', self::PERFORMANCE['name']])->isSuccessful();
    }

    /**
     * Checks whether the application can make locally trusted certificates with a third-party tool.
     */
    public function canMakeLocallyTrustedCertificates(): bool
    {
        return $this->processFactory->runBackgroundProcess(['which', self::CERTIFICATES['name']])->isSuccessful();
    }

    /**
     * Checks whether the given binary is available.
     */
    private function isInstalled(string $binary): bool
    {
        return $this->processFactory->runBackgroundProcess(['which', $binary])->isSuccessful();
    }
}
