<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Exception\InvalidEnvironmentException;
use App\Middleware\Binary\DockerCompose;
use App\Tests\AbstractDockerComposeTestCase;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @covers \App\Middleware\Binary\DockerCompose
 */
final class DockerComposeLogsTest extends AbstractDockerComposeTestCase
{
    /**
     * @throws InvalidEnvironmentException
     */
    public function testItShowServicesLogsWithDefaultArguments(): void
    {
        $this->prophesizeSuccessfulValidations();

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);

        $environmentVariables = $this->getFakeEnvironmentVariables();

        $this->processFactory->runForegroundProcess(['docker-compose', 'logs', '--follow', '--tail=0'], $environmentVariables)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $dockerCompose = new DockerCompose($this->validator->reveal(), $this->processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        static::assertTrue($dockerCompose->showServicesLogs());
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItShowServicesLogsWithSpecificService(): void
    {
        $this->prophesizeSuccessfulValidations();

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);

        $environmentVariables = $this->getFakeEnvironmentVariables();

        $this->processFactory->runForegroundProcess(['docker-compose', 'logs', '--follow', '--tail=0', 'php'], $environmentVariables)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $dockerCompose = new DockerCompose($this->validator->reveal(), $this->processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        static::assertTrue($dockerCompose->showServicesLogs(0, 'php'));
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItShowServicesLogsWithSpecificTail(): void
    {
        $this->prophesizeSuccessfulValidations();

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);

        $environmentVariables = $this->getFakeEnvironmentVariables();

        $this->processFactory->runForegroundProcess(['docker-compose', 'logs', '--follow', '--tail=42'], $environmentVariables)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $dockerCompose = new DockerCompose($this->validator->reveal(), $this->processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        static::assertTrue($dockerCompose->showServicesLogs(42));
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItShowServicesLogsWithSpecificServiceAndTail(): void
    {
        $this->prophesizeSuccessfulValidations();

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);

        $environmentVariables = $this->getFakeEnvironmentVariables();

        $this->processFactory->runForegroundProcess(['docker-compose', 'logs', '--follow', '--tail=42', 'php'], $environmentVariables)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $dockerCompose = new DockerCompose($this->validator->reveal(), $this->processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        static::assertTrue($dockerCompose->showServicesLogs(42, 'php'));
    }
}
