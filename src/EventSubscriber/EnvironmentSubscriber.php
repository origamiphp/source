<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\EnvironmentInstalledEvent;
use App\Event\EnvironmentRestartedEvent;
use App\Event\EnvironmentStartedEvent;
use App\Event\EnvironmentStoppedEvent;
use App\Event\EnvironmentUninstalledEvent;
use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Service\ApplicationData;
use App\Service\Middleware\Binary\Docker;
use App\Service\Middleware\Binary\Mutagen;
use App\Service\Middleware\Hosts;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EnvironmentSubscriber implements EventSubscriberInterface
{
    private Hosts $hosts;
    private Docker $docker;
    private Mutagen $mutagen;
    private ApplicationData $applicationData;

    public function __construct(Hosts $hosts, Docker $docker, Mutagen $mutagen, ApplicationData $applicationData)
    {
        $this->hosts = $hosts;
        $this->docker = $docker;
        $this->mutagen = $mutagen;
        $this->applicationData = $applicationData;
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
     *
     * @return string[]
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
     *
     * @throws InvalidEnvironmentException
     */
    public function onEnvironmentInstall(EnvironmentInstalledEvent $event): void
    {
        $environment = $event->getEnvironment();
        $io = $event->getConsoleStyle();

        try {
            if (($domains = $environment->getDomains()) && !$this->hosts->hasDomains($domains)) {
                $message = sprintf('Your hosts file do not contain the "127.0.0.1 %s" entry.', $domains);
                $io->warning($message);

                if ($io->confirm('Do you want to automatically update it? Your password may be asked by the system, but you can also do it yourself afterwards.', false)) {
                    $this->hosts->fixHostsFile($domains);
                }
            }
        } catch (OrigamiExceptionInterface $exception) {
            $io->error('Unable to check whether the custom domains are defined in your hosts file.');
        }

        $this->applicationData->add($environment);
        $this->applicationData->save();
    }

    /**
     * Listener which triggers the Docker synchronization start.
     */
    public function onEnvironmentStart(EnvironmentStartedEvent $event): void
    {
        $environment = $event->getEnvironment();
        $io = $event->getConsoleStyle();

        if (!$this->docker->fixPermissionsOnSharedSSHAgent()) {
            $io->error('An error occurred while trying to fix the permissions on the shared SSH agent.');
        }

        if (!$this->mutagen->startDockerSynchronization()) {
            $io->error('An error occurred while starting the Docker synchronization.');
        }

        $environment->activate();
        $this->applicationData->save();
    }

    /**
     * Listener which triggers the Docker synchronization stop.
     */
    public function onEnvironmentStop(EnvironmentStoppedEvent $event): void
    {
        $environment = $event->getEnvironment();
        $io = $event->getConsoleStyle();

        if (!$this->mutagen->stopDockerSynchronization()) {
            $io->error('An error occurred while stopping the Docker synchronization.');
        }

        $environment->deactivate();
        $this->applicationData->save();
    }

    /**
     * Listener which triggers the Docker synchronization restart.
     */
    public function onEnvironmentRestart(EnvironmentRestartedEvent $event): void
    {
        $io = $event->getConsoleStyle();

        if (!$this->mutagen->stopDockerSynchronization() || !$this->mutagen->startDockerSynchronization()) {
            $io->error('An error occurred while restarting the Docker synchronization.');
        }
    }

    /**
     * Listener which triggers the Docker synchronization removing and environment unregistration.
     */
    public function onEnvironmentUninstall(EnvironmentUninstalledEvent $event): void
    {
        $environment = $event->getEnvironment();
        $io = $event->getConsoleStyle();

        if (!$this->mutagen->removeDockerSynchronization()) {
            $io->error('An error occurred while removing the Docker synchronization.');
        }

        $this->applicationData->remove($environment);
        $this->applicationData->save();
    }
}
