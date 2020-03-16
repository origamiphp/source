<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Entity\Environment;
use App\Event\EnvironmentRestartedEvent;
use App\Event\EnvironmentStartedEvent;
use App\Event\EnvironmentStoppedEvent;
use App\Event\EnvironmentUninstallEvent;
use App\EventSubscriber\EnvironmentSubscriber;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\Binary\Mutagen;
use Doctrine\ORM\EntityManagerInterface;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 *
 * @covers \App\Event\AbstractEnvironmentEvent
 * @covers \App\Event\EnvironmentRestartedEvent
 * @covers \App\Event\EnvironmentStartedEvent
 * @covers \App\Event\EnvironmentStoppedEvent
 * @covers \App\Event\EnvironmentUninstallEvent
 * @covers \App\EventSubscriber\EnvironmentSubscriber
 */
final class EnvironmentSubscriberTest extends WebTestCase
{
    /** @var DockerCompose|ObjectProphecy */
    private $dockerCompose;

    /** @var Mutagen|ObjectProphecy */
    private $mutagen;

    /** @var EntityManagerInterface|ObjectProphecy */
    private $entityManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dockerCompose = $this->prophesize(DockerCompose::class);
        $this->mutagen = $this->prophesize(Mutagen::class);
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
    }

    public function testItStartsTheDockerSynchronizationWithSuccess(): void
    {
        $environment = $this->prophesize(Environment::class);
        $environment->setActive(true)->shouldBeCalledOnce();
        $environment->getType()->shouldBeCalledOnce()->willReturn(Environment::TYPE_SYMFONY);
        $this->entityManager->flush()->shouldBeCalledOnce();

        $this->dockerCompose->setActiveEnvironment($environment->reveal())->shouldBeCalledOnce();
        $this->dockerCompose->getRequiredVariables()->shouldBeCalledOnce()->willReturn([]);

        $this->mutagen->startDockerSynchronization([])->shouldBeCalledOnce()->willReturn(true);

        $subscriber = new EnvironmentSubscriber(
            $this->dockerCompose->reveal(),
            $this->mutagen->reveal(),
            $this->entityManager->reveal()
        );

        $symfonyStyle = $this->prophesize(SymfonyStyle::class);
        $symfonyStyle->success('Docker synchronization successfully started.')->shouldBeCalledOnce();
        $event = new EnvironmentStartedEvent($environment->reveal(), $symfonyStyle->reveal());

        $subscriber->onEnvironmentStart($event);
    }

    public function testItStartsTheDockerSynchronizationWithoutSuccess(): void
    {
        $environment = $this->prophesize(Environment::class);
        $environment->setActive(true)->shouldBeCalledOnce();
        $environment->getType()->shouldBeCalledOnce()->willReturn(Environment::TYPE_SYMFONY);
        $this->entityManager->flush()->shouldBeCalledOnce();

        $this->dockerCompose->setActiveEnvironment($environment->reveal())->shouldBeCalledOnce();
        $this->dockerCompose->getRequiredVariables()->shouldBeCalledOnce()->willReturn([]);

        $this->mutagen->startDockerSynchronization([])->shouldBeCalledOnce()->willReturn(false);

        $subscriber = new EnvironmentSubscriber(
            $this->dockerCompose->reveal(),
            $this->mutagen->reveal(),
            $this->entityManager->reveal()
        );

        $symfonyStyle = $this->prophesize(SymfonyStyle::class);
        $symfonyStyle->error('An error occurred while starting the Docker synchronization.')->shouldBeCalledOnce();
        $event = new EnvironmentStartedEvent($environment->reveal(), $symfonyStyle->reveal());

        $subscriber->onEnvironmentStart($event);
    }

    public function testItStopsTheDockerSynchronizationWithSuccess(): void
    {
        $environment = $this->prophesize(Environment::class);
        $environment->setActive(false)->shouldBeCalledOnce();
        $environment->getType()->shouldBeCalledOnce()->willReturn(Environment::TYPE_SYMFONY);
        $this->entityManager->flush()->shouldBeCalledOnce();

        $this->dockerCompose->setActiveEnvironment($environment->reveal())->shouldBeCalledOnce();
        $this->dockerCompose->getRequiredVariables()->shouldBeCalledOnce()->willReturn([]);

        $this->mutagen->stopDockerSynchronization([])->shouldBeCalledOnce()->willReturn(true);

        $subscriber = new EnvironmentSubscriber(
            $this->dockerCompose->reveal(),
            $this->mutagen->reveal(),
            $this->entityManager->reveal()
        );

        $symfonyStyle = $this->prophesize(SymfonyStyle::class);
        $symfonyStyle->success('Docker synchronization successfully stopped.')->shouldBeCalledOnce();
        $event = new EnvironmentStoppedEvent($environment->reveal(), $symfonyStyle->reveal());

        $subscriber->onEnvironmentStop($event);
    }

    public function testItStopsTheDockerSynchronizationWithoutSuccess(): void
    {
        $environment = $this->prophesize(Environment::class);
        $environment->setActive(false)->shouldBeCalledOnce();
        $environment->getType()->shouldBeCalledOnce()->willReturn(Environment::TYPE_SYMFONY);
        $this->entityManager->flush()->shouldBeCalledOnce();

        $this->dockerCompose->setActiveEnvironment($environment->reveal())->shouldBeCalledOnce();
        $this->dockerCompose->getRequiredVariables()->shouldBeCalledOnce()->willReturn([]);

        $this->mutagen->stopDockerSynchronization([])->shouldBeCalledOnce()->willReturn(false);

        $subscriber = new EnvironmentSubscriber(
            $this->dockerCompose->reveal(),
            $this->mutagen->reveal(),
            $this->entityManager->reveal()
        );

        $symfonyStyle = $this->prophesize(SymfonyStyle::class);
        $symfonyStyle->error('An error occurred while stopping the Docker synchronization.')->shouldBeCalledOnce();
        $event = new EnvironmentStoppedEvent($environment->reveal(), $symfonyStyle->reveal());

        $subscriber->onEnvironmentStop($event);
    }

    public function testItRestartsTheDockerSynchronizationWithSuccess(): void
    {
        $environment = $this->prophesize(Environment::class);
        $environment->getType()->shouldBeCalledOnce()->willReturn(Environment::TYPE_SYMFONY);

        $this->dockerCompose->setActiveEnvironment($environment->reveal())->shouldBeCalledOnce();
        $this->dockerCompose->getRequiredVariables()->shouldBeCalledOnce()->willReturn([]);

        $this->mutagen->startDockerSynchronization([])->shouldBeCalledOnce()->willReturn(true);
        $this->mutagen->stopDockerSynchronization([])->shouldBeCalledOnce()->willReturn(true);

        $subscriber = new EnvironmentSubscriber(
            $this->dockerCompose->reveal(),
            $this->mutagen->reveal(),
            $this->entityManager->reveal()
        );

        $symfonyStyle = $this->prophesize(SymfonyStyle::class);
        $symfonyStyle->success('Docker synchronization successfully restarted.')->shouldBeCalledOnce();
        $event = new EnvironmentRestartedEvent($environment->reveal(), $symfonyStyle->reveal());

        $subscriber->onEnvironmentRestart($event);
    }

    public function testItRestartsTheDockerSynchronizationWithoutSuccess(): void
    {
        $environment = $this->prophesize(Environment::class);
        $environment->getType()->shouldBeCalledOnce()->willReturn(Environment::TYPE_SYMFONY);

        $this->dockerCompose->setActiveEnvironment($environment->reveal())->shouldBeCalledOnce();
        $this->dockerCompose->getRequiredVariables()->shouldBeCalledOnce()->willReturn([]);

        $this->mutagen->stopDockerSynchronization([])->shouldBeCalledOnce()->willReturn(true);
        $this->mutagen->startDockerSynchronization([])->shouldBeCalledOnce()->willReturn(false);

        $subscriber = new EnvironmentSubscriber(
            $this->dockerCompose->reveal(),
            $this->mutagen->reveal(),
            $this->entityManager->reveal()
        );

        $symfonyStyle = $this->prophesize(SymfonyStyle::class);
        $symfonyStyle->error('An error occurred while restarting the Docker synchronization.')->shouldBeCalledOnce();
        $event = new EnvironmentRestartedEvent($environment->reveal(), $symfonyStyle->reveal());

        $subscriber->onEnvironmentRestart($event);
    }

    public function testItUninstallsTheDockerSynchronizationWithSuccess(): void
    {
        $environment = $this->prophesize(Environment::class);
        $environment->getType()->shouldBeCalledOnce()->willReturn(Environment::TYPE_SYMFONY);

        $this->dockerCompose->setActiveEnvironment($environment->reveal())->shouldBeCalledOnce();
        $this->dockerCompose->getRequiredVariables()->shouldBeCalledOnce()->willReturn([]);

        $this->mutagen->removeDockerSynchronization([])->shouldBeCalledOnce()->willReturn(true);

        $subscriber = new EnvironmentSubscriber(
            $this->dockerCompose->reveal(),
            $this->mutagen->reveal(),
            $this->entityManager->reveal()
        );

        $symfonyStyle = $this->prophesize(SymfonyStyle::class);
        $symfonyStyle->success('Docker synchronization successfully removed.')->shouldBeCalledOnce();
        $event = new EnvironmentUninstallEvent($environment->reveal(), $symfonyStyle->reveal());

        $subscriber->onEnvironmentUninstall($event);
    }

    public function testItUninstallsTheDockerSynchronizationWithoutSuccess(): void
    {
        $environment = $this->prophesize(Environment::class);
        $environment->getType()->shouldBeCalledOnce()->willReturn(Environment::TYPE_SYMFONY);

        $this->dockerCompose->setActiveEnvironment($environment->reveal())->shouldBeCalledOnce();
        $this->dockerCompose->getRequiredVariables()->shouldBeCalledOnce()->willReturn([]);

        $this->mutagen->removeDockerSynchronization([])->shouldBeCalledOnce()->willReturn(false);

        $subscriber = new EnvironmentSubscriber(
            $this->dockerCompose->reveal(),
            $this->mutagen->reveal(),
            $this->entityManager->reveal()
        );

        $symfonyStyle = $this->prophesize(SymfonyStyle::class);
        $symfonyStyle->error('An error occurred while removing the Docker synchronization.')->shouldBeCalledOnce();
        $event = new EnvironmentUninstallEvent($environment->reveal(), $symfonyStyle->reveal());

        $subscriber->onEnvironmentUninstall($event);
    }
}
