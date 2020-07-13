<?php

declare(strict_types=1);

namespace App\Tests\Command\Contextual;

use App\Command\Contextual\StopCommand;
use App\Exception\FilesystemException;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Helper\CurrentContext;
use App\Middleware\Binary\DockerCompose;
use App\Tests\TestCommandTrait;
use App\Tests\TestFakeEnvironmentTrait;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Contextual\StopCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent::__construct()
 */
final class StopCommandTest extends WebTestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestFakeEnvironmentTrait;

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItExecutesProcessSuccessfully(): void
    {
        $environment = $this->getFakeEnvironment();

        $currentContext = $this->prophesize(CurrentContext::class);
        $dockerCompose = $this->prophesize(DockerCompose::class);
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $dockerCompose->stopServices()->shouldBeCalledOnce()->willReturn(true);
        $eventDispatcher->dispatch(Argument::any())->willReturn(new stdClass());

        $command = new StopCommand($currentContext->reveal(), $dockerCompose->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $display = $commandTester->getDisplay();

        static::assertDisplayIsVerbose($environment, $display);
        static::assertStringContainsString('[OK] Docker services successfully stopped.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $environment = $this->getFakeEnvironment();

        $currentContext = $this->prophesize(CurrentContext::class);
        $dockerCompose = $this->prophesize(DockerCompose::class);
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $dockerCompose->stopServices()->shouldBeCalledOnce()->willReturn(false);

        $command = new StopCommand($currentContext->reveal(), $dockerCompose->reveal(), $eventDispatcher->reveal());
        static::assertExceptionIsHandled($command, '[ERROR] An error occurred while stopping the Docker services.');
    }
}
