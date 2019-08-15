<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\EnvironmentRestartedEvent;
use App\Event\EnvironmentStartedEvent;
use App\Event\EnvironmentStoppedEvent;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\Binary\Mutagen;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EnvironmentSubscriber implements EventSubscriberInterface
{
    /** @var DockerCompose */
    private $dockerCompose;

    /** @var Mutagen */
    private $mutagen;

    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * EnvironmentSubscriber constructor.
     *
     * @param DockerCompose          $dockerCompose
     * @param Mutagen                $mutagen
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(DockerCompose $dockerCompose, Mutagen $mutagen, EntityManagerInterface $entityManager)
    {
        $this->dockerCompose = $dockerCompose;
        $this->mutagen = $mutagen;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     *
     * @uses \App\EventSubscriber\EnvironmentSubscriber::onEnvironmentStart
     * @uses \App\EventSubscriber\EnvironmentSubscriber::onEnvironmentStop
     * @uses \App\EventSubscriber\EnvironmentSubscriber::onEnvironmentRestart
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
     *
     * @throws \App\Exception\InvalidEnvironmentException
     */
    public function onEnvironmentStart(EnvironmentStartedEvent $event): void
    {
        $this->dockerCompose->setActiveEnvironment($event->getEnvironment());
        $environmentVariables = $this->dockerCompose->getRequiredVariables();

        $io = $event->getSymfonyStyle();

        if ($this->mutagen->startDockerSynchronization($environmentVariables)) {
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
     *
     * @throws \App\Exception\InvalidEnvironmentException
     */
    public function onEnvironmentStop(EnvironmentStoppedEvent $event): void
    {
        $this->dockerCompose->setActiveEnvironment($event->getEnvironment());
        $environmentVariables = $this->dockerCompose->getRequiredVariables();

        $io = $event->getSymfonyStyle();

        if ($this->mutagen->stopDockerSynchronization($environmentVariables)) {
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
     *
     * @throws \App\Exception\InvalidEnvironmentException
     */
    public function onEnvironmentRestart(EnvironmentRestartedEvent $event): void
    {
        $this->dockerCompose->setActiveEnvironment($event->getEnvironment());
        $environmentVariables = $this->dockerCompose->getRequiredVariables();

        $io = $event->getSymfonyStyle();

        if ($this->mutagen->startDockerSynchronization($environmentVariables)
            && $this->mutagen->stopDockerSynchronization($environmentVariables)
        ) {
            $io->success('Docker synchronization successfully restarted.');
        } else {
            $io->error('An error occurred while restarting the Docker synchronization.');
        }
    }
}
