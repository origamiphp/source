<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Environment\EnvironmentEntity;
use App\Environment\EnvironmentMaker\RequirementsChecker;
use App\Event\EnvironmentInstalledEvent;
use App\Event\EnvironmentRestartedEvent;
use App\Event\EnvironmentStartedEvent;
use App\Event\EnvironmentStoppedEvent;
use App\Event\EnvironmentUninstalledEvent;
use App\EventSubscriber\EnvironmentSubscriber;
use App\Exception\InvalidEnvironmentException;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\Binary\Mutagen;
use App\Middleware\Database;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 *
 * @covers \App\Event\AbstractEnvironmentEvent
 * @covers \App\Event\EnvironmentRestartedEvent
 * @covers \App\Event\EnvironmentStartedEvent
 * @covers \App\Event\EnvironmentStoppedEvent
 * @covers \App\Event\EnvironmentUninstalledEvent
 * @covers \App\EventSubscriber\EnvironmentSubscriber
 */
final class EnvironmentSubscriberTest extends WebTestCase
{
    /** @var Prophet */
    private $prophet;

    /** @var ObjectProphecy */
    private $dockerCompose;

    /** @var ObjectProphecy */
    private $mutagen;

    /** @var ObjectProphecy */
    private $database;

    /** @var ObjectProphecy */
    private $requirementsChecker;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prophet = new Prophet();
        $this->dockerCompose = $this->prophet->prophesize(DockerCompose::class);
        $this->mutagen = $this->prophet->prophesize(Mutagen::class);
        $this->database = $this->prophet->prophesize(Database::class);
        $this->requirementsChecker = $this->prophet->prophesize(RequirementsChecker::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->prophet->checkPredictions();
    }

