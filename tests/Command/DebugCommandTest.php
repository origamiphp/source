<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\DebugCommand;
use App\Exception\InvalidEnvironmentException;
use App\Service\CurrentContext;
use App\Service\Middleware\Binary\Docker;
use App\Service\Middleware\Binary\Mkcert;
use App\Service\Middleware\Binary\Mutagen;
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
        $mutagen = $this->prophesize(Mutagen::class);
        $mkcert = $this->prophesize(Mkcert::class);
        $currentContext = $this->prophesize(CurrentContext::class);
        $installDir = '/var/docker';

        $docker->getVersion()->shouldBeCalledOnce()->willReturn('docker version');
        $mutagen->getVersion()->shouldBeCalledOnce()->willReturn('mutagen version');
        $mkcert->getVersion()->shouldBeCalledOnce()->willReturn('mkcert version');

        $environment = $this->createEnvironment();
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

        $command = new DebugCommand($docker->reveal(), $mutagen->reveal(), $mkcert->reveal(), $currentContext->reveal(), $installDir);

        $commandTester = new CommandTester($command);
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('docker version', $display);
        static::assertStringContainsString('mutagen version', $display);
        static::assertStringContainsString('mkcert version', $display);
        static::assertStringContainsString(file_get_contents($environment->getLocation().$installDir.'/docker-compose.yml'), $display);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItDisplaysDebugWithoutActiveEnvironment(): void
    {
        $docker = $this->prophesize(Docker::class);
        $mutagen = $this->prophesize(Mutagen::class);
        $mkcert = $this->prophesize(Mkcert::class);
        $currentContext = $this->prophesize(CurrentContext::class);
        $installDir = '/var/docker';

        $docker->getVersion()->shouldBeCalledOnce()->willReturn('docker version');
        $mutagen->getVersion()->shouldBeCalledOnce()->willReturn('mutagen version');
        $mkcert->getVersion()->shouldBeCalledOnce()->willReturn('mkcert version');

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $currentContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->shouldBeCalledOnce()
            ->willThrow(InvalidEnvironmentException::class)
        ;

        $command = new DebugCommand($docker->reveal(), $mutagen->reveal(), $mkcert->reveal(), $currentContext->reveal(), $installDir);

        $commandTester = new CommandTester($command);
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('docker version', $display);
        static::assertStringContainsString('mutagen version', $display);
        static::assertStringContainsString('mkcert version', $display);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
