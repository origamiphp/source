<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\UninstallCommand;
use App\Exception\InvalidEnvironmentException;
use App\Service\CurrentContext;
use App\Service\Middleware\Binary\Docker;
use App\Service\Setup\ConfigurationFiles;
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
 * @covers \App\Command\UninstallCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class UninstallCommandTest extends TestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestEnvironmentTrait;

    public function testItUninstallsTheCurrentEnvironment(): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $docker = $this->prophesize(Docker::class);
        $configurationFiles = $this->prophesize(ConfigurationFiles::class);
        $eventDispatcher = $this->prophesize(EventDispatcher::class);

        $environment = $this->createEnvironment();
        $environment->deactivate();
        $this->installEnvironmentConfiguration($environment);

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
            ->removeServices()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $eventDispatcher
            ->dispatch(Argument::any())
            ->shouldBeCalledOnce()
        ;

        $configurationFiles
            ->uninstall($environment)
            ->shouldBeCalledOnce()
        ;

        $command = new UninstallCommand($currentContext->reveal(), $docker->reveal(), $configurationFiles->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] ', $display);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItDisplaysWarningWithError(): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $docker = $this->prophesize(Docker::class);
        $configurationFiles = $this->prophesize(ConfigurationFiles::class);
        $eventDispatcher = $this->prophesize(EventDispatcher::class);

        $environment = $this->createEnvironment();
        $environment->deactivate();
        $this->installEnvironmentConfiguration($environment);

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
            ->removeServices()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $eventDispatcher
            ->dispatch(Argument::any())
            ->shouldBeCalledOnce()
        ;

        $configurationFiles
            ->uninstall($environment)
            ->shouldBeCalledOnce()
        ;

        $command = new UninstallCommand($currentContext->reveal(), $docker->reveal(), $configurationFiles->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[WARNING] ', $display);
        static::assertStringContainsString('[OK] ', $display);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $docker = $this->prophesize(Docker::class);
        $configurationFiles = $this->prophesize(ConfigurationFiles::class);
        $eventDispatcher = $this->prophesize(EventDispatcher::class);

        $currentContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->willThrow(InvalidEnvironmentException::class)
        ;

        $currentContext
            ->getActiveEnvironment()
            ->shouldNotBeCalled()
        ;

        $command = new UninstallCommand($currentContext->reveal(), $docker->reveal(), $configurationFiles->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] ', $display);
        static::assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }
}
