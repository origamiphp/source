<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Environment\EnvironmentEntity;
use App\Environment\EnvironmentMaker\RequirementsChecker;
use App\Event\EnvironmentInstalledEvent;
use App\Event\EnvironmentRestartedEvent;
use App\Event\EnvironmentStartedEvent;
use App\Event\EnvironmentStoppedEvent;
use App\Event\EnvironmentUninstalledEvent;
use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\Binary\Mutagen;
use App\Middleware\Database;
use App\Middleware\Hosts;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EnvironmentSubscriber implements EventSubscriberInterface
{
    /** @var Hosts */
    private $hosts;

    /** @var DockerCompose */
    private $dockerCompose;

    /** @var Mutagen */
    private $mutagen;

    /** @var Database */
    private $database;

    /** @var RequirementsChecker */
    private $requirementsChecker;

    public function __construct(
        Hosts $hosts,
        DockerCompose $dockerCompose,
        Mutagen $mutagen,
        Database $database,
        RequirementsChecker $requirementsChecker
    ) {
        $this->hosts = $hosts;
        $this->dockerCompose = $dockerCompose;
        $this->mutagen = $mutagen;
        $this->database = $database;
        $this->requirementsChecker = $requirementsChecker;
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
     * Listener which triggers the check on custom domains and the environment registration.
     */
    public function onEnvironmentInstall(EnvironmentInstalledEvent $event): void
    {
        $environment = $event->getEnvironment();
        $io = $event->getSymfonyStyle();

        try {
            if (($domains = $environment->getDomains()) && !$this->hosts->hasDomains($domains)) {
                $io->warning(sprintf('Your hosts file do not contain the "127.0.0.1 %s" entry.', $domains));
                if ($io->confirm('Do you want to automatically update it? Your password may be asked by the system, but you can also do it yourself afterwards.', false)) {
                    $this->hosts->fixHostsFile($domains);
                }
            }
        } catch (OrigamiExceptionInterface $exception) {
            $io->error('Unable to check whether the custom domains are defined in your hosts file.');
        }

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

            if ($this->requirementsChecker->canOptimizeSynchronizationPerformance()
                && $this->mutagen->startDockerSynchronization($environmentVariables)
            ) {
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

            if ($this->requirementsChecker->canOptimizeSynchronizationPerformance()
                && $this->mutagen->stopDockerSynchronization($environmentVariables)
            ) {
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

            if ($this->requirementsChecker->canOptimizeSynchronizationPerformance()
                && $this->mutagen->stopDockerSynchronization($environmentVariables)
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

            if ($this->requirementsChecker->canOptimizeSynchronizationPerformance()
                && $this->mutagen->removeDockerSynchronization($environmentVariables)
            ) {
                $io->success('Docker synchronization successfully removed.');
            } else {
                $io->error('An error occurred while removing the Docker synchronization.');
            }
        }

        $this->database->remove($environment);
        $this->database->save();
    }
}
