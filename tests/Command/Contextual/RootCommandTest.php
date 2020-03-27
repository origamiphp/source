<?php

declare(strict_types=1);

namespace App\Tests\Command\Contextual;

use App\Command\Contextual\RootCommand;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Tests\AbstractCommandWebTestCase;
use App\Tests\TestFakeEnvironmentTrait;
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
final class RootCommandTest extends AbstractCommandWebTestCase
{
    use TestFakeEnvironmentTrait;

    public function testItShowsRootInstructions(): void
    {
        $environment = $this->getFakeEnvironment();

        $this->database->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $this->dockerCompose->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $this->dockerCompose->getRequiredVariables()->shouldBeCalledOnce()->willReturn(
            [
                'COMPOSE_FILE' => sprintf('%s/var/docker/docker-compose.yml', $environment->getLocation()),
                'COMPOSE_PROJECT_NAME' => $environment->getType().'_'.$environment->getName(),
                'DOCKER_PHP_IMAGE' => 'default',
                'PROJECT_LOCATION' => $environment->getLocation(),
            ]
        );

        $commandTester = new CommandTester($this->getCommand(RootCommand::class));
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $display = $commandTester->getDisplay();

        static::assertDisplayIsVerbose($environment, $display);
        static::assertStringContainsString('export COMPOSE_FILE="~/Sites/origami/var/docker/docker-compose.yml"', $display);
        static::assertStringContainsString('export COMPOSE_PROJECT_NAME="symfony_origami"', $display);
        static::assertStringContainsString('export DOCKER_PHP_IMAGE="default"', $display);
        static::assertStringContainsString('export PROJECT_LOCATION="~/Sites/origami"', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $this->database->getActiveEnvironment()
            ->willThrow(new InvalidEnvironmentException('Dummy exception.'))
        ;

        static::assertExceptionIsHandled($this->getCommand(RootCommand::class), '[ERROR] Dummy exception.');
    }
}
