<?php

declare(strict_types=1);

namespace App\Tests\Service\Middleware\Binary;

use App\Service\ApplicationContext;
use App\Service\Middleware\Binary\Mutagen;
use App\Service\Wrapper\ProcessFactory;
use App\Tests\TestEnvironmentTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @covers \App\Service\Middleware\Binary\Mutagen
 */
final class MutagenTest extends TestCase
{
    use ProphecyTrait;
    use TestEnvironmentTrait;

    public function testItRetrievesBinaryVersion(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $processFactory = $this->prophesize(ProcessFactory::class);

        $process = $this->prophesize(Process::class);
        $process->getOutput()
            ->shouldBeCalledOnce()
            ->willReturn('mutagen version')
        ;

        $processFactory->runBackgroundProcess(Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn($process)
        ;

        $mutagen = new Mutagen($applicationContext->reveal(), $processFactory->reveal());
        static::assertSame('mutagen version', $mutagen->getVersion());
    }

    public function testItStartsSynchronizationSession(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $processFactory = $this->prophesize(ProcessFactory::class);

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
            ->shouldBeCalled()
            ->willReturn($projectName)
        ;

        $process
            ->isSuccessful()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $processFactory
            ->runForegroundProcess(Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $mutagen = new Mutagen($applicationContext->reveal(), $processFactory->reveal());
        static::assertTrue($mutagen->startDockerSynchronization());
    }

    public function testItStopsSynchronizationSession(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $processFactory = $this->prophesize(ProcessFactory::class);

        $environment = $this->createEnvironment();
        $projectName = "{$environment->getType()}_{$environment->getName()}";
        $process = $this->prophesize(Process::class);

        $applicationContext
            ->getProjectName()
            ->shouldBeCalled()
            ->willReturn($projectName)
        ;

        $process
            ->isSuccessful()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $processFactory
            ->runForegroundProcess(Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $mutagen = new Mutagen($applicationContext->reveal(), $processFactory->reveal());
        static::assertTrue($mutagen->removeDockerSynchronization());
    }

    public function testItRemovesSynchronizationSession(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $processFactory = $this->prophesize(ProcessFactory::class);

        $environment = $this->createEnvironment();
        $projectName = "{$environment->getType()}_{$environment->getName()}";
        $process = $this->prophesize(Process::class);

        $applicationContext
            ->getProjectName()
            ->shouldBeCalled()
            ->willReturn($projectName)
        ;

        $process
            ->isSuccessful()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $processFactory
            ->runForegroundProcess(Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $mutagen = new Mutagen($applicationContext->reveal(), $processFactory->reveal());
        static::assertTrue($mutagen->removeDockerSynchronization());
    }
}
