<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Helper\ProcessFactory;
use App\Helper\RequirementsChecker;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophet;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @covers \App\Helper\RequirementsChecker
 */
final class RequirementsCheckerTest extends TestCase
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

    public function testItDetectsMandatoryBinaryStatus(): void
    {
        $processFactory = $this->prophet->prophesize(ProcessFactory::class);

        $processWithDocker = $this->prophet->prophesize(Process::class);
        (new MethodProphecy($processWithDocker, 'isSuccessful', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;
        (new MethodProphecy($processFactory, 'runBackgroundProcess', [['which', 'docker']]))
            ->shouldBeCalledOnce()
            ->willReturn($processWithDocker->reveal())
        ;

        $processWithDockerCompose = $this->prophet->prophesize(Process::class);
        (new MethodProphecy($processWithDockerCompose, 'isSuccessful', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;
        (new MethodProphecy($processFactory, 'runBackgroundProcess', [['which', 'docker-compose']]))
            ->shouldBeCalledOnce()
            ->willReturn($processWithDockerCompose->reveal())
        ;

        $requirementsChecker = new RequirementsChecker($processFactory->reveal());
        static::assertSame([
            [
                'name' => 'docker',
                'description' => 'A self-sufficient runtime for containers.',
                'status' => true,
            ],
            [
                'name' => 'docker-compose',
                'description' => 'Define and run multi-container applications with Docker.',
                'status' => true,
            ],
        ], $requirementsChecker->checkMandatoryRequirements());
    }

    public function testItDetectsNonMandatoryBinaryStatus(): void
    {
        $processFactory = $this->prophet->prophesize(ProcessFactory::class);

        $processWithMutagen = $this->prophet->prophesize(Process::class);
        (new MethodProphecy($processWithMutagen, 'isSuccessful', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;
        (new MethodProphecy($processFactory, 'runBackgroundProcess', [['which', 'mutagen']]))
            ->shouldBeCalledOnce()
            ->willReturn($processWithMutagen->reveal())
        ;

        $processWithMkcert = $this->prophet->prophesize(Process::class);
        (new MethodProphecy($processWithMkcert, 'isSuccessful', []))
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;
        (new MethodProphecy($processFactory, 'runBackgroundProcess', [['which', 'mkcert']]))
            ->shouldBeCalledOnce()
            ->willReturn($processWithMkcert->reveal())
        ;

        $requirementsChecker = new RequirementsChecker($processFactory->reveal());
        static::assertSame([
            [
                'name' => 'mutagen',
                'description' => 'Fast and efficient way to synchronize code to Docker containers.',
                'status' => true,
            ],
            [
                'name' => 'mkcert',
                'description' => 'A simple zero-config tool to make locally trusted development certificates.',
                'status' => false,
            ],
        ], $requirementsChecker->checkNonMandatoryRequirements());
    }

    public function testItDetectsPerformanceBinaryFoundStatus(): void
    {
        $processFactory = $this->prophet->prophesize(ProcessFactory::class);

        $process = $this->prophet->prophesize(Process::class);
        (new MethodProphecy($process, 'isSuccessful', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;
        (new MethodProphecy($processFactory, 'runBackgroundProcess', [['which', 'mutagen']]))
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $requirementsChecker = new RequirementsChecker($processFactory->reveal());
        static::assertTrue($requirementsChecker->canOptimizeSynchronizationPerformance());
    }

    public function testItDetectsPerformanceBinaryNotFoundStatus(): void
    {
        $processFactory = $this->prophet->prophesize(ProcessFactory::class);

        $process = $this->prophet->prophesize(Process::class);
        (new MethodProphecy($process, 'isSuccessful', []))
            ->shouldBeCalled()
            ->willReturn(false)
        ;
        (new MethodProphecy($processFactory, 'runBackgroundProcess', [['which', 'mutagen']]))
            ->shouldBeCalled()
            ->willReturn($process->reveal())
        ;

        $requirementsChecker = new RequirementsChecker($processFactory->reveal());
        static::assertFalse($requirementsChecker->canOptimizeSynchronizationPerformance());
    }

    public function testItDetectsCertificatesBinaryFoundStatus(): void
    {
        $processFactory = $this->prophet->prophesize(ProcessFactory::class);

        $process = $this->prophet->prophesize(Process::class);
        (new MethodProphecy($process, 'isSuccessful', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;
        (new MethodProphecy($processFactory, 'runBackgroundProcess', [['which', 'mkcert']]))
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $requirementsChecker = new RequirementsChecker($processFactory->reveal());
        static::assertTrue($requirementsChecker->canMakeLocallyTrustedCertificates());
    }

    public function testItDetectsCertificatesBinaryNotFoundStatus(): void
    {
        $processFactory = $this->prophet->prophesize(ProcessFactory::class);

        $process = $this->prophet->prophesize(Process::class);
        (new MethodProphecy($process, 'isSuccessful', []))
            ->shouldBeCalled()
            ->willReturn(false)
        ;
        (new MethodProphecy($processFactory, 'runBackgroundProcess', [['which', 'mkcert']]))
            ->shouldBeCalled()
            ->willReturn($process->reveal())
        ;

        $requirementsChecker = new RequirementsChecker($processFactory->reveal());
        static::assertFalse($requirementsChecker->canMakeLocallyTrustedCertificates());
    }
}
