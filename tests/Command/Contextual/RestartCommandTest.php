<?php

declare(strict_types=1);

namespace App\Tests\Command\Contextual;

use App\Command\Contextual\RestartCommand;
use App\Helper\CurrentContext;
use App\Middleware\Binary\DockerCompose;
use App\Tests\Command\TestCommandTrait;
use App\Tests\TestLocationTrait;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Command\Command;
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
    use TestLocationTrait;

    public function testItExecutesProcessSuccessfully(): void
    {
        $environment = $this->createEnvironment();

        [$currentContext, $dockerCompose] = $this->prophesizeRestartCommandArguments();

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $currentContext->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $dockerCompose->restartServices()->shouldBeCalledOnce()->willReturn(true);

        $command = new RestartCommand($currentContext->reveal(), $dockerCompose->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $display = $commandTester->getDisplay();

        static::assertDisplayIsVerbose($environment, $display);
        static::assertStringContainsString('[OK] ', $display);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $environment = $this->createEnvironment();

        [$currentContext, $dockerCompose] = $this->prophesizeRestartCommandArguments();

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $currentContext->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $dockerCompose->restartServices()->shouldBeCalledOnce()->willReturn(false);

        $command = new RestartCommand($currentContext->reveal(), $dockerCompose->reveal());
        static::assertExceptionIsHandled($command, '[ERROR] ');
    }

    /**
     * Prophesizes arguments needed by the \App\Command\Contextual\RestartCommand class.
     */
    private function prophesizeRestartCommandArguments(): array
    {
        return [
            $this->prophesize(CurrentContext::class),
            $this->prophesize(DockerCompose::class),
        ];
    }
}
