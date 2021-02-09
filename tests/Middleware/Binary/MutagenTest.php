<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Helper\ProcessFactory;
use App\Middleware\Binary\Mutagen;
use App\Tests\CustomProphecyTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @covers \App\Middleware\Binary\Mutagen
 */
final class MutagenTest extends TestCase
{
    use CustomProphecyTrait;

    public function testItStartsSynchronizationSession(): void
    {
        [$processFactory] = $this->prophesizeObjectArguments();

        $environmentVariables = ['COMPOSE_PROJECT_NAME' => 'type_project', 'PROJECT_LOCATION' => 'project_location'];

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $process->getOutput()->shouldBeCalledOnce()->willReturn('');

        $command = ['mutagen', 'list', sprintf('--label-selector=name=%s', $environmentVariables['COMPOSE_PROJECT_NAME'])];
        $processFactory->runBackgroundProcess($command, $environmentVariables)->shouldBeCalledOnce()->willReturn($process->reveal());

        $command = [
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
        ];
        $processFactory->runForegroundProcess($command, $environmentVariables)->shouldBeCalledOnce()->willReturn($process->reveal());

        $mutagen = new Mutagen($processFactory->reveal());
        static::assertTrue($mutagen->startDockerSynchronization($environmentVariables));
    }

    public function testItResumesSynchronizationSession(): void
    {
        [$processFactory] = $this->prophesizeObjectArguments();

        $environmentVariables = ['COMPOSE_PROJECT_NAME' => 'type_project', 'PROJECT_LOCATION' => 'project_location'];

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $process->getOutput()->shouldBeCalledOnce()->willReturn('type_project');

        $command = ['mutagen', 'list', sprintf('--label-selector=name=%s', $environmentVariables['COMPOSE_PROJECT_NAME'])];
        $processFactory->runBackgroundProcess($command, $environmentVariables)->shouldBeCalledOnce()->willReturn($process->reveal());

        $command = ['mutagen', 'resume', sprintf('--label-selector=name=%s', $environmentVariables['COMPOSE_PROJECT_NAME'])];
        $processFactory->runForegroundProcess($command, $environmentVariables)->shouldBeCalledOnce()->willReturn($process->reveal());

        $mutagen = new Mutagen($processFactory->reveal());
        static::assertTrue($mutagen->startDockerSynchronization($environmentVariables));
    }

    public function testItStopsSynchronizationSession(): void
    {
        [$processFactory] = $this->prophesizeObjectArguments();

        $environmentVariables = ['COMPOSE_PROJECT_NAME' => 'type_project'];

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);

        $command = ['mutagen', 'pause', sprintf('--label-selector=name=%s', $environmentVariables['COMPOSE_PROJECT_NAME'])];
        $processFactory->runForegroundProcess($command, $environmentVariables)->shouldBeCalledOnce()->willReturn($process->reveal());

        $mutagen = new Mutagen($processFactory->reveal());
        static::assertTrue($mutagen->stopDockerSynchronization($environmentVariables));
    }

    public function testItRemovesSynchronizationSession(): void
    {
        [$processFactory] = $this->prophesizeObjectArguments();

        $environmentVariables = ['COMPOSE_PROJECT_NAME' => 'type_project'];

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);

        $command = ['mutagen', 'terminate', sprintf('--label-selector=name=%s', $environmentVariables['COMPOSE_PROJECT_NAME'])];
        $processFactory->runForegroundProcess($command, $environmentVariables)->shouldBeCalledOnce()->willReturn($process->reveal());

        $mutagen = new Mutagen($processFactory->reveal());
        static::assertTrue($mutagen->removeDockerSynchronization($environmentVariables));
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
