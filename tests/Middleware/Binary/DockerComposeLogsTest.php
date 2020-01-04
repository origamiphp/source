<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Middleware\Binary\DockerCompose;
use App\Tests\TestDockerComposeTrait;
use App\Tests\TestLocationTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @covers \App\Middleware\Binary\DockerCompose
 */
final class DockerComposeLogsTest extends TestCase
{
    use TestDockerComposeTrait;
    use TestLocationTrait;

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->removeLocation();
    }

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
