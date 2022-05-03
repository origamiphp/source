<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Event\EnvironmentInstalledEvent;
use App\Event\EnvironmentStartedEvent;
use App\Event\EnvironmentStoppedEvent;
use App\Event\EnvironmentUninstalledEvent;
use App\EventSubscriber\EnvironmentSubscriber;
use App\Service\ApplicationData;
use App\Service\Middleware\Binary\Docker;
use App\Service\Middleware\Binary\Mutagen;
use App\Service\Wrapper\OrigamiStyle;
use App\ValueObject\EnvironmentEntity;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 *
 * @covers \App\Event\AbstractEnvironmentEvent
 * @covers \App\Event\EnvironmentStartedEvent
 * @covers \App\Event\EnvironmentStoppedEvent
 * @covers \App\Event\EnvironmentUninstalledEvent
 * @covers \App\EventSubscriber\EnvironmentSubscriber
 */
final class EnvironmentSubscriberTest extends TestCase
{
    use ProphecyTrait;

    public function testItCreatesTheEnvironmentAfterInstall(): void
    {
        $docker = $this->prophesize(Docker::class);
        $mutagen = $this->prophesize(Mutagen::class);
        $database = $this->prophesize(ApplicationData::class);

        $environment = $this->prophesize(EnvironmentEntity::class);
        $io = $this->prophesize(OrigamiStyle::class);

        $database
            ->add($environment->reveal())
            ->shouldBeCalledOnce()
        ;

        $database
            ->save()
            ->shouldBeCalledOnce()
        ;

        $subscriber = new EnvironmentSubscriber($docker->reveal(), $mutagen->reveal(), $database->reveal());
        $subscriber->onEnvironmentInstall(new EnvironmentInstalledEvent($environment->reveal(), $io->reveal()));
    }

    public function testItCreatesTheEnvironmentAfterInstallEvenWithAnException(): void
    {
        $docker = $this->prophesize(Docker::class);
        $mutagen = $this->prophesize(Mutagen::class);
        $database = $this->prophesize(ApplicationData::class);

        $environment = $this->prophesize(EnvironmentEntity::class);
        $io = $this->prophesize(OrigamiStyle::class);

        $database
            ->add($environment->reveal())
            ->shouldBeCalledOnce()
        ;

        $database
            ->save()
            ->shouldBeCalledOnce()
        ;

        $subscriber = new EnvironmentSubscriber($docker->reveal(), $mutagen->reveal(), $database->reveal());
        $subscriber->onEnvironmentInstall(new EnvironmentInstalledEvent($environment->reveal(), $io->reveal()));
    }

    public function testItStartsTheEnvironmentSuccessfully(): void
    {
        $docker = $this->prophesize(Docker::class);
        $mutagen = $this->prophesize(Mutagen::class);
        $database = $this->prophesize(ApplicationData::class);

        $environment = $this->prophesize(EnvironmentEntity::class);
        $io = $this->prophesize(OrigamiStyle::class);

        $docker
            ->fixPermissionsOnSharedSSHAgent()
            ->shouldBeCalledOnce()
        ;

        $mutagen
            ->startDockerSynchronization()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $environment
            ->activate()
            ->shouldBeCalledOnce()
        ;

        $database
            ->save()
            ->shouldBeCalledOnce()
        ;

        $subscriber = new EnvironmentSubscriber($docker->reveal(), $mutagen->reveal(), $database->reveal());
        $subscriber->onEnvironmentStart(new EnvironmentStartedEvent($environment->reveal(), $io->reveal()));
    }

    public function testItStartsTheEnvironmentWithOnSharedSSHAgent(): void
    {
        $docker = $this->prophesize(Docker::class);
        $mutagen = $this->prophesize(Mutagen::class);
        $database = $this->prophesize(ApplicationData::class);

        $environment = $this->prophesize(EnvironmentEntity::class);
        $io = $this->prophesize(OrigamiStyle::class);

        $docker
            ->fixPermissionsOnSharedSSHAgent()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $io
            ->error(Argument::type('string'))
            ->shouldBeCalledOnce()
        ;

        $mutagen
            ->startDockerSynchronization()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $environment
            ->activate()
            ->shouldBeCalledOnce()
        ;

        $database
            ->save()
            ->shouldBeCalledOnce()
        ;

        $subscriber = new EnvironmentSubscriber($docker->reveal(), $mutagen->reveal(), $database->reveal());
        $subscriber->onEnvironmentStart(new EnvironmentStartedEvent($environment->reveal(), $io->reveal()));
    }

