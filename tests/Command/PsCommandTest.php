<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\PsCommand;
use App\Exception\InvalidEnvironmentException;
use App\Service\ApplicationContext;
use App\Service\Middleware\Binary\Docker;
use App\Tests\TestCommandTrait;
use App\Tests\TestEnvironmentTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\PsCommand
 */
final class PsCommandTest extends TestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestEnvironmentTrait;

    public function testItExecutesProcessSuccessfully(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $docker = $this->prophesize(Docker::class);

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
            ->showServicesStatus()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $command = new PsCommand($applicationContext->reveal(), $docker->reveal());
        static::assertResultIsSuccessful($command, $environment);
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $docker = $this->prophesize(Docker::class);

        $applicationContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->willThrow(InvalidEnvironmentException::class)
        ;

        $applicationContext
            ->getActiveEnvironment()
            ->shouldNotBeCalled()
        ;

        $command = new PsCommand($applicationContext->reveal(), $docker->reveal());
        static::assertExceptionIsHandled($command);
    }
}
