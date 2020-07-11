<?php

declare(strict_types=1);

namespace App\Tests\Command\Contextual;

use App\Command\Contextual\RootCommand;
use App\Environment\Configuration\AbstractConfiguration;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Contextual\RootCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class RootCommandTest extends AbstractContextualCommandWebTestCase
{
    public function testItShowsRootInstructions(): void
    {
        $environment = $this->getFakeEnvironment();

        (new MethodProphecy($this->currentContext, 'getEnvironment', [Argument::type(InputInterface::class)]))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        (new MethodProphecy($this->dockerCompose, 'getRequiredVariables', [$environment]))
            ->shouldBeCalledOnce()
            ->willReturn(
                [
                    'COMPOSE_FILE' => $environment->getLocation().AbstractConfiguration::INSTALLATION_DIRECTORY.'docker-compose.yml',
                    'COMPOSE_PROJECT_NAME' => "{$environment->getType()}_{$environment->getName()}",
                    'DOCKER_PHP_IMAGE' => 'default',
                    'PROJECT_LOCATION' => $environment->getLocation(),
                ]
            )
        ;

        $commandTester = new CommandTester($this->getCommand());
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $display = $commandTester->getDisplay();

        static::assertDisplayIsVerbose($environment, $display);
        static::assertStringContainsString('export COMPOSE_FILE="~/Sites/origami'.AbstractConfiguration::INSTALLATION_DIRECTORY.'docker-compose.yml"', $display);
        static::assertStringContainsString('export COMPOSE_PROJECT_NAME="symfony_origami"', $display);
        static::assertStringContainsString('export DOCKER_PHP_IMAGE="default"', $display);
        static::assertStringContainsString('export PROJECT_LOCATION="~/Sites/origami"', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        (new MethodProphecy($this->currentContext, 'getEnvironment', [Argument::type(InputInterface::class)]))
            ->shouldBeCalledOnce()
            ->willThrow(new InvalidEnvironmentException('Dummy exception.'))
        ;

        static::assertExceptionIsHandled($this->getCommand(), '[ERROR] Dummy exception.');
    }

    /**
     * Retrieves the \App\Command\Contextual\RootCommand instance to use within the tests.
     */
    private function getCommand(): RootCommand
    {
        return new RootCommand(
            $this->currentContext->reveal(),
            $this->dockerCompose->reveal()
        );
    }
}
