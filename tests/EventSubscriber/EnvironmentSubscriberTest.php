<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Environment\EnvironmentEntity;
use App\Event\EnvironmentInstalledEvent;
use App\Event\EnvironmentRestartedEvent;
use App\Event\EnvironmentStartedEvent;
use App\Event\EnvironmentStoppedEvent;
use App\Event\EnvironmentUninstalledEvent;
use App\EventSubscriber\EnvironmentSubscriber;
use App\Exception\UnsupportedOperatingSystemException;
use App\Middleware\Binary\Docker;
use App\Middleware\Binary\Mutagen;
use App\Middleware\Database;
use App\Middleware\Hosts;
use App\Tests\CustomProphecyTrait;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 *
 * @covers \App\Event\AbstractEnvironmentEvent
 * @covers \App\Event\EnvironmentStartedEvent
 * @covers \App\Event\EnvironmentStoppedEvent
 * @covers \App\Event\EnvironmentUninstalledEvent
 * @covers \App\EventSubscriber\EnvironmentSubscriber
 */
final class EnvironmentSubscriberTest extends WebTestCase
{
    use CustomProphecyTrait;

    public function testItCreatesTheEnvironmentAfterInstall(): void
    {
        [$hosts, $docker, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $database->add($environment->reveal())->shouldBeCalledOnce();
        $database->save()->shouldBeCalledOnce();

        $event = new EnvironmentInstalledEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentInstall($event);
    }

    public function testItCreatesTheEnvironmentAfterInstallEvenWithAnException(): void
    {
        $domains = 'test.localhost';

        [$hosts, $docker, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $environment->getDomains()->shouldBeCalledOnce()->willReturn($domains);
        $hosts->hasDomains($domains)->shouldBeCalledOnce()->willThrow(UnsupportedOperatingSystemException::class);
        $hosts->fixHostsFile($domains)->shouldNotBeCalled();

        $database->add($environment->reveal())->shouldBeCalledOnce();
        $database->save()->shouldBeCalledOnce();

        $event = new EnvironmentInstalledEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentInstall($event);
    }

    public function testItAnalyzesAndFixesSystemHostsFile(): void
    {
        $domains = 'test.localhost';

        [$hosts, $docker, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $environment->getDomains()->shouldBeCalledOnce()->willReturn($domains);
        $hosts->hasDomains($domains)->shouldBeCalledOnce()->willReturn(false);
        $symfonyStyle->warning(Argument::type('string'))->shouldBeCalledOnce();
        $symfonyStyle->confirm(Argument::type('string'), false)->shouldBeCalledOnce()->willReturn(true);
        $hosts->fixHostsFile($domains)->shouldBeCalledOnce();

        $database->add($environment->reveal())->shouldBeCalledOnce();
        $database->save()->shouldBeCalledOnce();

        $event = new EnvironmentInstalledEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentInstall($event);
    }

    public function testItAnalyzesAndDoesNotFixSystemHostsFile(): void
    {
        $domains = 'test.localhost';

        [$hosts, $docker, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $environment->getDomains()->shouldBeCalledOnce()->willReturn($domains);
        $hosts->hasDomains($domains)->shouldBeCalledOnce()->willReturn(false);
        $symfonyStyle->warning(Argument::type('string'))->shouldBeCalledOnce();
        $symfonyStyle->confirm(Argument::type('string'), false)->shouldBeCalledOnce()->willReturn(false);
        $hosts->fixHostsFile($domains)->shouldNotBeCalled();

        $database->add($environment->reveal())->shouldBeCalledOnce();
        $database->save()->shouldBeCalledOnce();

        $event = new EnvironmentInstalledEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentInstall($event);
    }

    public function testItStartsTheEnvironmentSuccessfully(): void
    {
        [$hosts, $docker, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $docker->fixPermissionsOnSharedSSHAgent()->shouldBeCalledOnce();
        $mutagen->startDockerSynchronization()->shouldBeCalledOnce()->willReturn(true);

        $environment->activate()->shouldBeCalledOnce();
        $database->save()->shouldBeCalledOnce();

        $event = new EnvironmentStartedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentStart($event);
    }

    public function testItStartsTheEnvironmentWithOnSharedSSHAgent(): void
    {
        [$hosts, $docker, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $docker->fixPermissionsOnSharedSSHAgent()->shouldBeCalledOnce()->willReturn(false);
        $symfonyStyle->error(Argument::type('string'))->shouldBeCalledOnce();
        $mutagen->startDockerSynchronization()->shouldBeCalledOnce()->willReturn(true);

        $environment->activate()->shouldBeCalledOnce();
        $database->save()->shouldBeCalledOnce();

        $event = new EnvironmentStartedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentStart($event);
    }

    public function testItStartsTheEnvironmentWithAnErrorWithMutagen(): void
    {
        [$hosts, $docker, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $docker->fixPermissionsOnSharedSSHAgent()->shouldBeCalledOnce()->willReturn(true);
        $mutagen->startDockerSynchronization()->shouldBeCalledOnce()->willReturn(false);
        $symfonyStyle->error(Argument::type('string'))->shouldBeCalledOnce();

        $environment->activate()->shouldBeCalledOnce();
        $database->save()->shouldBeCalledOnce();

        $event = new EnvironmentStartedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentStart($event);
    }

    public function testItStopsTheEnvironmentSuccessfully(): void
    {
        [$hosts, $docker, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $mutagen->stopDockerSynchronization()->shouldBeCalledOnce()->willReturn(true);

        $environment->deactivate()->shouldBeCalledOnce();
        $database->save()->shouldBeCalledOnce();

        $event = new EnvironmentStoppedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentStop($event);
    }

    public function testItStopsTheEnvironmentWithAnErrorOnMutagen(): void
    {
        [$hosts, $docker, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $mutagen->stopDockerSynchronization()->shouldBeCalledOnce()->willReturn(false);
        $symfonyStyle->error(Argument::type('string'))->shouldBeCalledOnce();

        $environment->deactivate()->shouldBeCalledOnce();
        $database->save()->shouldBeCalledOnce();

        $event = new EnvironmentStoppedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentStop($event);
    }

    public function testItRestartsTheEnvironmentSuccessfully(): void
    {
        [$hosts, $docker, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $mutagen->stopDockerSynchronization()->shouldBeCalledOnce()->willReturn(true);
        $mutagen->startDockerSynchronization()->shouldBeCalledOnce()->willReturn(true);

        $event = new EnvironmentRestartedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentRestart($event);
    }

    public function testItRestartsTheEnvironmentWithAnErrorOnMutagenStopping(): void
    {
        [$hosts, $docker, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $mutagen->stopDockerSynchronization()->shouldBeCalledOnce()->willReturn(false);
        $mutagen->startDockerSynchronization()->shouldNotBeCalled();
        $symfonyStyle->error(Argument::type('string'))->shouldBeCalledOnce();

        $event = new EnvironmentRestartedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentRestart($event);
    }

    public function testItRestartsTheEnvironmentWithAnErrorOnMutagenStarting(): void
    {
        [$hosts, $docker, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $mutagen->stopDockerSynchronization()->shouldBeCalledOnce()->willReturn(true);
        $mutagen->startDockerSynchronization()->shouldBeCalledOnce()->willReturn(false);
        $symfonyStyle->error(Argument::type('string'))->shouldBeCalledOnce();

        $event = new EnvironmentRestartedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentRestart($event);
    }

    public function testItUninstallsTheEnvironmentSuccessfully(): void
    {
        [$hosts, $docker, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $mutagen->removeDockerSynchronization()->shouldBeCalledOnce()->willReturn(true);

        $database->remove($environment)->shouldBeCalledOnce();
        $database->save()->shouldBeCalledOnce();

        $event = new EnvironmentUninstalledEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentUninstall($event);
    }

    public function testItUninstallsTheEnvironmentWithAnErrorOnMutagen(): void
    {
        [$hosts, $docker, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $mutagen->removeDockerSynchronization()->shouldBeCalledOnce()->willReturn(false);
        $symfonyStyle->error(Argument::type('string'))->shouldBeCalledOnce();

        $database->remove($environment)->shouldBeCalledOnce();
        $database->save()->shouldBeCalledOnce();

        $event = new EnvironmentUninstalledEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentUninstall($event);
    }

    /**
     * {@inheritdoc}
     */
    protected function prophesizeObjectArguments(): array
    {
        return [
            $this->prophesize(Hosts::class),
            $this->prophesize(Docker::class),
            $this->prophesize(Mutagen::class),
            $this->prophesize(Database::class),
        ];
    }
}
