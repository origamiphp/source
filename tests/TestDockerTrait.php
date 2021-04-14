<?php

declare(strict_types=1);

namespace App\Tests;

use App\Middleware\Binary\Docker;
use Prophecy\Argument;
use Symfony\Component\Process\Process;

trait TestDockerTrait
{
    /**
     * Prepares the common instructions needed by foreground commands.
     */
    protected function prepareForegroundCommand(array $command): Docker
    {
        [$processFactory] = $this->prophesizeObjectArguments();
        $process = $this->prophesize(Process::class);

        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $processFactory->runForegroundProcess($command, Argument::type('array'))->shouldBeCalledOnce()->willReturn($process->reveal());

        $docker = new Docker($processFactory->reveal());
        $docker->refreshEnvironmentVariables($this->createEnvironment());

        return $docker;
    }

    /**
     * Prepares the common instructions needed by complex foreground commands.
     */
    protected function prepareForegroundFromShellCommand(string $command): Docker
    {
        [$processFactory] = $this->prophesizeObjectArguments();
        $process = $this->prophesize(Process::class);

        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $processFactory->runForegroundProcessFromShellCommandLine($command, Argument::type('array'))->shouldBeCalledOnce()->willReturn($process->reveal());

        $docker = new Docker($processFactory->reveal());
        $docker->refreshEnvironmentVariables($this->createEnvironment());

        return $docker;
    }
}