    public function testItCreatesTheEnvironmentAfterInstall(): void
    {
        $environment = $this->prophet->prophesize(EnvironmentEntity::class)->reveal();

        (new MethodProphecy($this->database, 'add', [$environment]))->shouldBeCalledOnce();
        (new MethodProphecy($this->database, 'save', []))->shouldBeCalledOnce();

        $subscriber = new EnvironmentSubscriber(
            $this->dockerCompose->reveal(),
            $this->mutagen->reveal(),
            $this->database->reveal(),
            $this->requirementsChecker->reveal()
        );

        $event = new EnvironmentInstalledEvent(
            $environment,
            $this->prophet->prophesize(SymfonyStyle::class)->reveal()
        );
        $subscriber->onEnvironmentInstall($event);

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItStartsTheDockerSynchronizationWithSuccess(): void
    {
        $environment = $this->prophet->prophesize(EnvironmentEntity::class);
        $this->prophesizeCommonMethods($environment);

        (new MethodProphecy($environment, 'activate', []))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->database, 'save', []))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->dockerCompose, 'fixPermissionsOnSharedSSHAgent', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        (new MethodProphecy($this->dockerCompose, 'fixPermissionsOnSharedSSHAgent', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        (new MethodProphecy($this->mutagen, 'startDockerSynchronization', [[]]))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $subscriber = new EnvironmentSubscriber(
            $this->dockerCompose->reveal(),
            $this->mutagen->reveal(),
            $this->database->reveal(),
            $this->requirementsChecker->reveal()
        );

        $symfonyStyle = $this->prophet->prophesize(SymfonyStyle::class);
        (new MethodProphecy($symfonyStyle, 'success', [Argument::type('string')]))
            ->shouldBeCalledTimes(2)
        ;

        $event = new EnvironmentStartedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentStart($event);

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItStartsTheDockerSynchronizationWithoutSuccess(): void
    {
        $environment = $this->prophet->prophesize(EnvironmentEntity::class);
        $this->prophesizeCommonMethods($environment);

        (new MethodProphecy($environment, 'activate', []))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->database, 'save', []))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->dockerCompose, 'fixPermissionsOnSharedSSHAgent', []))
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        (new MethodProphecy($this->mutagen, 'startDockerSynchronization', [[]]))
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $subscriber = new EnvironmentSubscriber(
            $this->dockerCompose->reveal(),
            $this->mutagen->reveal(),
            $this->database->reveal(),
            $this->requirementsChecker->reveal()
        );

        $symfonyStyle = $this->prophet->prophesize(SymfonyStyle::class);
        (new MethodProphecy($symfonyStyle, 'error', [Argument::type('string')]))
            ->shouldBeCalledTimes(2)
        ;

        $event = new EnvironmentStartedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentStart($event);

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItStopsTheDockerSynchronizationWithSuccess(): void
    {
        $environment = $this->prophet->prophesize(EnvironmentEntity::class);
        $this->prophesizeCommonMethods($environment);

        (new MethodProphecy($environment, 'deactivate', []))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->database, 'save', []))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->mutagen, 'stopDockerSynchronization', [[]]))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $subscriber = new EnvironmentSubscriber(
            $this->dockerCompose->reveal(),
            $this->mutagen->reveal(),
            $this->database->reveal(),
            $this->requirementsChecker->reveal()
        );

        $symfonyStyle = $this->prophet->prophesize(SymfonyStyle::class);
        (new MethodProphecy($symfonyStyle, 'success', [Argument::type('string')]))
            ->shouldBeCalledOnce()
        ;

        $event = new EnvironmentStoppedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentStop($event);

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItStopsTheDockerSynchronizationWithoutSuccess(): void
    {
        $environment = $this->prophet->prophesize(EnvironmentEntity::class);
        $this->prophesizeCommonMethods($environment);

        (new MethodProphecy($environment, 'deactivate', []))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->database, 'save', []))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->mutagen, 'stopDockerSynchronization', [[]]))
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $subscriber = new EnvironmentSubscriber(
            $this->dockerCompose->reveal(),
            $this->mutagen->reveal(),
            $this->database->reveal(),
            $this->requirementsChecker->reveal()
        );

        $symfonyStyle = $this->prophet->prophesize(SymfonyStyle::class);
        (new MethodProphecy($symfonyStyle, 'error', [Argument::type('string')]))
            ->shouldBeCalledOnce()
        ;

        $event = new EnvironmentStoppedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentStop($event);

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItRestartsTheDockerSynchronizationWithSuccess(): void
    {
        $environment = $this->prophet->prophesize(EnvironmentEntity::class);
        $this->prophesizeCommonMethods($environment);

        (new MethodProphecy($this->mutagen, 'startDockerSynchronization', [[]]))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        (new MethodProphecy($this->mutagen, 'stopDockerSynchronization', [[]]))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $subscriber = new EnvironmentSubscriber(
            $this->dockerCompose->reveal(),
            $this->mutagen->reveal(),
            $this->database->reveal(),
            $this->requirementsChecker->reveal()
        );

        $symfonyStyle = $this->prophet->prophesize(SymfonyStyle::class);
        (new MethodProphecy($symfonyStyle, 'success', [Argument::type('string')]))
            ->shouldBeCalledOnce()
        ;

        $event = new EnvironmentRestartedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentRestart($event);

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItRestartsTheDockerSynchronizationWithoutSuccess(): void
    {
        $environment = $this->prophet->prophesize(EnvironmentEntity::class);
        $this->prophesizeCommonMethods($environment);

        (new MethodProphecy($this->mutagen, 'stopDockerSynchronization', [[]]))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        (new MethodProphecy($this->mutagen, 'startDockerSynchronization', [[]]))
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $subscriber = new EnvironmentSubscriber(
            $this->dockerCompose->reveal(),
            $this->mutagen->reveal(),
            $this->database->reveal(),
            $this->requirementsChecker->reveal()
        );

        $symfonyStyle = $this->prophet->prophesize(SymfonyStyle::class);
        (new MethodProphecy($symfonyStyle, 'error', [Argument::type('string')]))
            ->shouldBeCalledOnce()
        ;

        $event = new EnvironmentRestartedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentRestart($event);

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItUninstallsTheDockerSynchronizationWithSuccess(): void
    {
        $environment = $this->prophet->prophesize(EnvironmentEntity::class);
        $this->prophesizeCommonMethods($environment);

        (new MethodProphecy($this->mutagen, 'removeDockerSynchronization', [[]]))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $subscriber = new EnvironmentSubscriber(
            $this->dockerCompose->reveal(),
            $this->mutagen->reveal(),
            $this->database->reveal(),
            $this->requirementsChecker->reveal()
        );

        $symfonyStyle = $this->prophet->prophesize(SymfonyStyle::class);
        (new MethodProphecy($symfonyStyle, 'success', [Argument::type('string')]))
            ->shouldBeCalledOnce()
        ;

        $event = new EnvironmentUninstalledEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentUninstall($event);

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItUninstallsTheDockerSynchronizationWithoutSuccess(): void
    {
        $environment = $this->prophet->prophesize(EnvironmentEntity::class);
        $this->prophesizeCommonMethods($environment);

        (new MethodProphecy($this->mutagen, 'removeDockerSynchronization', [[]]))
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $subscriber = new EnvironmentSubscriber(
            $this->dockerCompose->reveal(),
            $this->mutagen->reveal(),
            $this->database->reveal(),
            $this->requirementsChecker->reveal()
        );

        $symfonyStyle = $this->prophet->prophesize(SymfonyStyle::class);
        (new MethodProphecy($symfonyStyle, 'error', [Argument::type('string')]))
            ->shouldBeCalledOnce()
        ;

        $event = new EnvironmentUninstalledEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentUninstall($event);

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    /**
     * Prophesizes common methods for tests above.
     */
    private function prophesizeCommonMethods(ObjectProphecy $environment): void
    {
        (new MethodProphecy($environment, 'getType', []))
            ->shouldBeCalledOnce()
            ->willReturn(EnvironmentEntity::TYPE_SYMFONY)
        ;

        (new MethodProphecy($this->dockerCompose, 'setActiveEnvironment', [$environment->reveal()]))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->dockerCompose, 'getRequiredVariables', [$environment->reveal()]))
            ->shouldBeCalledOnce()
            ->willReturn([])
        ;

        (new MethodProphecy($this->requirementsChecker, 'canOptimizeSynchronizationPerformance', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;
    }
}
