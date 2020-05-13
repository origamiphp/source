<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Helper\ProcessFactory;
use App\Middleware\Binary\Mutagen;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophet;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @covers \App\Middleware\Binary\Mutagen
 */
final class MutagenTest extends TestCase
{
    /** @var Prophet */
    private $prophet;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prophet = new Prophet();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->prophet->checkPredictions();
    }

    public function testItStartsSynchronizationSession(): void
    {
        $environmentVariables = ['COMPOSE_PROJECT_NAME' => 'type_project', 'PROJECT_LOCATION' => 'project_location'];
        $process = $this->prophet->prophesize(Process::class);
        $processFactory = $this->prophet->prophesize(ProcessFactory::class);

        (new MethodProphecy($process, 'isSuccessful', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        (new MethodProphecy($process, 'getOutput', []))
            ->shouldBeCalledOnce()
            ->willReturn('No sessions found')
        ;

        $command = ['mutagen', 'list', sprintf('--label-selector=name=%s', $environmentVariables['COMPOSE_PROJECT_NAME'])];
        (new MethodProphecy($processFactory, 'runBackgroundProcess', [$command, $environmentVariables]))
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

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
        (new MethodProphecy($processFactory, 'runForegroundProcess', [$command, $environmentVariables]))
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $mutagen = new Mutagen($processFactory->reveal());
        static::assertTrue($mutagen->startDockerSynchronization($environmentVariables));
    }

    public function testItResumesSynchronizationSession(): void
    {
        $environmentVariables = ['COMPOSE_PROJECT_NAME' => 'type_project', 'PROJECT_LOCATION' => 'project_location'];
        $process = $this->prophet->prophesize(Process::class);
        $processFactory = $this->prophet->prophesize(ProcessFactory::class);

        (new MethodProphecy($process, 'isSuccessful', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        (new MethodProphecy($process, 'getOutput', []))
            ->shouldBeCalledOnce()
            ->willReturn('azerty')
        ;

        $command = ['mutagen', 'list', sprintf('--label-selector=name=%s', $environmentVariables['COMPOSE_PROJECT_NAME'])];
        (new MethodProphecy($processFactory, 'runBackgroundProcess', [$command, $environmentVariables]))
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $command = ['mutagen', 'resume', sprintf('--label-selector=name=%s', $environmentVariables['COMPOSE_PROJECT_NAME'])];
        (new MethodProphecy($processFactory, 'runForegroundProcess', [$command, $environmentVariables]))
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $mutagen = new Mutagen($processFactory->reveal());
        static::assertTrue($mutagen->startDockerSynchronization($environmentVariables));
    }

    public function testItStopsSynchronizationSession(): void
    {
        $environmentVariables = ['COMPOSE_PROJECT_NAME' => 'type_project'];
        $process = $this->prophet->prophesize(Process::class);
        $processFactory = $this->prophet->prophesize(ProcessFactory::class);

        (new MethodProphecy($process, 'isSuccessful', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $command = ['mutagen', 'pause', sprintf('--label-selector=name=%s', $environmentVariables['COMPOSE_PROJECT_NAME'])];
        (new MethodProphecy($processFactory, 'runForegroundProcess', [$command, $environmentVariables]))
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $mutagen = new Mutagen($processFactory->reveal());
        static::assertTrue($mutagen->stopDockerSynchronization($environmentVariables));
    }

    public function testItRemovesSynchronizationSession(): void
    {
        $environmentVariables = ['COMPOSE_PROJECT_NAME' => 'type_project'];
        $process = $this->prophet->prophesize(Process::class);
        $processFactory = $this->prophet->prophesize(ProcessFactory::class);

        (new MethodProphecy($process, 'isSuccessful', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $command = ['mutagen', 'terminate', sprintf('--label-selector=name=%s', $environmentVariables['COMPOSE_PROJECT_NAME'])];
        (new MethodProphecy($processFactory, 'runForegroundProcess', [$command, $environmentVariables]))
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $mutagen = new Mutagen($processFactory->reveal());
        static::assertTrue($mutagen->removeDockerSynchronization($environmentVariables));
    }
}
