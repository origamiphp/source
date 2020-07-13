<?php

declare(strict_types=1);

namespace App\Tests\Environment\EnvironmentMaker;

use App\Environment\EnvironmentMaker\RequirementsChecker;
use App\Helper\ProcessFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @covers \App\Environment\EnvironmentMaker\RequirementsChecker
 */
final class RequirementsCheckerTest extends TestCase
{
    use ProphecyTrait;

    public function testItDetectsMandatoryBinaryStatus(): void
    {
        $processWithDocker = $this->prophesize(Process::class);
        $processWithDockerCompose = $this->prophesize(Process::class);
        $processFactory = $this->prophesize(ProcessFactory::class);

        $processWithDocker->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $processFactory->runBackgroundProcess(['which', 'docker'])->shouldBeCalledOnce()->willReturn($processWithDocker->reveal());

        $processWithDockerCompose->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $processFactory->runBackgroundProcess(['which', 'docker-compose'])->shouldBeCalledOnce()->willReturn($processWithDockerCompose->reveal());

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
        $processWithMkcert = $this->prophesize(Process::class);
        $processFactory = $this->prophesize(ProcessFactory::class);

        $processWithMkcert->isSuccessful()->shouldBeCalledOnce()->willReturn(false);
        $processFactory->runBackgroundProcess(['which', 'mkcert'])->shouldBeCalledOnce()->willReturn($processWithMkcert->reveal());

        $requirementsChecker = new RequirementsChecker($processFactory->reveal());
        static::assertSame([
            [
                'name' => 'mkcert',
                'description' => 'A simple zero-config tool to make locally trusted development certificates.',
                'status' => false,
            ],
        ], $requirementsChecker->checkNonMandatoryRequirements());
    }

    public function testItDetectsCertificatesBinaryFoundStatus(): void
    {
        $processWithMkcert = $this->prophesize(Process::class);
        $processFactory = $this->prophesize(ProcessFactory::class);

        $processWithMkcert->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $processFactory->runBackgroundProcess(['which', 'mkcert'])->shouldBeCalledOnce()->willReturn($processWithMkcert->reveal());

        $requirementsChecker = new RequirementsChecker($processFactory->reveal());
        static::assertTrue($requirementsChecker->canMakeLocallyTrustedCertificates());
    }

    public function testItDetectsCertificatesBinaryNotFoundStatus(): void
    {
        $processWithMkcert = $this->prophesize(Process::class);
        $processFactory = $this->prophesize(ProcessFactory::class);

        $processWithMkcert->isSuccessful()->shouldBeCalledOnce()->willReturn(false);
        $processFactory->runBackgroundProcess(['which', 'mkcert'])->shouldBeCalledOnce()->willReturn($processWithMkcert->reveal());

        $requirementsChecker = new RequirementsChecker($processFactory->reveal());
        static::assertFalse($requirementsChecker->canMakeLocallyTrustedCertificates());
    }
}
