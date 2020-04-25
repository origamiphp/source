<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Exception\InvalidEnvironmentException;
use App\Middleware\Binary\DockerCompose;
use Prophecy\Prophecy\MethodProphecy;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @covers \App\Middleware\Binary\DockerCompose
 */
final class DockerComposeTerminalTest extends AbstractDockerComposeTestCase
{
    /**
     * @throws InvalidEnvironmentException
     */
    public function testItFixesPermissionsOnSharedSSHAgent(): void
    {
        $this->prophesizeSuccessfulValidations();
        $process = $this->prophet->prophesize(Process::class);
        $environmentVariables = $this->getFakeEnvironmentVariables();

        (new MethodProphecy($process, 'isSuccessful', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        (new MethodProphecy($this->processFactory, 'runForegroundProcess', [['docker-compose', 'exec', 'php', 'sh', '-c', 'chown www-data:www-data /run/host-services/ssh-auth.sock'], $environmentVariables]))
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $dockerCompose = new DockerCompose($this->validator->reveal(), $this->processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        static::assertTrue($dockerCompose->fixPermissionsOnSharedSSHAgent());
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItOpensTerminalOnGivenServiceWithSpecificUser(): void
    {
        $this->prophesizeSuccessfulValidations();
        $process = $this->prophet->prophesize(Process::class);
        $environmentVariables = $this->getFakeEnvironmentVariables();

        (new MethodProphecy($process, 'isSuccessful', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        (new MethodProphecy($this->processFactory, 'runForegroundProcess', [['docker-compose', 'exec', '-u', 'www-data:www-data', 'php', 'sh', '-l'], $environmentVariables]))
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $dockerCompose = new DockerCompose($this->validator->reveal(), $this->processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        static::assertTrue($dockerCompose->openTerminal('php', 'www-data:www-data'));
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItOpensTerminalOnGivenServiceWithoutSpecificUser(): void
    {
        $this->prophesizeSuccessfulValidations();
        $process = $this->prophet->prophesize(Process::class);
        $environmentVariables = $this->getFakeEnvironmentVariables();

        (new MethodProphecy($process, 'isSuccessful', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        (new MethodProphecy($this->processFactory, 'runForegroundProcess', [['docker-compose', 'exec', 'php', 'sh', '-l'], $environmentVariables]))
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $dockerCompose = new DockerCompose($this->validator->reveal(), $this->processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        static::assertTrue($dockerCompose->openTerminal('php'));
    }
}
