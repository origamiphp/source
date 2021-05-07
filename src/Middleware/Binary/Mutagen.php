<?php

declare(strict_types=1);

namespace App\Middleware\Binary;

use App\Helper\CurrentContext;
use App\Helper\ProcessFactory;

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
     * Starts the Docker synchronization needed to share the project source code.
     */
    public function startDockerSynchronization(): bool
    {
        $environment = $this->currentContext->getActiveEnvironment();
        $projectName = $this->currentContext->getProjectName();

        if (!$this->canResumeSynchronization()) {
            $command = [
                'mutagen',
                'sync',
                'create',
                '--default-owner-beta='.self::DEFAULT_CONTAINER_UID,
                '--default-group-beta='.self::DEFAULT_CONTAINER_GID,
                '--sync-mode=two-way-resolved',
                '--ignore-vcs',
                '--symlink-mode=posix-raw',
                "--label=name={$projectName}",
                $environment->getLocation(),
                "docker://{$projectName}_synchro/var/www/html/",
            ];
        } else {
            $command = ['mutagen', 'sync', 'resume', "--label-selector=name={$projectName}"];
        }
        $process = $this->processFactory->runForegroundProcess($command);

        return $process->isSuccessful();
    }

    /**
     * Stops the Docker synchronization needed to share the project source code.
     */
    public function stopDockerSynchronization(): bool
    {
        $projectName = $this->currentContext->getProjectName();
        $command = ['mutagen', 'sync', 'pause', "--label-selector=name={$projectName}"];

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

    /**
     * Checks whether an existing session is associated with the given environment.
     */
    private function canResumeSynchronization(): bool
    {
        $projectName = $this->currentContext->getProjectName();
        $command = ['mutagen', 'sync', 'list', "--label-selector=name={$projectName}"];

        return strpos($this->processFactory->runBackgroundProcess($command)->getOutput(), $projectName) !== false;
    }
}
