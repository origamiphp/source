<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Event\EnvironmentInstalledEvent;
use App\Event\EnvironmentRestartedEvent;
use App\Event\EnvironmentStartedEvent;
use App\Event\EnvironmentStoppedEvent;
use App\Event\EnvironmentUninstalledEvent;
use App\EventSubscriber\EnvironmentSubscriber;
use App\Exception\UnsupportedOperatingSystemException;
use App\Service\Middleware\Binary\Docker;
use App\Service\Middleware\Binary\Mutagen;
use App\Service\Middleware\Database;
use App\Service\Middleware\Hosts;
use App\ValueObject\EnvironmentEntity;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
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
final class EnvironmentSubscriberTest extends TestCase
{
    use ProphecyTrait;

    public function testItCreatesTheEnvironmentAfterInstall(): void
    {
        $hosts = $this->prophesize(Hosts::class);
        $docker = $this->prophesize(Docker::class);
        $mutagen = $this->prophesize(Mutagen::class);
        $database = $this->prophesize(Database::class);

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $database
            ->add($environment->reveal())
            ->shouldBeCalledOnce()
        ;

        $database
            ->save()
            ->shouldBeCalledOnce()
        ;

        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());
        $subscriber->onEnvironmentInstall(new EnvironmentInstalledEvent($environment->reveal(), $symfonyStyle->reveal()));
    }

    public function testItCreatesTheEnvironmentAfterInstallEvenWithAnException(): void
    {
        $hosts = $this->prophesize(Hosts::class);
        $docker = $this->prophesize(Docker::class);
        $mutagen = $this->prophesize(Mutagen::class);
        $database = $this->prophesize(Database::class);

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);
        $domains = 'mydomain.test';

        $environment
            ->getDomains()
            ->shouldBeCalledOnce()
            ->willReturn($domains)
        ;

        $hosts
            ->hasDomains($domains)
            ->shouldBeCalledOnce()
            ->willThrow(UnsupportedOperatingSystemException::class)
        ;

        $hosts
            ->fixHostsFile($domains)
            ->shouldNotBeCalled()
        ;

        $database
            ->add($environment->reveal())
            ->shouldBeCalledOnce()
        ;

        $database
            ->save()
            ->shouldBeCalledOnce()
        ;

        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());
        $subscriber->onEnvironmentInstall(new EnvironmentInstalledEvent($environment->reveal(), $symfonyStyle->reveal()));
    }

    public function testItAnalyzesAndFixesSystemHostsFile(): void
    {
        $hosts = $this->prophesize(Hosts::class);
        $docker = $this->prophesize(Docker::class);
        $mutagen = $this->prophesize(Mutagen::class);
        $database = $this->prophesize(Database::class);

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);
        $domains = 'mydomain.test';

        $environment
            ->getDomains()
            ->shouldBeCalledOnce()
            ->willReturn($domains)
        ;

        $hosts
            ->hasDomains($domains)
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $symfonyStyle
            ->warning(Argument::type('string'))
            ->shouldBeCalledOnce()
        ;

        $symfonyStyle
            ->confirm(Argument::type('string'), false)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $hosts
            ->fixHostsFile($domains)
            ->shouldBeCalledOnce()
        ;

        $database
            ->add($environment->reveal())
            ->shouldBeCalledOnce()
        ;

        $database
            ->save()
            ->shouldBeCalledOnce()
        ;

        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());
        $subscriber->onEnvironmentInstall(new EnvironmentInstalledEvent($environment->reveal(), $symfonyStyle->reveal()));
    }

    public function testItAnalyzesAndDoesNotFixSystemHostsFile(): void
    {
        $hosts = $this->prophesize(Hosts::class);
        $docker = $this->prophesize(Docker::class);
        $mutagen = $this->prophesize(Mutagen::class);
        $database = $this->prophesize(Database::class);

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);
        $domains = 'mydomain.test';

        $environment
            ->getDomains()
            ->shouldBeCalledOnce()
            ->willReturn($domains)
        ;

        $hosts
            ->hasDomains($domains)
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $symfonyStyle
            ->warning(Argument::type('string'))
            ->shouldBeCalledOnce()
        ;

        $symfonyStyle
            ->confirm(Argument::type('string'), false)
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $hosts
            ->fixHostsFile($domains)
            ->shouldNotBeCalled()
        ;

        $database
            ->add($environment->reveal())
            ->shouldBeCalledOnce()
        ;

        $database
            ->save()
            ->shouldBeCalledOnce()
        ;

        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());
        $subscriber->onEnvironmentInstall(new EnvironmentInstalledEvent($environment->reveal(), $symfonyStyle->reveal()));
    }

    public function testItStartsTheEnvironmentSuccessfully(): void
    {
        $hosts = $this->prophesize(Hosts::class);
        $docker = $this->prophesize(Docker::class);
        $mutagen = $this->prophesize(Mutagen::class);
        $database = $this->prophesize(Database::class);

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

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

        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());
        $subscriber->onEnvironmentStart(new EnvironmentStartedEvent($environment->reveal(), $symfonyStyle->reveal()));
    }

    public function testItStartsTheEnvironmentWithOnSharedSSHAgent(): void
    {
        $hosts = $this->prophesize(Hosts::class);
        $docker = $this->prophesize(Docker::class);
        $mutagen = $this->prophesize(Mutagen::class);
        $database = $this->prophesize(Database::class);

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $docker
            ->fixPermissionsOnSharedSSHAgent()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $symfonyStyle
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

        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());
        $subscriber->onEnvironmentStart(new EnvironmentStartedEvent($environment->reveal(), $symfonyStyle->reveal()));
    }

    public function testItStartsTheEnvironmentWithAnErrorWithMutagen(): void
    {
        $hosts = $this->prophesize(Hosts::class);
        $docker = $this->prophesize(Docker::class);
        $mutagen = $this->prophesize(Mutagen::class);
        $database = $this->prophesize(Database::class);

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

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

        $symfonyStyle
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

        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());
        $subscriber->onEnvironmentStart(new EnvironmentStartedEvent($environment->reveal(), $symfonyStyle->reveal()));
    }

    public function testItStopsTheEnvironmentSuccessfully(): void
    {
        $hosts = $this->prophesize(Hosts::class);
        $docker = $this->prophesize(Docker::class);
        $mutagen = $this->prophesize(Mutagen::class);
        $database = $this->prophesize(Database::class);

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $mutagen
            ->stopDockerSynchronization()
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

        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());
        $subscriber->onEnvironmentStop(new EnvironmentStoppedEvent($environment->reveal(), $symfonyStyle->reveal()));
    }

    public function testItStopsTheEnvironmentWithAnErrorOnMutagen(): void
    {
        $hosts = $this->prophesize(Hosts::class);
        $docker = $this->prophesize(Docker::class);
        $mutagen = $this->prophesize(Mutagen::class);
        $database = $this->prophesize(Database::class);

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $mutagen
            ->stopDockerSynchronization()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $symfonyStyle
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

        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());
        $subscriber->onEnvironmentStop(new EnvironmentStoppedEvent($environment->reveal(), $symfonyStyle->reveal()));
    }

    public function testItRestartsTheEnvironmentSuccessfully(): void
    {
        $hosts = $this->prophesize(Hosts::class);
        $docker = $this->prophesize(Docker::class);
        $mutagen = $this->prophesize(Mutagen::class);
        $database = $this->prophesize(Database::class);

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $mutagen
            ->stopDockerSynchronization()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $mutagen
            ->startDockerSynchronization()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());
        $subscriber->onEnvironmentRestart(new EnvironmentRestartedEvent($environment->reveal(), $symfonyStyle->reveal()));
    }

    public function testItRestartsTheEnvironmentWithAnErrorOnMutagenStopping(): void
    {
        $hosts = $this->prophesize(Hosts::class);
        $docker = $this->prophesize(Docker::class);
        $mutagen = $this->prophesize(Mutagen::class);
        $database = $this->prophesize(Database::class);

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $mutagen
            ->stopDockerSynchronization()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $mutagen
            ->startDockerSynchronization()
            ->shouldNotBeCalled()
        ;

        $symfonyStyle
            ->error(Argument::type('string'))
            ->shouldBeCalledOnce()
        ;

        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());
        $subscriber->onEnvironmentRestart(new EnvironmentRestartedEvent($environment->reveal(), $symfonyStyle->reveal()));
    }

    public function testItRestartsTheEnvironmentWithAnErrorOnMutagenStarting(): void
    {
        $hosts = $this->prophesize(Hosts::class);
        $docker = $this->prophesize(Docker::class);
        $mutagen = $this->prophesize(Mutagen::class);
        $database = $this->prophesize(Database::class);

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $mutagen
            ->stopDockerSynchronization()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $mutagen
            ->startDockerSynchronization()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $symfonyStyle
            ->error(Argument::type('string'))
            ->shouldBeCalledOnce()
        ;

        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());
        $subscriber->onEnvironmentRestart(new EnvironmentRestartedEvent($environment->reveal(), $symfonyStyle->reveal()));
    }

    public function testItUninstallsTheEnvironmentSuccessfully(): void
    {
        $hosts = $this->prophesize(Hosts::class);
        $docker = $this->prophesize(Docker::class);
        $mutagen = $this->prophesize(Mutagen::class);
        $database = $this->prophesize(Database::class);

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

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

        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());
        $subscriber->onEnvironmentUninstall(new EnvironmentUninstalledEvent($environment->reveal(), $symfonyStyle->reveal()));
    }

    public function testItUninstallsTheEnvironmentWithAnErrorOnMutagen(): void
    {
        $hosts = $this->prophesize(Hosts::class);
        $docker = $this->prophesize(Docker::class);
        $mutagen = $this->prophesize(Mutagen::class);
        $database = $this->prophesize(Database::class);

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $mutagen
            ->removeDockerSynchronization()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $symfonyStyle
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

        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $docker->reveal(), $mutagen->reveal(), $database->reveal());
        $subscriber->onEnvironmentUninstall(new EnvironmentUninstalledEvent($environment->reveal(), $symfonyStyle->reveal()));
    }
}
