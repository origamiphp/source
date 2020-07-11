<?php

declare(strict_types=1);

namespace App\Tests\Command\Contextual;

use App\Command\Contextual\RootCommand;
use App\Environment\Configuration\AbstractConfiguration;
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
 * @covers \App\Command\Contextual\RootCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class RootCommandTest extends WebTestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestFakeEnvironmentTrait;

    /**
     * @throws InvalidEnvironmentException
     * @throws FilesystemException
     */
    public function testItShowsRootInstructions(): void
    {
        $environment = $this->getFakeEnvironment();
        $environmentVariables = [
            'COMPOSE_FILE' => $environment->getLocation().AbstractConfiguration::INSTALLATION_DIRECTORY.'docker-compose.yml',
            'COMPOSE_PROJECT_NAME' => "{$environment->getType()}_{$environment->getName()}",
            'DOCKER_PHP_IMAGE' => 'default',
            'PROJECT_LOCATION' => $environment->getLocation(),
        ];

        $currentContext = $this->prophesize(CurrentContext::class);
        $dockerCompose = $this->prophesize(DockerCompose::class);

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $dockerCompose->getRequiredVariables($environment)->willReturn($environmentVariables);

        $command = new RootCommand($currentContext->reveal(), $dockerCompose->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $display = $commandTester->getDisplay();

        static::assertDisplayIsVerbose($environment, $display);
        static::assertStringContainsString('export COMPOSE_FILE="~/Sites/origami'.AbstractConfiguration::INSTALLATION_DIRECTORY.'docker-compose.yml"', $display);
        static::assertStringContainsString('export COMPOSE_PROJECT_NAME="symfony_origami"', $display);
        static::assertStringContainsString('export DOCKER_PHP_IMAGE="default"', $display);
        static::assertStringContainsString('export PROJECT_LOCATION="~/Sites/origami"', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * @throws InvalidEnvironmentException
     * @throws FilesystemException
     */
    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $dockerCompose = $this->prophesize(DockerCompose::class);

        $exception = new InvalidEnvironmentException('Dummy exception.');
        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willThrow($exception);

        $command = new RootCommand($currentContext->reveal(), $dockerCompose->reveal());
        static::assertExceptionIsHandled($command, '[ERROR] Dummy exception.');
    }
}
