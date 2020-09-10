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
        [$processWithDocker, $processWithDockerCompose, $processWithMutagen, $processFactory] = $this->getProcessProphecies();

        $processWithDocker->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $processFactory->runBackgroundProcess(['which', 'docker'])->shouldBeCalledOnce()->willReturn($processWithDocker->reveal());

        $processWithDockerCompose->isSuccessful()->shouldBeCalledOnce()->willReturn(false);
        $processFactory->runBackgroundProcess(['which', 'docker-compose'])->shouldBeCalledOnce()->willReturn($processWithDockerCompose->reveal());

        $processWithMutagen->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $processWithMutagen->getOutput()->shouldBeCalledOnce()->willReturn('0.12.0');
        $processFactory->runBackgroundProcess(['mutagen', 'version'])->shouldBeCalledOnce()->willReturn($processWithMutagen->reveal());

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
                'status' => false,
            ],
            [
                'name' => 'mutagen',
                'description' => 'Fast and efficient way to synchronize code to Docker containers.',
                'status' => true,
            ],
        ], $requirementsChecker->checkMandatoryRequirements());
    }

    public function testItDetectsWrongMutagenVersion(): void
    {
        [$processWithDocker, $processWithDockerCompose, $processWithMutagen, $processFactory] = $this->getProcessProphecies();

        $processWithDocker->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $processFactory->runBackgroundProcess(['which', 'docker'])->shouldBeCalledOnce()->willReturn($processWithDocker->reveal());

        $processWithDockerCompose->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $processFactory->runBackgroundProcess(['which', 'docker-compose'])->shouldBeCalledOnce()->willReturn($processWithDockerCompose->reveal());

        $processWithMutagen->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $processWithMutagen->getOutput()->shouldBeCalledOnce()->willReturn('0.10.0');
        $processFactory->runBackgroundProcess(['mutagen', 'version'])->shouldBeCalledOnce()->willReturn($processWithMutagen->reveal());

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
            [
                'name' => 'mutagen',
                'description' => 'Fast and efficient way to synchronize code to Docker containers.',
                'status' => false,
            ],
        ], $requirementsChecker->checkMandatoryRequirements());
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

    /**
     * Retrieves the prophecies used to test \App\Environment\EnvironmentMaker\RequirementsChecker::checkMandatoryRequirements.
     */
    private function getProcessProphecies(): array
    {
        return [
            $this->prophesize(Process::class),
            $this->prophesize(Process::class),
            $this->prophesize(Process::class),
            $this->prophesize(ProcessFactory::class),
        ];
    }
}
