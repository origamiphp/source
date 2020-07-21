<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Helper\ProcessFactory;
use App\Middleware\Binary\DockerCompose;
use Prophecy\Argument;
use Symfony\Component\Process\Process;

trait TestDockerComposeTrait
{
    /**
     * Prepares the common instructions needed by foreground commands.
     */
    protected function prepareForegroundCommand(array $command): DockerCompose
    {
        $environment = $this->createEnvironment();

        $processFactory = $this->prophesize(ProcessFactory::class);
        $process = $this->prophesize(Process::class);

        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $processFactory->runForegroundProcess($command, Argument::type('array'))->shouldBeCalledOnce()->willReturn($process->reveal());

        $dockerCompose = new DockerCompose($processFactory->reveal());
        $dockerCompose->refreshEnvironmentVariables($environment);

        return $dockerCompose;
    }

    /**
     * Prepares the common instructions needed by complex foreground commands.
     */
    protected function prepareForegroundFromShellCommand(string $command): DockerCompose
    {
        $environment = $this->createEnvironment();

        $processFactory = $this->prophesize(ProcessFactory::class);
        $process = $this->prophesize(Process::class);

        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $processFactory->runForegroundProcessFromShellCommandLine($command, Argument::type('array'))->shouldBeCalledOnce()->willReturn($process->reveal());

        $dockerCompose = new DockerCompose($processFactory->reveal());
        $dockerCompose->refreshEnvironmentVariables($environment);

        return $dockerCompose;
    }
}
