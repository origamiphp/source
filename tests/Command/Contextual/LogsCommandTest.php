<?php

declare(strict_types=1);

namespace App\Tests\Command\Contextual;

use App\Command\Contextual\LogsCommand;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use Generator;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
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
final class LogsCommandTest extends AbstractContextualCommandWebTestCase
{
    /**
     * @dataProvider provideCommandModifiers
     */
    public function testItShowsServicesLogs(?int $tail, ?string $service): void
    {
        $environment = $this->getFakeEnvironment();

        (new MethodProphecy($this->currentContext, 'getEnvironment', [Argument::type(InputInterface::class)]))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        (new MethodProphecy($this->dockerCompose, 'showServicesLogs', [$tail ?? 0, $service]))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $commandTester = new CommandTester($this->getCommand());
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
     */
    public function testItGracefullyExitsWhenAnExceptionOccurred(?int $tail, ?string $service): void
    {
        $environment = $this->getFakeEnvironment();

        (new MethodProphecy($this->currentContext, 'getEnvironment', [Argument::type(InputInterface::class)]))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        (new MethodProphecy($this->dockerCompose, 'showServicesLogs', [$tail ?? 0, $service]))
            ->shouldBeCalledOnce()
            ->willThrow(new InvalidEnvironmentException('Dummy exception.'))
        ;

        $commandTester = new CommandTester($this->getCommand());
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
        yield [null, null];
        yield [50, null];
        yield [50, 'php'];
        yield [null, 'php'];
    }

    /**
     * Retrieves the \App\Command\Contextual\LogsCommand instance to use within the tests.
     */
    private function getCommand(): LogsCommand
    {
        return new LogsCommand(
            $this->currentContext->reveal(),
            $this->dockerCompose->reveal()
        );
    }
}
