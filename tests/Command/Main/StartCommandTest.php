<?php

declare(strict_types=1);

namespace App\Tests\Command\Main;

use App\Command\Main\StartCommand;
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
 * @covers \App\Command\Main\StartCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class StartCommandTest extends AbstractCommandWebTestCase
{
    use TestFakeEnvironmentTrait;

    public function testItStartsTheEnvironment(): void
    {
        $environment = $this->getFakeEnvironment();

        (new MethodProphecy($this->database, 'getActiveEnvironment', []))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        (new MethodProphecy($this->dockerCompose, 'setActiveEnvironment', [$environment]))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->dockerCompose, 'startServices', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        (new MethodProphecy($this->eventDispatcher, 'dispatch', [Argument::any()]))
            ->shouldBeCalledOnce()
        ;

        $commandTester = new CommandTester($this->getCommand(StartCommand::class));
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Docker services successfully started.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItDoesNotStartMultipleEnvironments(): void
    {
        $environment = $this->getFakeEnvironment();
        $environment->setActive(true);

        (new MethodProphecy($this->database, 'getActiveEnvironment', []))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        (new MethodProphecy($this->processProxy, 'getWorkingDirectory', []))
            ->shouldBeCalledOnce()
            ->willReturn('')
        ;

        (new MethodProphecy($this->dockerCompose, 'setActiveEnvironment', [$environment]))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->dockerCompose, 'startServices', []))
            ->shouldNotBeCalled()
        ;

        (new MethodProphecy($this->eventDispatcher, 'dispatch', [Argument::any()]))
            ->shouldNotBeCalled()
        ;

        $commandTester = new CommandTester($this->getCommand(StartCommand::class));
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Unable to start an environment when there is already a running one.', $display);
        static::assertSame(CommandExitCode::INVALID, $commandTester->getStatusCode());
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

        (new MethodProphecy($this->dockerCompose, 'startServices', []))
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        (new MethodProphecy($this->eventDispatcher, 'dispatch', [Argument::any()]))
            ->shouldNotBeCalled()
        ;

        $commandTester = new CommandTester($this->getCommand(StartCommand::class));
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] An error occurred while starting the Docker services.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }
}
