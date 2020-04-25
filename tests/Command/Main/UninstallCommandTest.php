<?php

declare(strict_types=1);

namespace App\Tests\Command\Main;

use App\Command\Main\UninstallCommand;
use App\Helper\CommandExitCode;
use App\Tests\Command\AbstractCommandWebTestCase;
use App\Tests\TestFakeEnvironmentTrait;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
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

        (new MethodProphecy($this->database, 'getActiveEnvironment', []))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        (new MethodProphecy($this->dockerCompose, 'setActiveEnvironment', [$environment]))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->dockerCompose, 'removeServices', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        (new MethodProphecy($this->eventDispatcher, 'dispatch', [Argument::any()]))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->systemManager, 'uninstall', [$environment]))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->database, 'remove', [$environment]))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->database, 'save', []))
            ->shouldBeCalledOnce()
        ;

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

        (new MethodProphecy($this->database, 'getActiveEnvironment', []))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        (new MethodProphecy($this->dockerCompose, 'setActiveEnvironment', [$environment]))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->dockerCompose, 'removeServices', []))
            ->shouldNotBeCalled()
        ;

        (new MethodProphecy($this->eventDispatcher, 'dispatch', [Argument::any()]))
            ->shouldNotBeCalled()
        ;

        (new MethodProphecy($this->systemManager, 'uninstall', [$environment]))
            ->shouldNotBeCalled()
        ;

        (new MethodProphecy($this->database, 'remove', [$environment]))
            ->shouldNotBeCalled()
        ;

        (new MethodProphecy($this->database, 'save', []))
            ->shouldNotBeCalled()
        ;

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

        (new MethodProphecy($this->database, 'getActiveEnvironment', []))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        (new MethodProphecy($this->dockerCompose, 'setActiveEnvironment', [$environment]))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->dockerCompose, 'removeServices', []))
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        (new MethodProphecy($this->eventDispatcher, 'dispatch', [Argument::any()]))
            ->shouldNotBeCalled()
        ;

        (new MethodProphecy($this->systemManager, 'uninstall', [$environment]))
            ->shouldNotBeCalled()
        ;

        (new MethodProphecy($this->database, 'remove', [$environment]))
            ->shouldNotBeCalled()
        ;

        (new MethodProphecy($this->database, 'save', []))
            ->shouldNotBeCalled()
        ;

        $commandTester = new CommandTester($this->getCommand(UninstallCommand::class));
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] An error occurred while removing the Docker services.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }
}
