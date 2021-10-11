<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\StartCommand;
use App\Service\ApplicationContext;
use App\Service\Middleware\Binary\Docker;
use App\Service\Wrapper\ProcessProxy;
use App\Tests\TestCommandTrait;
use App\Tests\TestEnvironmentTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\StartCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class StartCommandTest extends TestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestEnvironmentTrait;

    public function testItStartsTheEnvironment(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $processProxy = $this->prophesize(ProcessProxy::class);
        $docker = $this->prophesize(Docker::class);
        $eventDispatcher = $this->prophesize(EventDispatcher::class);

        $environment = $this->createEnvironment();

        $applicationContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->shouldBeCalledOnce()
        ;

        $applicationContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $docker
            ->startServices()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $eventDispatcher
            ->dispatch(Argument::any())
            ->shouldBeCalledOnce()
        ;

        $command = new StartCommand($applicationContext->reveal(), $processProxy->reveal(), $docker->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] ', $display);
        static::assertStringContainsString('[INFO] ', $display);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItDoesNotStartMultipleEnvironments(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $processProxy = $this->prophesize(ProcessProxy::class);
        $docker = $this->prophesize(Docker::class);
        $eventDispatcher = $this->prophesize(EventDispatcher::class);

        $environment = $this->createEnvironment();
        $environment->activate();

        $applicationContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->shouldBeCalledOnce()
        ;

        $applicationContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $processProxy
            ->getWorkingDirectory()
            ->willReturn('')
        ;

        $docker
            ->startServices()
            ->shouldNotBeCalled()
        ;

        $eventDispatcher
            ->dispatch(Argument::any())
            ->shouldNotBeCalled()
        ;

        $command = new StartCommand($applicationContext->reveal(), $processProxy->reveal(), $docker->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] ', $display);
        static::assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $processProxy = $this->prophesize(ProcessProxy::class);
        $docker = $this->prophesize(Docker::class);
        $eventDispatcher = $this->prophesize(EventDispatcher::class);

        $environment = $this->createEnvironment();

        $applicationContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->shouldBeCalledOnce()
        ;

        $applicationContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $applicationContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->shouldBeCalledOnce()
        ;

        $applicationContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $processProxy
            ->getWorkingDirectory()
            ->willReturn('')
        ;

        $docker
            ->startServices()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $command = new StartCommand($applicationContext->reveal(), $processProxy->reveal(), $docker->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] ', $display);
        static::assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }
}