    public function testItStartsTheEnvironmentWithAnErrorWithMutagen(): void
    {
        $docker = $this->prophesize(Docker::class);
        $mutagen = $this->prophesize(Mutagen::class);
        $database = $this->prophesize(ApplicationData::class);

        $environment = $this->prophesize(EnvironmentEntity::class);
        $io = $this->prophesize(OrigamiStyle::class);

        $docker
            ->fixPermissionsOnSharedSSHAgent()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $mutagen
            ->startDockerSynchronization()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $io
            ->error(Argument::type('string'))
            ->shouldBeCalledOnce()
        ;

        $environment
            ->activate()
            ->shouldBeCalledOnce()
        ;

        $database
            ->save()
            ->shouldBeCalledOnce()
        ;

        $subscriber = new EnvironmentSubscriber($docker->reveal(), $mutagen->reveal(), $database->reveal());
        $subscriber->onEnvironmentStart(new EnvironmentStartedEvent($environment->reveal(), $io->reveal()));
    }

    public function testItStopsTheEnvironmentSuccessfully(): void
    {
        $docker = $this->prophesize(Docker::class);
        $mutagen = $this->prophesize(Mutagen::class);
        $database = $this->prophesize(ApplicationData::class);

        $environment = $this->prophesize(EnvironmentEntity::class);
        $io = $this->prophesize(OrigamiStyle::class);

        $mutagen
            ->removeDockerSynchronization()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $environment
            ->deactivate()
            ->shouldBeCalledOnce()
        ;

        $database
            ->save()
            ->shouldBeCalledOnce()
        ;

        $subscriber = new EnvironmentSubscriber($docker->reveal(), $mutagen->reveal(), $database->reveal());
        $subscriber->onEnvironmentStop(new EnvironmentStoppedEvent($environment->reveal(), $io->reveal()));
    }

    public function testItStopsTheEnvironmentWithAnErrorOnMutagen(): void
    {
        $docker = $this->prophesize(Docker::class);
        $mutagen = $this->prophesize(Mutagen::class);
        $database = $this->prophesize(ApplicationData::class);

        $environment = $this->prophesize(EnvironmentEntity::class);
        $io = $this->prophesize(OrigamiStyle::class);

        $mutagen
            ->removeDockerSynchronization()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $io
            ->error(Argument::type('string'))
            ->shouldBeCalledOnce()
        ;

        $environment
            ->deactivate()
            ->shouldBeCalledOnce()
        ;

        $database
            ->save()
            ->shouldBeCalledOnce()
        ;

        $subscriber = new EnvironmentSubscriber($docker->reveal(), $mutagen->reveal(), $database->reveal());
        $subscriber->onEnvironmentStop(new EnvironmentStoppedEvent($environment->reveal(), $io->reveal()));
    }

    public function testItUninstallsTheEnvironmentSuccessfully(): void
    {
        $docker = $this->prophesize(Docker::class);
        $mutagen = $this->prophesize(Mutagen::class);
        $database = $this->prophesize(ApplicationData::class);

        $environment = $this->prophesize(EnvironmentEntity::class);
        $io = $this->prophesize(OrigamiStyle::class);

        $mutagen
            ->removeDockerSynchronization()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $database
            ->remove($environment)
            ->shouldBeCalledOnce()
        ;

        $database
            ->save()
            ->shouldBeCalledOnce()
        ;

        $subscriber = new EnvironmentSubscriber($docker->reveal(), $mutagen->reveal(), $database->reveal());
        $subscriber->onEnvironmentUninstall(new EnvironmentUninstalledEvent($environment->reveal(), $io->reveal()));
    }

    public function testItUninstallsTheEnvironmentWithAnErrorOnMutagen(): void
    {
        $docker = $this->prophesize(Docker::class);
        $mutagen = $this->prophesize(Mutagen::class);
        $database = $this->prophesize(ApplicationData::class);

        $environment = $this->prophesize(EnvironmentEntity::class);
        $io = $this->prophesize(OrigamiStyle::class);

        $mutagen
            ->removeDockerSynchronization()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $io
            ->error(Argument::type('string'))
            ->shouldBeCalledOnce()
        ;

        $database
            ->remove($environment)
            ->shouldBeCalledOnce()
        ;

        $database
            ->save()
            ->shouldBeCalledOnce()
        ;

        $subscriber = new EnvironmentSubscriber($docker->reveal(), $mutagen->reveal(), $database->reveal());
        $subscriber->onEnvironmentUninstall(new EnvironmentUninstalledEvent($environment->reveal(), $io->reveal()));
    }
}
