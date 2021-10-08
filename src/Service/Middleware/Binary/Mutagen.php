<?php

declare(strict_types=1);

namespace App\Service\Middleware\Binary;

use App\Service\CurrentContext;
use App\Service\Wrapper\ProcessFactory;

class Mutagen
{
    private const DEFAULT_CONTAINER_UID = 'id:1000';
    private const DEFAULT_CONTAINER_GID = 'id:1000';

    private CurrentContext $currentContext;
    private ProcessFactory $processFactory;

    public function __construct(CurrentContext $currentContext, ProcessFactory $processFactory)
    {
        $this->currentContext = $currentContext;
        $this->processFactory = $processFactory;
    }

    /**
     * Retrieves the version of the binary installed on the host.
     */
    public function getVersion(): string
    {
        return $this->processFactory->runBackgroundProcess(['mutagen', 'version'])->getOutput();
    }

    /**
     * Starts the Docker synchronization needed to share the project source code.
     */
    public function startDockerSynchronization(): bool
    {
        $environment = $this->currentContext->getActiveEnvironment();
        $projectName = $this->currentContext->getProjectName();

        $command = [
            'mutagen',
            'sync',
            'create',
            '--default-owner-beta='.self::DEFAULT_CONTAINER_UID,
            '--default-group-beta='.self::DEFAULT_CONTAINER_GID,
            '--sync-mode=two-way-resolved',
            '--ignore-vcs',
            '--ignore=var/cache/**',
            '--ignore=var/page_cache/**',
            '--ignore=var/view_preprocessed/**',
            '--symlink-mode=posix-raw',
            "--label=name={$projectName}",
            $environment->getLocation(),
            "docker://{$projectName}_synchro/var/www/html/",
        ];

        return $this->processFactory->runForegroundProcess($command)->isSuccessful();
    }

    /**
     * Removes the Docker synchronization needed to share the project source code.
     */
    public function removeDockerSynchronization(): bool
    {
        $projectName = $this->currentContext->getProjectName();
        $command = ['mutagen', 'sync', 'terminate', "--label-selector=name={$projectName}"];

        return $this->processFactory->runForegroundProcess($command)->isSuccessful();
    }
}
