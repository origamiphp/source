<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\LogsCommand;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CurrentContext;
use App\Middleware\Binary\Docker;
use App\Tests\CustomProphecyTrait;
use App\Tests\TestCommandTrait;
use App\Tests\TestLocationTrait;
use Generator;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
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
final class LogsCommandTest extends WebTestCase
{
    use CustomProphecyTrait;
    use TestCommandTrait;
    use TestLocationTrait;

    /**
     * @dataProvider provideCommandModifiers
     */
    public function testItShowsServicesLogs(?int $tail, ?string $service): void
    {
        $environment = $this->createEnvironment();
        [$currentContext, $docker] = $this->prophesizeObjectArguments();

        $currentContext->loadEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce();
        $currentContext->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $docker->showServicesLogs($tail ?? 0, $service)->shouldBeCalledOnce()->willReturn(true);

        $command = new LogsCommand($currentContext->reveal(), $docker->reveal());
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
        [$currentContext, $docker] = $this->prophesizeObjectArguments();

        $currentContext->loadEnvironment(Argument::type(InputInterface::class))->willThrow(InvalidEnvironmentException::class);
        $currentContext->getActiveEnvironment()->shouldNotBeCalled();

        $command = new LogsCommand($currentContext->reveal(), $docker->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['--tail' => $tail, 'service' => $service],
            ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]
        );

        $display = $commandTester->getDisplay();

        static::assertStringContainsString('[ERROR] ', $display);
        static::assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }

    public function provideCommandModifiers(): Generator
    {
        yield 'no modifiers' => [null, null];
        yield 'tail only' => [50, null];
        yield 'tail and service' => [50, 'php'];
        yield 'service only' => [null, 'php'];
    }

    /**
     * {@inheritdoc}
     */
    protected function prophesizeObjectArguments(): array
    {
        return [
            $this->prophesize(CurrentContext::class),
            $this->prophesize(Docker::class),
        ];
    }
}
