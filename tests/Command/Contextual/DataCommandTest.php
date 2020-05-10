<?php

declare(strict_types=1);

namespace App\Tests\Command\Contextual;

use App\Command\Contextual\DataCommand;
use App\Helper\CommandExitCode;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use stdClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Contextual\DataCommand
 */
final class DataCommandTest extends AbstractContextualCommandWebTestCase
{
    public function testItExecutesProcessSuccessfully(): void
    {
        $environment = $this->getFakeEnvironment();

        (new MethodProphecy($this->currentContext, 'getEnvironment', [Argument::type(InputInterface::class)]))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        (new MethodProphecy($this->dockerCompose, 'showResourcesUsage', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        (new MethodProphecy($this->eventDispatcher, 'dispatch', [Argument::any()]))
            ->willReturn(new stdClass())
        ;

        $commandTester = new CommandTester($this->getCommand());
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        static::assertDisplayIsVerbose($environment, $commandTester->getDisplay());
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        (new MethodProphecy($this->currentContext, 'getEnvironment', [Argument::type(InputInterface::class)]))
            ->shouldBeCalledOnce()
            ->willReturn($this->getFakeEnvironment())
        ;

        (new MethodProphecy($this->dockerCompose, 'showResourcesUsage', []))
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        static::assertExceptionIsHandled($this->getCommand(), '[ERROR] An error occurred while checking the resources usage.');
    }

    /**
     * Retrieves the \App\Command\Contextual\DataCommand instance to use within the tests.
     */
    private function getCommand(): DataCommand
    {
        return new DataCommand(
            $this->currentContext->reveal(),
            $this->dockerCompose->reveal()
        );
    }
}
