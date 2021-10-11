<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\RestartCommand;
use App\Service\ApplicationContext;
use App\Service\Middleware\Binary\Docker;
use App\Tests\TestCommandTrait;
use App\Tests\TestEnvironmentTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\RestartCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class RestartCommandTest extends TestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestEnvironmentTrait;

    public function testItExecutesProcessSuccessfully(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $docker = $this->prophesize(Docker::class);
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

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
            ->restartServices()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $eventDispatcher
            ->dispatch(Argument::any())
            ->shouldBeCalledOnce()
        ;

        $command = new RestartCommand($applicationContext->reveal(), $docker->reveal(), $eventDispatcher->reveal());
        static::assertResultIsSuccessful($command, $environment);
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $docker = $this->prophesize(Docker::class);
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

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
            ->restartServices()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $command = new RestartCommand($applicationContext->reveal(), $docker->reveal(), $eventDispatcher->reveal());
        static::assertExceptionIsHandled($command);
    }
}
