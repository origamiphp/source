<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\StartCommand;
use App\Helper\CurrentContext;
use App\Helper\ProcessProxy;
use App\Middleware\Binary\Docker;
use App\Tests\CustomProphecyTrait;
use App\Tests\TestCommandTrait;
use App\Tests\TestLocationTrait;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\StartCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class StartCommandTest extends WebTestCase
{
    use CustomProphecyTrait;
    use TestCommandTrait;
    use TestLocationTrait;

    public function testItStartsTheEnvironment(): void
    {
        $environment = $this->createEnvironment();
        [$currentContext, $processProxy, $docker, $eventDispatcher] = $this->prophesizeObjectArguments();

        $currentContext->loadEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce();
        $currentContext->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $docker->startServices()->shouldBeCalledOnce()->willReturn(true);
        $eventDispatcher->dispatch(Argument::any())->shouldBeCalledOnce();

        $command = new StartCommand($currentContext->reveal(), $processProxy->reveal(), $docker->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] ', $display);
        static::assertStringContainsString('[INFO] ', $display);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItDoesNotStartMultipleEnvironments(): void
    {
        $environment = $this->createEnvironment();
        $environment->activate();
        [$currentContext, $processProxy, $docker, $eventDispatcher] = $this->prophesizeObjectArguments();

        $currentContext->loadEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce();
        $currentContext->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $processProxy->getWorkingDirectory()->willReturn('');
        $docker->startServices()->shouldNotBeCalled();
        $eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $command = new StartCommand($currentContext->reveal(), $processProxy->reveal(), $docker->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] ', $display);
        static::assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $environment = $this->createEnvironment();
        [$currentContext, $processProxy, $docker, $eventDispatcher] = $this->prophesizeObjectArguments();

        $currentContext->loadEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce();
        $currentContext->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $currentContext->loadEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce();
        $currentContext->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $processProxy->getWorkingDirectory()->willReturn('');
        $docker->startServices()->shouldBeCalledOnce()->willReturn(false);

        $command = new StartCommand($currentContext->reveal(), $processProxy->reveal(), $docker->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] ', $display);
        static::assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }

    /**
     * {@inheritdoc}
     */
    protected function prophesizeObjectArguments(): array
    {
        return [
            $this->prophesize(CurrentContext::class),
            $this->prophesize(ProcessProxy::class),
            $this->prophesize(Docker::class),
            $this->prophesize(EventDispatcher::class),
        ];
    }
}
