<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Helper\CurrentContext;
use App\Helper\ProcessFactory;
use App\Middleware\Binary\Docker;
use App\Tests\CustomProphecyTrait;
use App\Tests\TestEnvironmentTrait;
use Generator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @covers \App\Middleware\Binary\Docker
 */
final class DockerTest extends TestCase
{
    use CustomProphecyTrait;
    use TestEnvironmentTrait;

    /**
     * @dataProvider provideDockerComposeScenarios
     */
    public function testItExecutesDockerComposeInstruction(string $function, array $action): void
    {
        $environment = $this->createEnvironment();
        [$currentContext, $processFactory] = $this->prophesizeObjectArguments();

        $currentContext->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $projectName = "{$environment->getType()}_{$environment->getName()}";
        $currentContext->getProjectName()->shouldBeCalledTimes(2)->willReturn($projectName);

        $defaultDockerComposeOptions = [
            "--file={$this->location}/var/docker/docker-compose.yml",
            "--project-directory={$this->location}",
            "--project-name={$projectName}",
        ];
        $command = array_merge(['docker', 'compose'], $defaultDockerComposeOptions, $action);

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $processFactory->runForegroundProcess($command, Argument::type('array'))->shouldBeCalledOnce()->willReturn($process->reveal());

        $docker = new Docker($currentContext->reveal(), $processFactory->reveal());
        static::assertTrue($docker->{$function}());
    }

    public function provideDockerComposeScenarios(): Generator
    {
        // @see \App\Middleware\Binary\Docker::pullServices
        yield 'pull' => [
            'pullServices',
            ['pull'],
        ];

        // @see \App\Middleware\Binary\Docker::buildServices
        yield 'build' => [
            'buildServices',
            ['build', '--pull', '--parallel'],
        ];

        // @see \App\Middleware\Binary\Docker::fixPermissionsOnSharedSSHAgent
        yield 'permissions' => [
            'fixPermissionsOnSharedSSHAgent',
            ['exec', '-T', 'php', 'bash', '-c', 'chown www-data:www-data /run/host-services/ssh-auth.sock'],
        ];

        // @see \App\Middleware\Binary\Docker::startServices
        yield 'start' => [
            'startServices',
            ['up', '--build', '--detach', '--remove-orphans'],
        ];

        // @see \App\Middleware\Binary\Docker::stopServices
        yield 'stop' => [
            'stopServices',
            ['stop'],
        ];

        // @see \App\Middleware\Binary\Docker::restartServices
        yield 'restart' => [
            'restartServices',
            ['restart'],
        ];

        // @see \App\Middleware\Binary\Docker::showServicesStatus
        yield 'status' => [
            'showServicesStatus',
            ['ps'],
        ];

        // @see \App\Middleware\Binary\Docker::removeServices
        yield 'uninstall' => [
            'removeServices',
            ['down', '--rmi', 'local', '--volumes', '--remove-orphans'],
        ];
    }

    public function testItDisplaysResourceUsage(): void
    {
        $environment = $this->createEnvironment();
        [$currentContext, $processFactory] = $this->prophesizeObjectArguments();

        $currentContext->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $projectName = "{$environment->getType()}_{$environment->getName()}";
        $currentContext->getProjectName()->shouldBeCalledTimes(2)->willReturn($projectName);

        $defaultDockerComposeOptions = [
            "--file={$this->location}/var/docker/docker-compose.yml",
            "--project-directory={$this->location}",
            "--project-name={$projectName}",
        ];
        $command = implode(' ', array_merge(['docker', 'compose'], $defaultDockerComposeOptions, ['ps --quiet | xargs docker stats']));

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $processFactory->runForegroundProcessFromShellCommandLine($command, Argument::type('array'))->shouldBeCalledOnce()->willReturn($process->reveal());

        $docker = new Docker($currentContext->reveal(), $processFactory->reveal());
        static::assertTrue($docker->showResourcesUsage());
    }

    /**
     * @dataProvider provideDockerComposeLogsScenarios
     */
    public function testItDisplaysLogs(?int $tail = null, ?string $service = null): void
    {
        $environment = $this->createEnvironment();
        [$currentContext, $processFactory] = $this->prophesizeObjectArguments();

        $currentContext->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $projectName = "{$environment->getType()}_{$environment->getName()}";
        $currentContext->getProjectName()->shouldBeCalledTimes(2)->willReturn($projectName);

        $defaultDockerComposeOptions = [
            "--file={$this->location}/var/docker/docker-compose.yml",
            "--project-directory={$this->location}",
            "--project-name={$projectName}",
        ];
        $action = ['logs', '--follow', sprintf('--tail=%s', $tail ?? 0)];
        if ($service) {
            $action[] = $service;
        }
        $command = array_merge(['docker', 'compose'], $defaultDockerComposeOptions, $action);

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $processFactory->runForegroundProcess($command, Argument::type('array'))->shouldBeCalledOnce()->willReturn($process->reveal());

        $docker = new Docker($currentContext->reveal(), $processFactory->reveal());
        static::assertTrue($docker->showServicesLogs($tail, $service));
    }

    public function provideDockerComposeLogsScenarios(): Generator
    {
        yield 'no·modifiers' => [];
        yield 'tail only' => [42, null];
        yield 'tail and service' => [42, 'php'];
        yield 'service only' => [null, 'php'];
    }

    public function testItOpensTerminalWithSpecificUser(): void
    {
        $environment = $this->createEnvironment();
        [$currentContext, $processFactory] = $this->prophesizeObjectArguments();

        $currentContext->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $projectName = "{$environment->getType()}_{$environment->getName()}";
        $currentContext->getProjectName()->shouldBeCalledOnce()->willReturn($projectName);

        $command = "docker exec -it --user=www-data:www-data {$projectName}_php_1 bash --login";

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $processFactory->runForegroundProcessFromShellCommandLine($command, Argument::type('array'))->shouldBeCalledOnce()->willReturn($process->reveal());

        $docker = new Docker($currentContext->reveal(), $processFactory->reveal());
        static::assertTrue($docker->openTerminal('php', 'www-data:www-data'));
    }

    public function testItOpensTerminalWithoutSpecificUser(): void
    {
        $environment = $this->createEnvironment();
        [$currentContext, $processFactory] = $this->prophesizeObjectArguments();

        $currentContext->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $projectName = "{$environment->getType()}_{$environment->getName()}";
        $currentContext->getProjectName()->shouldBeCalledOnce()->willReturn($projectName);

        $command = "docker exec -it {$projectName}_php_1 bash --login";

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $processFactory->runForegroundProcessFromShellCommandLine($command, Argument::type('array'))->shouldBeCalledOnce()->willReturn($process->reveal());

        $docker = new Docker($currentContext->reveal(), $processFactory->reveal());
        static::assertTrue($docker->openTerminal('php'));
    }

    /**
     * {@inheritdoc}
     */
    protected function prophesizeObjectArguments(): array
    {
        return [
            $this->prophesize(CurrentContext::class),
            $this->prophesize(ProcessFactory::class),
        ];
    }
}
