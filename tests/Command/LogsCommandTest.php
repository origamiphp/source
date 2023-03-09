<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\LogsCommand;
use App\Exception\InvalidEnvironmentException;
use App\Service\ApplicationContext;
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
 * @covers \App\Command\LogsCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class LogsCommandTest extends TestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestEnvironmentTrait;

    /**
     * @dataProvider provideCommandModifiers
     */
    public function testItShowsServicesLogs(?int $tail, ?string $service): void
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
            ->showServicesLogs($tail ?? 0, $service)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $command = new LogsCommand($applicationContext->reveal(), $docker->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['--tail' => $tail, 'service' => $service],
            ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]
        );

        $display = $commandTester->getDisplay();
        static::assertDisplayIsVerbose($environment, $display);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * @dataProvider provideCommandModifiers
     */
    public function testItGracefullyExitsWhenAnExceptionOccurred(?int $tail, ?string $service): void
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

        $command = new LogsCommand($applicationContext->reveal(), $docker->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['--tail' => $tail, 'service' => $service],
            ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]
        );

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] ', $display);
        static::assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }

    public function provideCommandModifiers(): \Iterator
    {
        yield 'no modifiers' => [null, null];
        yield 'tail only' => [50, null];
        yield 'tail and service' => [50, 'php'];
        yield 'service only' => [null, 'php'];
    }
}
