<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Environment\EnvironmentEntity;
use App\Event\EnvironmentInstalledEvent;
use App\Event\EnvironmentStartedEvent;
use App\Event\EnvironmentStoppedEvent;
use App\Event\EnvironmentUninstalledEvent;
use App\EventSubscriber\EnvironmentSubscriber;
use App\Exception\UnsupportedOperatingSystemException;
use App\Middleware\Database;
use App\Middleware\Hosts;
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
    private $hosts;

    /** @var ObjectProphecy */
    private $database;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prophet = new Prophet();
        $this->hosts = $this->prophet->prophesize(Hosts::class);
        $this->database = $this->prophet->prophesize(Database::class);
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
        $environment = $this->prophet->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophet->prophesize(SymfonyStyle::class);
        $subscriber = $this->getEnvironmentSubscriberInstance();

        (new MethodProphecy($this->database, 'add', [$environment->reveal()]))->shouldBeCalledOnce();
        (new MethodProphecy($this->database, 'save', []))->shouldBeCalledOnce();

        $event = new EnvironmentInstalledEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentInstall($event);

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    public function testItCreatesTheEnvironmentAfterInstallEvenWithAnException(): void
    {
        $environment = $this->prophet->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophet->prophesize(SymfonyStyle::class);
        $subscriber = $this->getEnvironmentSubscriberInstance();

        (new MethodProphecy($environment, 'getDomains', []))->shouldBeCalledOnce()->willReturn('test.localhost');
        (new MethodProphecy($this->hosts, 'hasDomains', ['test.localhost']))
            ->shouldBeCalledOnce()
            ->willThrow(new UnsupportedOperatingSystemException('Dummy exception.'))
        ;
        (new MethodProphecy($this->hosts, 'fixHostsFile', ['test.localhost']))->shouldNotBeCalled();

        (new MethodProphecy($this->database, 'add', [$environment->reveal()]))->shouldBeCalledOnce();
        (new MethodProphecy($this->database, 'save', []))->shouldBeCalledOnce();

        $event = new EnvironmentInstalledEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentInstall($event);

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    public function testItAnalyzesAndFixesSystemHostsFile(): void
    {
        $environment = $this->prophet->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophet->prophesize(SymfonyStyle::class);
        $subscriber = $this->getEnvironmentSubscriberInstance();

        (new MethodProphecy($environment, 'getDomains', []))->shouldBeCalledOnce()->willReturn('test.localhost');
        (new MethodProphecy($this->hosts, 'hasDomains', ['test.localhost']))->shouldBeCalledOnce()->willReturn(false);
        (new MethodProphecy($symfonyStyle, 'warning', [Argument::type('string')]))->shouldBeCalledOnce();
        (new MethodProphecy($symfonyStyle, 'confirm', [Argument::type('string'), false]))->shouldBeCalledOnce()->willReturn(true);
        (new MethodProphecy($this->hosts, 'fixHostsFile', ['test.localhost']))->shouldBeCalledOnce();

        (new MethodProphecy($this->database, 'add', [$environment->reveal()]))->shouldBeCalledOnce();
        (new MethodProphecy($this->database, 'save', []))->shouldBeCalledOnce();

        $event = new EnvironmentInstalledEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentInstall($event);

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    public function testItAnalyzesAndDoesNotFixSystemHostsFile(): void
    {
        $environment = $this->prophet->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophet->prophesize(SymfonyStyle::class);
        $subscriber = $this->getEnvironmentSubscriberInstance();

        (new MethodProphecy($environment, 'getDomains', []))->shouldBeCalledOnce()->willReturn('test.localhost');
        (new MethodProphecy($this->hosts, 'hasDomains', ['test.localhost']))->shouldBeCalledOnce()->willReturn(false);
        (new MethodProphecy($symfonyStyle, 'warning', [Argument::type('string')]))->shouldBeCalledOnce();
        (new MethodProphecy($symfonyStyle, 'confirm', [Argument::type('string'), false]))->shouldBeCalledOnce()->willReturn(false);
        (new MethodProphecy($this->hosts, 'fixHostsFile', ['test.localhost']))->shouldNotBeCalled();

        (new MethodProphecy($this->database, 'add', [$environment->reveal()]))->shouldBeCalledOnce();
        (new MethodProphecy($this->database, 'save', []))->shouldBeCalledOnce();

        $event = new EnvironmentInstalledEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentInstall($event);

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    public function testItStartsTheEnvironment(): void
    {
        $environment = $this->prophet->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophet->prophesize(SymfonyStyle::class);
        $subscriber = $this->getEnvironmentSubscriberInstance();

        (new MethodProphecy($environment, 'activate', []))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->database, 'save', []))
            ->shouldBeCalledOnce()
        ;

        $event = new EnvironmentStartedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentStart($event);

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    public function testItStopsTheEnvironment(): void
    {
        $environment = $this->prophet->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophet->prophesize(SymfonyStyle::class);
        $subscriber = $this->getEnvironmentSubscriberInstance();

        (new MethodProphecy($environment, 'deactivate', []))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->database, 'save', []))
            ->shouldBeCalledOnce()
        ;

        $event = new EnvironmentStoppedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentStop($event);

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    public function testItUninstallsTheEnvironment(): void
    {
        $environment = $this->prophet->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophet->prophesize(SymfonyStyle::class);
        $subscriber = $this->getEnvironmentSubscriberInstance();

        $event = new EnvironmentUninstalledEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentUninstall($event);

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    /**
     * Retrieves the "EnvironmentSubscriber" instance used within the tests.
     */
    private function getEnvironmentSubscriberInstance(): EnvironmentSubscriber
    {
        return new EnvironmentSubscriber($this->hosts->reveal(), $this->database->reveal());
    }
}
