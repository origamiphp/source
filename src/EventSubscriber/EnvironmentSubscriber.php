<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Environment\EnvironmentEntity;
use App\Event\EnvironmentInstalledEvent;
use App\Event\EnvironmentRestartedEvent;
use App\Event\EnvironmentStartedEvent;
use App\Event\EnvironmentStoppedEvent;
use App\Event\EnvironmentUninstalledEvent;
use App\Exception\InvalidEnvironmentException;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\Binary\Mutagen;
use App\Middleware\Database;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EnvironmentSubscriber implements EventSubscriberInterface
{
    /** @var DockerCompose */
    private $dockerCompose;

    /** @var Mutagen */
    private $mutagen;

    /** @var Database */
    private $database;

    public function __construct(DockerCompose $dockerCompose, Mutagen $mutagen, Database $database)
    {
        $this->dockerCompose = $dockerCompose;
        $this->mutagen = $mutagen;
        $this->database = $database;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     *
     * @uses \App\EventSubscriber\EnvironmentSubscriber::onEnvironmentStart
     * @uses \App\EventSubscriber\EnvironmentSubscriber::onEnvironmentStop
     * @uses \App\EventSubscriber\EnvironmentSubscriber::onEnvironmentRestart
     * @uses \App\EventSubscriber\EnvironmentSubscriber::onEnvironmentUninstall
     */
    public static function getSubscribedEvents(): array
    {
        return [
            EnvironmentInstalledEvent::class => 'onEnvironmentInstall',
            EnvironmentStartedEvent::class => 'onEnvironmentStart',
            EnvironmentStoppedEvent::class => 'onEnvironmentStop',
            EnvironmentRestartedEvent::class => 'onEnvironmentRestart',
            EnvironmentUninstalledEvent::class => 'onEnvironmentUninstall',
        ];
    }

    /**
     * Listener which triggers the environment registration.     *.
     */
    public function onEnvironmentInstall(EnvironmentInstalledEvent $event): void
    {
        $environment = $event->getEnvironment();

        $this->database->add($environment);
        $this->database->save();
    }

    /**
     * Listener which triggers the Docker synchronization start.
     *
     * @throws InvalidEnvironmentException
     */
    public function onEnvironmentStart(EnvironmentStartedEvent $event): void
    {
        $environment = $event->getEnvironment();
        $this->dockerCompose->setActiveEnvironment($environment);

        if ($environment->getType() !== EnvironmentEntity::TYPE_CUSTOM) {
            $environmentVariables = $this->dockerCompose->getRequiredVariables($environment);
            $io = $event->getSymfonyStyle();

            if ($this->dockerCompose->fixPermissionsOnSharedSSHAgent()) {
                $io->success('Permissions on the shared SSH agent successfully fixed.');
            } else {
                $io->error('An error occurred while trying to fix the permissions on the shared SSH agent.');
            }

            if ($this->mutagen->startDockerSynchronization($environmentVariables)) {
                $io->success('Docker synchronization successfully started.');
            } else {
                $io->error('An error occurred while starting the Docker synchronization.');
            }
        }

        $environment->activate();
        $this->database->save();
    }

    /**
     * Listener which triggers the Docker synchronization stop.
     *
     * @throws InvalidEnvironmentException
     */
    public function onEnvironmentStop(EnvironmentStoppedEvent $event): void
    {
        $environment = $event->getEnvironment();
        $this->dockerCompose->setActiveEnvironment($environment);

        if ($environment->getType() !== EnvironmentEntity::TYPE_CUSTOM) {
            $environmentVariables = $this->dockerCompose->getRequiredVariables($environment);
            $io = $event->getSymfonyStyle();

            if ($this->mutagen->stopDockerSynchronization($environmentVariables)) {
                $io->success('Docker synchronization successfully stopped.');
            } else {
                $io->error('An error occurred while stopping the Docker synchronization.');
            }
        }

        $environment->deactivate();
        $this->database->save();
    }

    /**
     * Listener which triggers the Docker synchronization restart.
     *
     * @throws InvalidEnvironmentException
     */
    public function onEnvironmentRestart(EnvironmentRestartedEvent $event): void
    {
        $environment = $event->getEnvironment();
        $this->dockerCompose->setActiveEnvironment($environment);

        if ($environment->getType() !== EnvironmentEntity::TYPE_CUSTOM) {
            $environmentVariables = $this->dockerCompose->getRequiredVariables($environment);
            $io = $event->getSymfonyStyle();

            if ($this->mutagen->stopDockerSynchronization($environmentVariables)
                && $this->mutagen->startDockerSynchronization($environmentVariables)
            ) {
                $io->success('Docker synchronization successfully restarted.');
            } else {
                $io->error('An error occurred while restarting the Docker synchronization.');
            }
        }
    }

    /**
     * Listener which triggers the Docker synchronization removing and environment unregistration.
     *
     * @throws InvalidEnvironmentException
     */
    public function onEnvironmentUninstall(EnvironmentUninstalledEvent $event): void
    {
        $environment = $event->getEnvironment();
        $this->dockerCompose->setActiveEnvironment($environment);

        if ($environment->getType() !== EnvironmentEntity::TYPE_CUSTOM) {
            $environmentVariables = $this->dockerCompose->getRequiredVariables($environment);
            $io = $event->getSymfonyStyle();

            if ($this->mutagen->removeDockerSynchronization($environmentVariables)) {
                $io->success('Docker synchronization successfully removed.');
            } else {
                $io->error('An error occurred while removing the Docker synchronization.');
            }
        }

        $this->database->remove($environment);
        $this->database->save();
    }
}
