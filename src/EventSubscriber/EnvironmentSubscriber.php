<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\EnvironmentInstalledEvent;
use App\Event\EnvironmentStartedEvent;
use App\Event\EnvironmentStoppedEvent;
use App\Event\EnvironmentUninstalledEvent;
use App\Exception\OrigamiExceptionInterface;
use App\Middleware\Database;
use App\Middleware\Hosts;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EnvironmentSubscriber implements EventSubscriberInterface
{
    private Hosts $hosts;
    private Database $database;

    public function __construct(Hosts $hosts, Database $database)
    {
        $this->hosts = $hosts;
        $this->database = $database;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     *
     * @uses \App\EventSubscriber\EnvironmentSubscriber::onEnvironmentInstall
     * @uses \App\EventSubscriber\EnvironmentSubscriber::onEnvironmentStart
     * @uses \App\EventSubscriber\EnvironmentSubscriber::onEnvironmentStop
     * @uses \App\EventSubscriber\EnvironmentSubscriber::onEnvironmentUninstall
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
     */
    public function onEnvironmentStart(EnvironmentStartedEvent $event): void
    {
        $environment = $event->getEnvironment();
        $environment->activate();

        $this->database->save();
    }

    /**
     * Listener which triggers the Docker synchronization stop.
     */
    public function onEnvironmentStop(EnvironmentStoppedEvent $event): void
    {
        $environment = $event->getEnvironment();
        $environment->deactivate();

        $this->database->save();
    }

    /**
     * Listener which triggers the Docker synchronization removing and environment unregistration.
     */
    public function onEnvironmentUninstall(EnvironmentUninstalledEvent $event): void
    {
        $environment = $event->getEnvironment();

        $this->database->remove($environment);
        $this->database->save();
    }
}
