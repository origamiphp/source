<?php

declare(strict_types=1);

namespace App\Tests\Command\Main;

use App\Command\Main\UninstallCommand;
use App\Helper\CommandExitCode;
use App\Tests\AbstractCommandWebTestCase;
use App\Tests\TestFakeEnvironmentTrait;
use Prophecy\Argument;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Main\UninstallCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class UninstallCommandTest extends AbstractCommandWebTestCase
{
    use TestFakeEnvironmentTrait;

    public function testItUninstallsTheCurrentEnvironment(): void
    {
        $environment = $this->getFakeEnvironment();
        $environment->setActive(false);

        $this->database->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $this->dockerCompose->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $this->dockerCompose->removeServices()->shouldBeCalledOnce()->willReturn(true);
        $this->eventDispatcher->dispatch(Argument::any())->shouldBeCalledOnce();
        $this->systemManager->uninstall($environment)->shouldBeCalledOnce();
        $this->database->remove($environment)->shouldBeCalledOnce();
        $this->database->save()->shouldBeCalledOnce();

        $commandTester = new CommandTester($this->getCommand(UninstallCommand::class));
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Environment successfully uninstalled.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItDoesNotUninstallARunningEnvironment(): void
    {
        $environment = $this->getFakeEnvironment();
        $environment->setActive(true);

        $this->database->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $this->dockerCompose->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $this->dockerCompose->removeServices()->shouldNotBeCalled();
        $this->eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();
        $this->systemManager->uninstall($environment)->shouldNotBeCalled();
        $this->database->remove($environment)->shouldNotBeCalled();
        $this->database->save()->shouldNotBeCalled();

        $commandTester = new CommandTester($this->getCommand(UninstallCommand::class));
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Unable to uninstall a running environment.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $environment = $this->getFakeEnvironment();

        $this->database->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $this->dockerCompose->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $this->dockerCompose->removeServices()->shouldBeCalledOnce()->willReturn(false);
        $this->eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();
        $this->systemManager->uninstall($environment)->shouldNotBeCalled();
        $this->database->remove($environment)->shouldNotBeCalled();
        $this->database->save()->shouldNotBeCalled();

        $commandTester = new CommandTester($this->getCommand(UninstallCommand::class));
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] An error occurred while removing the Docker services.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }
}
