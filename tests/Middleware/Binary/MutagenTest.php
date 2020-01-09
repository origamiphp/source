<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Helper\ProcessFactory;
use App\Middleware\Binary\Mutagen;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @covers \App\Middleware\Binary\Mutagen
 */
final class MutagenTest extends TestCase
{
    public function testItStartsSynchronizationSession(): void
    {
        $environmentVariables = ['COMPOSE_PROJECT_NAME' => 'type_project', 'PROJECT_LOCATION' => 'project_location'];

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $process->getOutput()->shouldBeCalledOnce()->willReturn('No sessions found');

        $processFactory = $this->prophesize(ProcessFactory::class);
        $processFactory->runBackgroundProcess(
            ['mutagen', 'list', sprintf('--label-selector=name=%s', $environmentVariables['COMPOSE_PROJECT_NAME'])],
            $environmentVariables
        )
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;
        $processFactory->runForegroundProcess(
            [
                'mutagen',
                'create',
                '--default-owner-beta=id:1000',
                '--default-group-beta=id:1000',
                '--sync-mode=two-way-resolved',
                '--ignore-vcs',
                '--symlink-mode=posix-raw',
                '--label=name=type_project',
                'project_location',
                'docker://type_project_synchro/var/www/html/',
            ],
            $environmentVariables
        )
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $mutagen = new Mutagen($processFactory->reveal());
        static::assertTrue($mutagen->startDockerSynchronization($environmentVariables));
    }

    public function testItResumesSynchronizationSession(): void
    {
        $environmentVariables = ['COMPOSE_PROJECT_NAME' => 'type_project', 'PROJECT_LOCATION' => 'project_location'];

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $process->getOutput()->shouldBeCalledOnce()->willReturn('azerty');

        $processFactory = $this->prophesize(ProcessFactory::class);
        $processFactory->runBackgroundProcess(
            ['mutagen', 'list', sprintf('--label-selector=name=%s', $environmentVariables['COMPOSE_PROJECT_NAME'])],
            $environmentVariables
        )
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;
        $processFactory->runForegroundProcess(
            ['mutagen', 'resume', sprintf('--label-selector=name=%s', $environmentVariables['COMPOSE_PROJECT_NAME'])],
            $environmentVariables
        )
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $mutagen = new Mutagen($processFactory->reveal());
        static::assertTrue($mutagen->startDockerSynchronization($environmentVariables));
    }

    public function testItStopsSynchronizationSession(): void
    {
        $environmentVariables = ['COMPOSE_PROJECT_NAME' => 'type_project'];

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);

        $processFactory = $this->prophesize(ProcessFactory::class);
        $processFactory->runForegroundProcess(
            ['mutagen', 'pause', sprintf('--label-selector=name=%s', $environmentVariables['COMPOSE_PROJECT_NAME'])],
            $environmentVariables
        )
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $mutagen = new Mutagen($processFactory->reveal());
        static::assertTrue($mutagen->stopDockerSynchronization($environmentVariables));
    }

    public function testItRemovesSynchronizationSession(): void
    {
        $environmentVariables = ['COMPOSE_PROJECT_NAME' => 'type_project'];

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);

        $processFactory = $this->prophesize(ProcessFactory::class);
        $processFactory->runForegroundProcess(
            ['mutagen', 'terminate', sprintf('--label-selector=name=%s', $environmentVariables['COMPOSE_PROJECT_NAME'])],
            $environmentVariables
        )
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $mutagen = new Mutagen($processFactory->reveal());
        static::assertTrue($mutagen->removeDockerSynchronization($environmentVariables));
    }
}
