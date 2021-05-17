<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\StopCommand;
use App\Helper\CurrentContext;
use App\Middleware\Binary\Docker;
use App\Tests\TestCommandTrait;
use App\Tests\TestEnvironmentTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use stdClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\StopCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class StopCommandTest extends TestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestEnvironmentTrait;

    public function testItExecutesProcessSuccessfully(): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $docker = $this->prophesize(Docker::class);
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $environment = $this->createEnvironment();

        $currentContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->shouldBeCalledOnce()
        ;

        $currentContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $docker
            ->stopServices()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $eventDispatcher
            ->dispatch(Argument::any())
            ->willReturn(new stdClass())
        ;

        $command = new StopCommand($currentContext->reveal(), $docker->reveal(), $eventDispatcher->reveal());
        static::assertResultIsSuccessful($command, $environment);
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $docker = $this->prophesize(Docker::class);
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $environment = $this->createEnvironment();

        $currentContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->shouldBeCalledOnce()
        ;

        $currentContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $docker
            ->stopServices()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $command = new StopCommand($currentContext->reveal(), $docker->reveal(), $eventDispatcher->reveal());
        static::assertExceptionIsHandled($command);
    }
}
