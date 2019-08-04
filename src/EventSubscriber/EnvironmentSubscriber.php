<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\EnvironmentRestartedEvent;
use App\Event\EnvironmentStartedEvent;
use App\Event\EnvironmentStoppedEvent;
use App\Manager\Process\Mutagen;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EnvironmentSubscriber implements EventSubscriberInterface
{
    /** @var Mutagen */
    private $mutagen;

    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * EnvironmentSubscriber constructor.
     *
     * @param Mutagen                $mutagen
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(Mutagen $mutagen, EntityManagerInterface $entityManager)
    {
        $this->mutagen = $mutagen;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            EnvironmentStartedEvent::class => 'onEnvironmentStart',
            EnvironmentStoppedEvent::class => 'onEnvironmentStop',
            EnvironmentRestartedEvent::class => 'onEnvironmentRestart',
        ];
    }

    /**
     * Listener which starts the Docker synchronization.
     *
     * @param EnvironmentStartedEvent $event
     */
    public function onEnvironmentStart(EnvironmentStartedEvent $event): void
    {
        $io = $event->getSymfonyStyle();

        if ($this->mutagen->startDockerSynchronization($event->getEnvironmentVariables())) {
            $io->success('Docker synchronization successfully started.');
        } else {
            $io->error('An error occurred while starting the Docker synchronization.');
        }

        $environment = $event->getEnvironment();
        $environment->setActive(true);
        $this->entityManager->flush();
    }

    /**
     * Listener which stops the Docker synchronization.
     *
     * @param EnvironmentStoppedEvent $event
     */
    public function onEnvironmentStop(EnvironmentStoppedEvent $event): void
    {
        $io = $event->getSymfonyStyle();

        if ($this->mutagen->stopDockerSynchronization($event->getEnvironmentVariables())) {
            $io->success('Docker synchronization successfully stopped.');
        } else {
            $io->error('An error occurred while stopping the Docker synchronization.');
        }

        $environment = $event->getEnvironment();
        $environment->setActive(false);
        $this->entityManager->flush();
    }

    /**
     * Listener which restarts the Docker synchronization.
     *
     * @param EnvironmentRestartedEvent $event
     */
    public function onEnvironmentRestart(EnvironmentRestartedEvent $event): void
    {
        $io = $event->getSymfonyStyle();

        if ($this->mutagen->startDockerSynchronization($event->getEnvironmentVariables())
            && $this->mutagen->stopDockerSynchronization($event->getEnvironmentVariables())
        ) {
            $io->success('Docker synchronization successfully restarted.');
        } else {
            $io->error('An error occurred while restarting the Docker synchronization.');
        }
    }
}
