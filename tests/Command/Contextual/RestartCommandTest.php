<?php

declare(strict_types=1);

namespace App\Tests\Command\Contextual;

use App\Command\Contextual\RestartCommand;
use App\Exception\FilesystemException;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Helper\CurrentContext;
use App\Middleware\Binary\DockerCompose;
use App\Tests\TestCommandTrait;
use App\Tests\TestFakeEnvironmentTrait;
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
 * @covers \App\Command\Contextual\RestartCommand
 */
final class RestartCommandTest extends WebTestCase
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

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $dockerCompose->restartServices()->shouldBeCalledOnce()->willReturn(true);

        $command = new RestartCommand($currentContext->reveal(), $dockerCompose->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $display = $commandTester->getDisplay();

        static::assertDisplayIsVerbose($environment, $display);
        static::assertStringContainsString('[OK] Docker services successfully restarted.', $display);
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

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $dockerCompose->restartServices()->shouldBeCalledOnce()->willReturn(false);

        $command = new RestartCommand($currentContext->reveal(), $dockerCompose->reveal());
        static::assertExceptionIsHandled($command, '[ERROR] An error occurred while restarting the Docker services.');
    }
}
