<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\EnvironmentInstalledEvent;
use App\Event\EnvironmentStartedEvent;
use App\Event\EnvironmentStoppedEvent;
use App\Event\EnvironmentUninstalledEvent;
use App\Exception\InvalidEnvironmentException;
use App\Service\ApplicationData;
use App\Service\Middleware\Binary\Docker;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EnvironmentSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Docker $docker,
        private ApplicationData $applicationData
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     *
     * @uses \App\EventSubscriber\EnvironmentSubscriber::onEnvironmentStart
     * @uses \App\EventSubscriber\EnvironmentSubscriber::onEnvironmentStop
     * @uses \App\EventSubscriber\EnvironmentSubscriber::onEnvironmentUninstall
     *
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            EnvironmentInstalledEvent::class => 'onEnvironmentInstall',
            EnvironmentStartedEvent::class => 'onEnvironmentStart',
            EnvironmentStoppedEvent::class => 'onEnvironmentStop',
            EnvironmentUninstalledEvent::class => 'onEnvironmentUninstall',
        ];
    }

    /**
     * Listener which triggers the environment registration.
     *
     * @throws InvalidEnvironmentException
     */
    public function onEnvironmentInstall(EnvironmentInstalledEvent $event): void
    {
        $environment = $event->getEnvironment();

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

        $environment->activate();
        $this->applicationData->save();
    }

    /**
     * Listener which triggers the Docker synchronization removing.
     */
    public function onEnvironmentStop(EnvironmentStoppedEvent $event): void
    {
        $environment = $event->getEnvironment();

        $environment->deactivate();
        $this->applicationData->save();
    }

    /**
     * Listener which triggers the Docker synchronization removing and environment unregistration.
     */
    public function onEnvironmentUninstall(EnvironmentUninstalledEvent $event): void
    {
        $environment = $event->getEnvironment();

        $this->applicationData->remove($environment);
        $this->applicationData->save();
    }
}
