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
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
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
    use ProphecyTrait;

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

        $this->hosts = $this->prophesize(Hosts::class);
        $this->database = $this->prophesize(Database::class);
    }

    public function testItCreatesTheEnvironmentAfterInstall(): void
    {
        $subscriber = $this->getEnvironmentSubscriberInstance();

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $this->database->add($environment->reveal())->shouldBeCalledOnce();
        $this->database->save()->shouldBeCalledOnce();

        $event = new EnvironmentInstalledEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentInstall($event);

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    /**
     * @throws UnsupportedOperatingSystemException
     */
    public function testItCreatesTheEnvironmentAfterInstallEvenWithAnException(): void
    {
        $subscriber = $this->getEnvironmentSubscriberInstance();
        $domains = 'test.localhost';
        $exception = new UnsupportedOperatingSystemException('Dummy exception.');

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $environment->getDomains()->shouldBeCalledOnce()->willReturn($domains);
        $this->hosts->hasDomains($domains)->shouldBeCalledOnce()->willThrow($exception);
        $this->hosts->fixHostsFile($domains)->shouldNotBeCalled();

        $this->database->add($environment->reveal())->shouldBeCalledOnce();
        $this->database->save()->shouldBeCalledOnce();

        $event = new EnvironmentInstalledEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentInstall($event);

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    /**
     * @throws UnsupportedOperatingSystemException
     */
    public function testItAnalyzesAndFixesSystemHostsFile(): void
    {
        $subscriber = $this->getEnvironmentSubscriberInstance();
        $domains = 'test.localhost';

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $environment->getDomains()->shouldBeCalledOnce()->willReturn($domains);
        $this->hosts->hasDomains($domains)->shouldBeCalledOnce()->willReturn(false);
        $symfonyStyle->warning(Argument::type('string'))->shouldBeCalledOnce();
        $symfonyStyle->confirm(Argument::type('string'), false)->shouldBeCalledOnce()->willReturn(true);
        $this->hosts->fixHostsFile($domains)->shouldBeCalledOnce();

        $this->database->add($environment->reveal())->shouldBeCalledOnce();
        $this->database->save()->shouldBeCalledOnce();

        $event = new EnvironmentInstalledEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentInstall($event);

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    /**
     * @throws UnsupportedOperatingSystemException
     */
    public function testItAnalyzesAndDoesNotFixSystemHostsFile(): void
    {
        $subscriber = $this->getEnvironmentSubscriberInstance();
        $domains = 'test.localhost';

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $environment->getDomains()->shouldBeCalledOnce()->willReturn($domains);
        $this->hosts->hasDomains($domains)->shouldBeCalledOnce()->willReturn(false);
        $symfonyStyle->warning(Argument::type('string'))->shouldBeCalledOnce();
        $symfonyStyle->confirm(Argument::type('string'), false)->shouldBeCalledOnce()->willReturn(false);
        $this->hosts->fixHostsFile($domains)->shouldNotBeCalled();

        $this->database->add($environment->reveal())->shouldBeCalledOnce();
        $this->database->save()->shouldBeCalledOnce();

        $event = new EnvironmentInstalledEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentInstall($event);

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    public function testItStartsTheEnvironment(): void
    {
        $subscriber = $this->getEnvironmentSubscriberInstance();

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $environment->activate()->shouldBeCalledOnce();
        $this->database->save()->shouldBeCalledOnce();

        $event = new EnvironmentStartedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentStart($event);

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    public function testItStopsTheEnvironment(): void
    {
        $subscriber = $this->getEnvironmentSubscriberInstance();

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $environment->deactivate()->shouldBeCalledOnce();
        $this->database->save()->shouldBeCalledOnce();

        $event = new EnvironmentStoppedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentStop($event);

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    public function testItUninstallsTheEnvironment(): void
    {
        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);
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
