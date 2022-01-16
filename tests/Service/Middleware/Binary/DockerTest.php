<?php

declare(strict_types=1);

namespace App\Tests\Service\Middleware\Binary;

use App\Service\ApplicationContext;
use App\Service\Middleware\Binary\Docker;
use App\Service\Wrapper\ProcessFactory;
use App\Tests\TestEnvironmentTrait;
use Iterator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @covers \App\Service\Middleware\Binary\Docker
 */
final class DockerTest extends TestCase
{
    use ProphecyTrait;
    use TestEnvironmentTrait;

    public function testItRetrievesBinaryVersion(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $processFactory = $this->prophesize(ProcessFactory::class);
        $installDir = '/var/docker';

        $process = $this->prophesize(Process::class);
        $process->getOutput()
            ->shouldBeCalledOnce()
            ->willReturn('docker version')
        ;

        $processFactory->runBackgroundProcess(Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn($process)
        ;

        $docker = new Docker($applicationContext->reveal(), $processFactory->reveal(), $installDir);
        static::assertSame('docker version', $docker->getVersion());
    }

    /**
     * @dataProvider provideDockerComposeScenarios
     */
    public function testItExecutesDockerComposeInstruction(string $function): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $processFactory = $this->prophesize(ProcessFactory::class);
        $installDir = '/var/docker';

        $environment = $this->createEnvironment();
        $projectName = "{$environment->getType()}_{$environment->getName()}";
        $process = $this->prophesize(Process::class);

        $applicationContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $applicationContext->getProjectName()
            ->shouldBeCalledOnce()
            ->willReturn($projectName)
        ;

        $process
            ->isSuccessful()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $processFactory
            ->runForegroundProcess(Argument::type('array'), Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $docker = new Docker($applicationContext->reveal(), $processFactory->reveal(), $installDir);
        static::assertTrue($docker->{$function}());
    }

    public function provideDockerComposeScenarios(): Iterator
    {
        // @see \App\Middleware\Binary\Docker::pullServices
        yield 'pull' => ['pullServices'];

        // @see \App\Middleware\Binary\Docker::buildServices
        yield 'build' => ['buildServices'];

        // @see \App\Middleware\Binary\Docker::fixPermissionsOnSharedSSHAgent
        yield 'permissions' => ['fixPermissionsOnSharedSSHAgent'];

        // @see \App\Middleware\Binary\Docker::startServices
        yield 'start' => ['startServices'];

        // @see \App\Middleware\Binary\Docker::stopServices
        yield 'stop' => ['stopServices'];

        // @see \App\Middleware\Binary\Docker::showServicesStatus
        yield 'status' => ['showServicesStatus'];

        // @see \App\Middleware\Binary\Docker::removeServices
        yield 'uninstall' => ['removeServices'];

        // @see \App\Middleware\Binary\Docker::removeDatabaseService
        yield 'remove database service' => ['removeDatabaseService'];

        // @see \App\Middleware\Binary\Docker::removeDatabaseVolume
        yield 'remove database volume' => ['removeDatabaseVolume'];
    }

    public function testItDisplaysResourceUsage(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $processFactory = $this->prophesize(ProcessFactory::class);
        $installDir = '/var/docker';

        $environment = $this->createEnvironment();
        $projectName = "{$environment->getType()}_{$environment->getName()}";
        $process = $this->prophesize(Process::class);

        $applicationContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $applicationContext
            ->getProjectName()
            ->shouldBeCalledOnce()
            ->willReturn($projectName)
        ;

        $process
            ->isSuccessful()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $processFactory
            ->runForegroundProcessFromShellCommandLine(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $docker = new Docker($applicationContext->reveal(), $processFactory->reveal(), $installDir);
        static::assertTrue($docker->showResourcesUsage());
    }

    /**
     * @dataProvider provideDockerComposeLogsScenarios
     */
    public function testItDisplaysLogs(?int $tail = null, ?string $service = null): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $processFactory = $this->prophesize(ProcessFactory::class);
        $installDir = '/var/docker';

        $environment = $this->createEnvironment();
        $projectName = "{$environment->getType()}_{$environment->getName()}";
        $process = $this->prophesize(Process::class);

        $applicationContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $applicationContext
            ->getProjectName()
            ->shouldBeCalledOnce()
            ->willReturn($projectName)
        ;

        $process
            ->isSuccessful()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $processFactory
            ->runForegroundProcess(Argument::type('array'), Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $docker = new Docker($applicationContext->reveal(), $processFactory->reveal(), $installDir);
        static::assertTrue($docker->showServicesLogs($tail, $service));
    }

    public function provideDockerComposeLogsScenarios(): Iterator
    {
        yield 'no·modifiers' => [];
        yield 'tail only' => [42, null];
        yield 'tail and service' => [42, 'php'];
        yield 'service only' => [null, 'php'];
    }

    public function testItOpensTerminalWithSpecificUser(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $processFactory = $this->prophesize(ProcessFactory::class);
        $installDir = '/var/docker';

        $environment = $this->createEnvironment();
        $projectName = "{$environment->getType()}_{$environment->getName()}";
        $process = $this->prophesize(Process::class);

        $applicationContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $applicationContext
            ->getProjectName()
            ->shouldBeCalledOnce()
            ->willReturn($projectName)
        ;

        $process
            ->isSuccessful()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $processFactory
            ->runForegroundProcessFromShellCommandLine(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $docker = new Docker($applicationContext->reveal(), $processFactory->reveal(), $installDir);
        static::assertTrue($docker->openTerminal('php', 'www-data:www-data'));
    }

    public function testItOpensTerminalWithoutSpecificUser(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $processFactory = $this->prophesize(ProcessFactory::class);
        $installDir = '/var/docker';

        $environment = $this->createEnvironment();
        $projectName = "{$environment->getType()}_{$environment->getName()}";
        $process = $this->prophesize(Process::class);

        $applicationContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $applicationContext
            ->getProjectName()
            ->shouldBeCalledOnce()
            ->willReturn($projectName)
        ;

        $process
            ->isSuccessful()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $processFactory
            ->runForegroundProcessFromShellCommandLine(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $docker = new Docker($applicationContext->reveal(), $processFactory->reveal(), $installDir);
        static::assertTrue($docker->openTerminal('php'));
    }

    public function testItDumpsMysqlDatabase(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $processFactory = $this->prophesize(ProcessFactory::class);
        $installDir = '/var/docker';

        $environment = $this->createEnvironment();
        $projectName = "{$environment->getType()}_{$environment->getName()}";
        $process = $this->prophesize(Process::class);

        $applicationContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $applicationContext
            ->getProjectName()
            ->shouldBeCalledOnce()
            ->willReturn($projectName)
        ;

        $process
            ->isSuccessful()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $processFactory
            ->runForegroundProcessFromShellCommandLine(Argument::containingString('> /path/to/dump_file.sql'), Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $docker = new Docker($applicationContext->reveal(), $processFactory->reveal(), $installDir);
        static::assertTrue($docker->dumpMysqlDatabase('username', 'password', '/path/to/dump_file.sql'));
    }

    public function testItRestoresMysqlDatabase(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $processFactory = $this->prophesize(ProcessFactory::class);
        $installDir = '/var/docker';

        $environment = $this->createEnvironment();
        $projectName = "{$environment->getType()}_{$environment->getName()}";
        $process = $this->prophesize(Process::class);

        $applicationContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $applicationContext
            ->getProjectName()
            ->shouldBeCalledOnce()
            ->willReturn($projectName)
        ;

        $process
            ->isSuccessful()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $processFactory
            ->runForegroundProcessFromShellCommandLine(Argument::containingString('< /path/to/dump_file.sql'), Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $docker = new Docker($applicationContext->reveal(), $processFactory->reveal(), $installDir);
        static::assertTrue($docker->restoreMysqlDatabase('username', 'password', '/path/to/dump_file.sql'));
    }

    public function testItDumpsPostgresDatabase(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $processFactory = $this->prophesize(ProcessFactory::class);
        $installDir = '/var/docker';

        $environment = $this->createEnvironment();
        $projectName = "{$environment->getType()}_{$environment->getName()}";
        $process = $this->prophesize(Process::class);

        $applicationContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $applicationContext
            ->getProjectName()
            ->shouldBeCalledOnce()
            ->willReturn($projectName)
        ;

        $process
            ->isSuccessful()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $processFactory
            ->runForegroundProcessFromShellCommandLine(Argument::containingString('> /path/to/dump_file.sql'), Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $docker = new Docker($applicationContext->reveal(), $processFactory->reveal(), $installDir);
        static::assertTrue($docker->dumpPostgresDatabase('username', 'password', '/path/to/dump_file.sql'));
    }

    public function testItRestoresPostgresDatabase(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $processFactory = $this->prophesize(ProcessFactory::class);
        $installDir = '/var/docker';

        $environment = $this->createEnvironment();
        $projectName = "{$environment->getType()}_{$environment->getName()}";
        $process = $this->prophesize(Process::class);

        $applicationContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $applicationContext
            ->getProjectName()
            ->shouldBeCalledOnce()
            ->willReturn($projectName)
        ;

        $process
            ->isSuccessful()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $processFactory
            ->runForegroundProcessFromShellCommandLine(Argument::containingString('< /path/to/dump_file.sql'), Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $docker = new Docker($applicationContext->reveal(), $processFactory->reveal(), $installDir);
        static::assertTrue($docker->restorePostgresDatabase('username', 'password', '/path/to/dump_file.sql'));
    }
}
