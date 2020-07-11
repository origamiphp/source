<?php

declare(strict_types=1);

namespace App\Tests\Command\Main;

use App\Command\Main\StartCommand;
use App\Exception\FilesystemException;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Helper\CurrentContext;
use App\Helper\ProcessProxy;
use App\Middleware\Binary\DockerCompose;
use App\Tests\TestCommandTrait;
use App\Tests\TestFakeEnvironmentTrait;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Main\StartCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class StartCommandTest extends WebTestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestFakeEnvironmentTrait;

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItStartsTheEnvironment(): void
    {
        $environment = $this->getFakeEnvironment();

        $currentContext = $this->prophesize(CurrentContext::class);
        $processProxy = $this->prophesize(ProcessProxy::class);
        $dockerCompose = $this->prophesize(DockerCompose::class);
        $eventDispatcher = $this->prophesize(EventDispatcher::class);

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $dockerCompose->startServices()->shouldBeCalledOnce()->willReturn(true);
        $eventDispatcher->dispatch(Argument::any())->shouldBeCalledOnce();

        $command = new StartCommand($currentContext->reveal(), $processProxy->reveal(), $dockerCompose->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Docker services successfully started.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItDoesNotStartMultipleEnvironments(): void
    {
        $environment = $this->getFakeEnvironment();
        $environment->activate();

        $currentContext = $this->prophesize(CurrentContext::class);
        $processProxy = $this->prophesize(ProcessProxy::class);
        $dockerCompose = $this->prophesize(DockerCompose::class);
        $eventDispatcher = $this->prophesize(EventDispatcher::class);

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $processProxy->getWorkingDirectory()->willReturn('');
        $dockerCompose->startServices()->shouldNotBeCalled();
        $eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $command = new StartCommand($currentContext->reveal(), $processProxy->reveal(), $dockerCompose->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Unable to start an environment when there is already a running one.', $display);
        static::assertSame(CommandExitCode::INVALID, $commandTester->getStatusCode());
    }

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $environment = $this->getFakeEnvironment();

        $currentContext = $this->prophesize(CurrentContext::class);
        $processProxy = $this->prophesize(ProcessProxy::class);
        $dockerCompose = $this->prophesize(DockerCompose::class);
        $eventDispatcher = $this->prophesize(EventDispatcher::class);

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $dockerCompose->startServices()->shouldBeCalledOnce()->willReturn(false);
        $eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $command = new StartCommand($currentContext->reveal(), $processProxy->reveal(), $dockerCompose->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] An error occurred while starting the Docker services.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }
}
