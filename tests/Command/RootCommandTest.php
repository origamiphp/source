<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\RootCommand;
use App\Exception\InvalidEnvironmentException;
use App\Service\ApplicationContext;
use App\Service\Middleware\Binary\Docker;
use App\Tests\TestCommandTrait;
use App\Tests\TestEnvironmentTrait;
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
 * @covers \App\Command\RootCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class RootCommandTest extends WebTestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestEnvironmentTrait;

    public function testItShowsRootInstructions(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $docker = $this->prophesize(Docker::class);

        $environment = $this->createEnvironment();
        $environmentLocation = $environment->getLocation();

        $environmentVariables = [
            'COMPOSE_FILE' => "{$environmentLocation}var/docker/docker-compose.yml",
            'COMPOSE_PROJECT_NAME' => "{$environment->getType()}_{$environment->getName()}",
            'PROJECT_LOCATION' => $environmentLocation,
        ];

        $applicationContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->shouldBeCalledOnce()
        ;

        $applicationContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $docker
            ->getEnvironmentVariables($environment)
            ->willReturn($environmentVariables)
        ;

        $command = new RootCommand($applicationContext->reveal(), $docker->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $display = $commandTester->getDisplay();
        foreach ($environmentVariables as $name => $value) {
            static::assertStringContainsString("export {$name}=\"{$value}\"", $display);
        }

        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $docker = $this->prophesize(Docker::class);

        $applicationContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->shouldBeCalledOnce()
            ->willThrow(InvalidEnvironmentException::class)
        ;

        $command = new RootCommand($applicationContext->reveal(), $docker->reveal());
        static::assertExceptionIsHandled($command);
    }
}
