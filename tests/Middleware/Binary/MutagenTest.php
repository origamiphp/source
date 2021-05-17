<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Helper\CurrentContext;
use App\Helper\ProcessFactory;
use App\Middleware\Binary\Mutagen;
use App\Tests\TestEnvironmentTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @covers \App\Middleware\Binary\Mutagen
 */
final class MutagenTest extends TestCase
{
    use ProphecyTrait;
    use TestEnvironmentTrait;

    public function testItStartsSynchronizationSession(): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $processFactory = $this->prophesize(ProcessFactory::class);

        $environment = $this->createEnvironment();
        $projectName = "{$environment->getType()}_{$environment->getName()}";
        $process = $this->prophesize(Process::class);

        $currentContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $currentContext
            ->getProjectName()
            ->shouldBeCalled()
            ->willReturn($projectName)
        ;

        $process
            ->isSuccessful()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $process
            ->getOutput()
            ->shouldBeCalledOnce()
            ->willReturn('')
        ;

        $command = ['mutagen', 'sync', 'list', "--label-selector=name={$projectName}"];
        $processFactory
            ->runBackgroundProcess($command)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $command = [
            'mutagen',
            'sync',
            'create',
            '--default-owner-beta=id:1000',
            '--default-group-beta=id:1000',
            '--sync-mode=two-way-resolved',
            '--ignore-vcs',
            '--symlink-mode=posix-raw',
            "--label=name={$projectName}",
            $environment->getLocation(),
            "docker://{$projectName}_synchro/var/www/html/",
        ];
        $processFactory
            ->runForegroundProcess($command)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $mutagen = new Mutagen($currentContext->reveal(), $processFactory->reveal());
        static::assertTrue($mutagen->startDockerSynchronization());
    }

    public function testItResumesSynchronizationSession(): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $processFactory = $this->prophesize(ProcessFactory::class);

        $environment = $this->createEnvironment();
        $projectName = "{$environment->getType()}_{$environment->getName()}";
        $process = $this->prophesize(Process::class);

        $currentContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $currentContext
            ->getProjectName()
            ->shouldBeCalled()
            ->willReturn($projectName)
        ;

        $process
            ->isSuccessful()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $process
            ->getOutput()
            ->shouldBeCalledOnce()
            ->willReturn($projectName)
        ;

        $command = ['mutagen', 'sync', 'list', "--label-selector=name={$projectName}"];
        $processFactory
            ->runBackgroundProcess($command)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $command = ['mutagen', 'sync', 'resume', "--label-selector=name={$projectName}"];
        $processFactory
            ->runForegroundProcess($command)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $mutagen = new Mutagen($currentContext->reveal(), $processFactory->reveal());
        static::assertTrue($mutagen->startDockerSynchronization());
    }

    public function testItStopsSynchronizationSession(): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $processFactory = $this->prophesize(ProcessFactory::class);

        $environment = $this->createEnvironment();
        $projectName = "{$environment->getType()}_{$environment->getName()}";
        $process = $this->prophesize(Process::class);

        $currentContext
            ->getProjectName()
            ->shouldBeCalled()
            ->willReturn($projectName)
        ;

        $process
            ->isSuccessful()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $command = ['mutagen', 'sync', 'pause', "--label-selector=name={$projectName}"];
        $processFactory
            ->runForegroundProcess($command)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $mutagen = new Mutagen($currentContext->reveal(), $processFactory->reveal());
        static::assertTrue($mutagen->stopDockerSynchronization());
    }

    public function testItRemovesSynchronizationSession(): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $processFactory = $this->prophesize(ProcessFactory::class);

        $environment = $this->createEnvironment();
        $projectName = "{$environment->getType()}_{$environment->getName()}";
        $process = $this->prophesize(Process::class);

        $currentContext
            ->getProjectName()
            ->shouldBeCalled()
            ->willReturn($projectName)
        ;

        $process
            ->isSuccessful()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $command = ['mutagen', 'sync', 'terminate', "--label-selector=name={$projectName}"];
        $processFactory
            ->runForegroundProcess($command)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $mutagen = new Mutagen($currentContext->reveal(), $processFactory->reveal());
        static::assertTrue($mutagen->removeDockerSynchronization());
    }
}
