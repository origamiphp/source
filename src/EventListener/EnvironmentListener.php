<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\EnvironmentStartedEvent;
use App\Event\EnvironmentStoppedEvent;
use App\Manager\ProcessManager;

class EnvironmentListener
{
    /** @var ProcessManager */
    private $processManager;

    /**
     * EnvironmentListener constructor.
     *
     * @param ProcessManager $processManager
     */
    public function __construct(ProcessManager $processManager)
    {
        $this->processManager = $processManager;
    }

    /**
     * Listener which starts the Docker synchronization.
     *
     * @param EnvironmentStartedEvent $event
     */
    public function onEnvironmentStart(EnvironmentStartedEvent $event): void
    {
        $io = $event->getSymfonyStyle();

        if ($this->processManager->startDockerSynchronization($event->getEnvironmentVariables())) {
            $io->success('Docker synchronization successfully started.');
        } else {
            $io->error('An error occurred while starting the Docker synchronization.');
        }
    }

    /**
     * Listener which stops the Docker synchronization.
     *
     * @param EnvironmentStoppedEvent $event
     */
    public function onEnvironmentStop(EnvironmentStoppedEvent $event): void
    {
        $io = $event->getSymfonyStyle();

        if ($this->processManager->stopDockerSynchronization($event->getEnvironmentVariables())) {
            $io->success('Docker synchronization successfully stopped.');
        } else {
            $io->error('An error occurred while stopping the Docker synchronization.');
        }
    }
}
