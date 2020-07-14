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

    public function testItCreatesTheEnvironmentAfterInstall(): void
    {
        [$hosts, $database] = $this->prophesizeEnvironmentSubscriberArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $database->reveal());

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

        [$hosts, $database] = $this->prophesizeEnvironmentSubscriberArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $database->reveal());

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

        [$hosts, $database] = $this->prophesizeEnvironmentSubscriberArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $database->reveal());

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

        [$hosts, $database] = $this->prophesizeEnvironmentSubscriberArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $database->reveal());

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

    public function testItStartsTheEnvironment(): void
    {
        [$hosts, $database] = $this->prophesizeEnvironmentSubscriberArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $environment->activate()->shouldBeCalledOnce();
        $database->save()->shouldBeCalledOnce();

        $event = new EnvironmentStartedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentStart($event);
    }

    public function testItStopsTheEnvironment(): void
    {
        [$hosts, $database] = $this->prophesizeEnvironmentSubscriberArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $environment->deactivate()->shouldBeCalledOnce();
        $database->save()->shouldBeCalledOnce();

        $event = new EnvironmentStoppedEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentStop($event);
    }

    public function testItUninstallsTheEnvironment(): void
    {
        [$hosts, $database] = $this->prophesizeEnvironmentSubscriberArguments();
        $subscriber = new EnvironmentSubscriber($hosts->reveal(), $database->reveal());

        $environment = $this->prophesize(EnvironmentEntity::class);
        $symfonyStyle = $this->prophesize(SymfonyStyle::class);

        $database->remove($environment)->shouldBeCalledOnce();
        $database->save()->shouldBeCalledOnce();

        $event = new EnvironmentUninstalledEvent($environment->reveal(), $symfonyStyle->reveal());
        $subscriber->onEnvironmentUninstall($event);
    }

    /**
     * Prophesizes arguments needed by the \App\Event\EnvironmentUninstalledEvent class.
     */
    private function prophesizeEnvironmentSubscriberArguments(): array
    {
        return [
            $this->prophesize(Hosts::class),
            $this->prophesize(Database::class),
        ];
    }
}
