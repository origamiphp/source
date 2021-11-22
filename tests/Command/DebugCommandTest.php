<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\DebugCommand;
use App\Exception\InvalidEnvironmentException;
use App\Service\ApplicationContext;
use App\Service\Middleware\Binary\Docker;
use App\Service\Middleware\Binary\Mkcert;
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
 * @covers \App\Command\DebugCommand
 */
final class DebugCommandTest extends TestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestEnvironmentTrait;

    public function testItDisplaysDebugWithActiveEnvironment(): void
    {
        $docker = $this->prophesize(Docker::class);
        $mkcert = $this->prophesize(Mkcert::class);
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $installDir = '/var/docker';

        $docker->getVersion()->shouldBeCalledOnce()->willReturn('docker version');
        $mkcert->getVersion()->shouldBeCalledOnce()->willReturn('mkcert version');

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $applicationContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->shouldBeCalledOnce()
        ;
        $applicationContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $command = new DebugCommand($docker->reveal(), $mkcert->reveal(), $applicationContext->reveal(), $installDir);

        $commandTester = new CommandTester($command);
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('docker version', $display);
        static::assertStringContainsString('mkcert version', $display);
        static::assertStringContainsString(file_get_contents($environment->getLocation().$installDir.'/docker-compose.yml'), $display);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItDisplaysDebugWithoutActiveEnvironment(): void
    {
        $docker = $this->prophesize(Docker::class);
        $mkcert = $this->prophesize(Mkcert::class);
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $installDir = '/var/docker';

        $docker->getVersion()->shouldBeCalledOnce()->willReturn('docker version');
        $mkcert->getVersion()->shouldBeCalledOnce()->willReturn('mkcert version');

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $applicationContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->shouldBeCalledOnce()
            ->willThrow(InvalidEnvironmentException::class)
        ;

        $command = new DebugCommand($docker->reveal(), $mkcert->reveal(), $applicationContext->reveal(), $installDir);

        $commandTester = new CommandTester($command);
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('docker version', $display);
        static::assertStringContainsString('mkcert version', $display);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
