<?php

declare(strict_types=1);

namespace App\Tests\Command\Database;

use App\Command\Database\ResetCommand;
use App\Service\ApplicationContext;
use App\Service\Middleware\Binary\Docker;
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
 * @covers \App\Command\Database\ResetCommand
 */
final class ResetCommandTest extends TestCase
{
    use ProphecyTrait;
    use TestEnvironmentTrait;

    public function testItResetsTheDatabaseVolume(): void
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
            ->removeDatabaseService()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $docker
            ->removeDatabaseVolume()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $docker
            ->startServices()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $command = new ResetCommand($applicationContext->reveal(), $docker->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] ', $display);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItAbortsGracefullyTheReset(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $docker = $this->prophesize(Docker::class);

        $command = new ResetCommand($applicationContext->reveal(), $docker->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringNotContainsString('[OK] ', $display);
        static::assertStringNotContainsString('[ERROR] ', $display);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurredWhileRemoving(): void
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
            ->removeDatabaseService()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $command = new ResetCommand($applicationContext->reveal(), $docker->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] ', $display);
        static::assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurredWhileStarting(): void
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
            ->removeDatabaseService()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $docker
            ->removeDatabaseVolume()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $docker
            ->startServices()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $command = new ResetCommand($applicationContext->reveal(), $docker->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] ', $display);
        static::assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }
}
