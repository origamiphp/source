<?php

declare(strict_types=1);

namespace App\Tests\Command\Contextual;

use App\Command\Contextual\LogsCommand;
use App\Exception\FilesystemException;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Helper\CurrentContext;
use App\Middleware\Binary\DockerCompose;
use App\Tests\TestCommandTrait;
use App\Tests\TestFakeEnvironmentTrait;
use Generator;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Contextual\LogsCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class LogsCommandTest extends WebTestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestFakeEnvironmentTrait;

    /**
     * @dataProvider provideCommandModifiers
     *
     * @throws InvalidEnvironmentException
     * @throws FilesystemException
     */
    public function testItShowsServicesLogs(?int $tail, ?string $service): void
    {
        $environment = $this->getFakeEnvironment();

        $currentContext = $this->prophesize(CurrentContext::class);
        $dockerCompose = $this->prophesize(DockerCompose::class);

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $dockerCompose->showServicesLogs($tail ?? 0, $service)->shouldBeCalledOnce()->willReturn(true);

        $command = new LogsCommand($currentContext->reveal(), $dockerCompose->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['--tail' => $tail, 'service' => $service],
            ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]
        );

        $display = $commandTester->getDisplay();

        static::assertDisplayIsVerbose($environment, $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * @dataProvider provideCommandModifiers
     *
     * @throws InvalidEnvironmentException
     * @throws FilesystemException
     */
    public function testItGracefullyExitsWhenAnExceptionOccurred(?int $tail, ?string $service): void
    {
        $environment = $this->getFakeEnvironment();
        $exception = new InvalidEnvironmentException('Dummy exception.');

        $currentContext = $this->prophesize(CurrentContext::class);
        $dockerCompose = $this->prophesize(DockerCompose::class);

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $dockerCompose->showServicesLogs($tail ?? 0, $service)->shouldBeCalledOnce()->willThrow($exception);

        $command = new LogsCommand($currentContext->reveal(), $dockerCompose->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['--tail' => $tail, 'service' => $service],
            ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]
        );

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Dummy exception.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }

    public function provideCommandModifiers(): Generator
    {
        yield 'no modifiers' => [null, null];
        yield 'tail only' => [50, null];
        yield 'tail and service' => [50, 'php'];
        yield 'service only' => [null, 'php'];
    }
}
