<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\PrepareCommand;
use App\Helper\CurrentContext;
use App\Middleware\Binary\Docker;
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
 * @covers \App\Command\PrepareCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class PrepareCommandTest extends TestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestEnvironmentTrait;

    public function testItPreparesTheActiveEnvironment(): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $docker = $this->prophesize(Docker::class);

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
            ->pullServices()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $docker
            ->buildServices()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $command = new PrepareCommand($currentContext->reveal(), $docker->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $display = $commandTester->getDisplay();
        static::assertDisplayIsVerbose($environment, $display);
        static::assertStringContainsString('[OK] ', $display);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $docker = $this->prophesize(Docker::class);

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
            ->pullServices()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $docker
            ->buildServices()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $command = new PrepareCommand($currentContext->reveal(), $docker->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] ', $display);
        static::assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }
}
