<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\PhpCommand;
use App\Exception\InvalidEnvironmentException;
use App\Service\CurrentContext;
use App\Service\Middleware\Binary\Docker;
use App\Tests\TestCommandTrait;
use App\Tests\TestEnvironmentTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\PhpCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class PhpCommandTest extends TestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestEnvironmentTrait;

    public function testItOpensTerminalOnService(): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $docker = $this->prophesize(Docker::class);

        $currentContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->shouldBeCalledOnce()
        ;

        $currentContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($this->createEnvironment())
        ;

        $docker
            ->openTerminal(Argument::type('string'), Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $command = new PhpCommand($currentContext->reveal(), $docker->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $docker = $this->prophesize(Docker::class);

        $currentContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->willThrow(InvalidEnvironmentException::class)
        ;

        $currentContext
            ->getActiveEnvironment()
            ->shouldNotBeCalled()
        ;

        $command = new PhpCommand($currentContext->reveal(), $docker->reveal());
        self::assertExceptionIsHandled($command);
    }
}
