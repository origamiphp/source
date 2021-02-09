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
use App\Middleware\Binary\DockerCompose;
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
        [$hosts, $dockerCompose, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $dockerCompose->reveal(), $mutagen->reveal(), $database->reveal());

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

        [$hosts, $dockerCompose, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $dockerCompose->reveal(), $mutagen->reveal(), $database->reveal());

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

        [$hosts, $dockerCompose, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $dockerCompose->reveal(), $mutagen->reveal(), $database->reveal());

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

        [$hosts, $dockerCompose, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $dockerCompose->reveal(), $mutagen->reveal(), $database->reveal());

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
        [$hosts, $dockerCompose, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $dockerCompose->reveal(), $mutagen->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $dockerCompose->fixPermissionsOnSharedSSHAgent()->shouldBeCalledOnce();
        $dockerCompose->getRequiredVariables($environment)->shouldBeCalledOnce()->willReturn([]);
        $mutagen->startDockerSynchronization([])->shouldBeCalledOnce()->willReturn(true);

        $environment->activate()->shouldBeCalledOnce();
        $database->save()->shouldBeCalledOnce();

        $event = new EnvironmentStartedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentStart($event);
    }

    public function testItStartsTheEnvironmentWithOnSharedSSHAgent(): void
    {
        [$hosts, $dockerCompose, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $dockerCompose->reveal(), $mutagen->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $dockerCompose->fixPermissionsOnSharedSSHAgent()->shouldBeCalledOnce()->willReturn(false);
        $symfonyStyle->error(Argument::type('string'))->shouldBeCalledOnce();
        $dockerCompose->getRequiredVariables($environment)->shouldBeCalledOnce()->willReturn([]);
        $mutagen->startDockerSynchronization([])->shouldBeCalledOnce()->willReturn(true);

        $environment->activate()->shouldBeCalledOnce();
        $database->save()->shouldBeCalledOnce();

        $event = new EnvironmentStartedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentStart($event);
    }

    public function testItStartsTheEnvironmentWithAnErrorWithMutagen(): void
    {
        [$hosts, $dockerCompose, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $dockerCompose->reveal(), $mutagen->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $dockerCompose->fixPermissionsOnSharedSSHAgent()->shouldBeCalledOnce()->willReturn(true);
        $dockerCompose->getRequiredVariables($environment)->shouldBeCalledOnce()->willReturn([]);
        $mutagen->startDockerSynchronization([])->shouldBeCalledOnce()->willReturn(false);
        $symfonyStyle->error(Argument::type('string'))->shouldBeCalledOnce();

        $environment->activate()->shouldBeCalledOnce();
        $database->save()->shouldBeCalledOnce();

        $event = new EnvironmentStartedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentStart($event);
    }

    public function testItStopsTheEnvironmentSuccessfully(): void
    {
        [$hosts, $dockerCompose, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $dockerCompose->reveal(), $mutagen->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $dockerCompose->getRequiredVariables($environment)->shouldBeCalledOnce()->willReturn([]);
        $mutagen->stopDockerSynchronization([])->shouldBeCalledOnce()->willReturn(true);

        $environment->deactivate()->shouldBeCalledOnce();
        $database->save()->shouldBeCalledOnce();

        $event = new EnvironmentStoppedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentStop($event);
    }

    public function testItStopsTheEnvironmentWithAnErrorOnMutagen(): void
    {
        [$hosts, $dockerCompose, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $dockerCompose->reveal(), $mutagen->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $dockerCompose->getRequiredVariables($environment)->shouldBeCalledOnce()->willReturn([]);
        $mutagen->stopDockerSynchronization([])->shouldBeCalledOnce()->willReturn(false);
        $symfonyStyle->error(Argument::type('string'))->shouldBeCalledOnce();

        $environment->deactivate()->shouldBeCalledOnce();
        $database->save()->shouldBeCalledOnce();

        $event = new EnvironmentStoppedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentStop($event);
    }

    public function testItRestartsTheEnvironmentSuccessfully(): void
    {
        [$hosts, $dockerCompose, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $dockerCompose->reveal(), $mutagen->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $dockerCompose->getRequiredVariables($environment)->shouldBeCalledOnce()->willReturn([]);
        $mutagen->stopDockerSynchronization([])->shouldBeCalledOnce()->willReturn(true);
        $mutagen->startDockerSynchronization([])->shouldBeCalledOnce()->willReturn(true);

        $event = new EnvironmentRestartedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentRestart($event);
    }

    public function testItRestartsTheEnvironmentWithAnErrorOnMutagenStopping(): void
    {
        [$hosts, $dockerCompose, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $dockerCompose->reveal(), $mutagen->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $dockerCompose->getRequiredVariables($environment)->shouldBeCalledOnce()->willReturn([]);
        $mutagen->stopDockerSynchronization([])->shouldBeCalledOnce()->willReturn(false);
        $mutagen->startDockerSynchronization([])->shouldNotBeCalled();
        $symfonyStyle->error(Argument::type('string'))->shouldBeCalledOnce();

        $event = new EnvironmentRestartedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentRestart($event);
    }

    public function testItRestartsTheEnvironmentWithAnErrorOnMutagenStarting(): void
    {
        [$hosts, $dockerCompose, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $dockerCompose->reveal(), $mutagen->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $dockerCompose->getRequiredVariables($environment)->shouldBeCalledOnce()->willReturn([]);
        $mutagen->stopDockerSynchronization([])->shouldBeCalledOnce()->willReturn(true);
        $mutagen->startDockerSynchronization([])->shouldBeCalledOnce()->willReturn(false);
        $symfonyStyle->error(Argument::type('string'))->shouldBeCalledOnce();

        $event = new EnvironmentRestartedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentRestart($event);
    }

    public function testItUninstallsTheEnvironmentSuccessfully(): void
    {
        [$hosts, $dockerCompose, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $dockerCompose->reveal(), $mutagen->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $dockerCompose->getRequiredVariables($environment)->shouldBeCalledOnce()->willReturn([]);
        $mutagen->removeDockerSynchronization([])->shouldBeCalledOnce()->willReturn(true);

        $database->remove($environment)->shouldBeCalledOnce();
        $database->save()->shouldBeCalledOnce();

        $event = new EnvironmentUninstalledEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentUninstall($event);
    }

    public function testItUninstallsTheEnvironmentWithAnErrorOnMutagen(): void
    {
        [$hosts, $dockerCompose, $mutagen, $database] = $this->prophesizeObjectArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $dockerCompose->reveal(), $mutagen->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $dockerCompose->getRequiredVariables($environment)->shouldBeCalledOnce()->willReturn([]);
        $mutagen->removeDockerSynchronization([])->shouldBeCalledOnce()->willReturn(false);
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
            $this->prophesize(DockerCompose::class),
            $this->prophesize(Mutagen::class),
            $this->prophesize(Database::class),
        ];
    }
}
