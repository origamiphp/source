<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Environment\Configuration\AbstractConfiguration;
use App\Helper\ProcessFactory;
use App\Middleware\Binary\Docker;
use App\Tests\CustomProphecyTrait;
use App\Tests\TestDockerTrait;
use App\Tests\TestLocationTrait;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @covers \App\Middleware\Binary\Docker
 */
final class DockerDefaultTest extends WebTestCase
{
    use CustomProphecyTrait;
    use TestDockerTrait;
    use TestLocationTrait;

    public function testItDefinesTheActiveEnvironmentWithInternals(): void
    {
        $environment = $this->createEnvironment();

        [$processFactory] = $this->prophesizeObjectArguments();

        $docker = new Docker($processFactory->reveal());
        $docker->refreshEnvironmentVariables($environment);

        $variables = $docker->getRequiredVariables($environment);

        static::assertArrayHasKey('COMPOSE_FILE', $variables);
        static::assertSame($this->location.AbstractConfiguration::INSTALLATION_DIRECTORY.'docker-compose.yml', $variables['COMPOSE_FILE']);

        static::assertArrayHasKey('COMPOSE_PROJECT_NAME', $variables);
        static::assertSame('symfony_origami', $variables['COMPOSE_PROJECT_NAME']);

        static::assertArrayHasKey('DOCKER_PHP_IMAGE', $variables);
        static::assertFalse($variables['DOCKER_PHP_IMAGE']);

        static::assertArrayHasKey('PROJECT_LOCATION', $variables);
        static::assertSame($this->location, $variables['PROJECT_LOCATION']);
    }

    public function testItPreparesTheEnvironmentServices(): void
    {
        $commands = [
            ['docker', 'compose', 'pull'],
            ['docker', 'compose', 'build', '--pull', '--parallel'],
        ];

        [$processFactory] = $this->prophesizeObjectArguments();
        $process = $this->prophesize(Process::class);

        $process->isSuccessful()->shouldBeCalledTimes(2)->willReturn(true);
        $processFactory->runForegroundProcess($commands[0], Argument::type('array'))->shouldBeCalledOnce()->willReturn($process->reveal());
        $processFactory->runForegroundProcess($commands[1], Argument::type('array'))->shouldBeCalledOnce()->willReturn($process->reveal());

        $docker = new Docker($processFactory->reveal());
        $docker->refreshEnvironmentVariables($this->createEnvironment());

        static::assertTrue($docker->prepareServices());
    }

    public function testItShowsResourcesUsage(): void
    {
        $command = 'docker compose ps -q | xargs docker stats';
        $docker = $this->prepareForegroundFromShellCommand($command);

        static::assertTrue($docker->showResourcesUsage());
    }

    public function testItShowsServicesStatus(): void
    {
        $command = ['docker', 'compose', 'ps'];
        $docker = $this->prepareForegroundCommand($command);

        static::assertTrue($docker->showServicesStatus());
    }

    public function testItRestartsServicesStatus(): void
    {
        $command = ['docker', 'compose', 'restart'];
        $docker = $this->prepareForegroundCommand($command);

        static::assertTrue($docker->restartServices());
    }

    public function testItStartsServicesStatus(): void
    {
        $command = ['docker', 'compose', 'up', '--build', '--detach', '--remove-orphans'];
        $docker = $this->prepareForegroundCommand($command);

        static::assertTrue($docker->startServices());
    }

    public function testItStopsServicesStatus(): void
    {
        $command = ['docker', 'compose', 'stop'];
        $docker = $this->prepareForegroundCommand($command);

        static::assertTrue($docker->stopServices());
    }

    public function testItRemovesServicesStatus(): void
    {
        $command = ['docker', 'compose', 'down', '--rmi', 'local', '--volumes', '--remove-orphans'];
        $docker = $this->prepareForegroundCommand($command);

        static::assertTrue($docker->removeServices());
    }

    /**
     * {@inheritdoc}
     */
    protected function prophesizeObjectArguments(): array
    {
        return [
            $this->prophesize(ProcessFactory::class),
        ];
    }
}
