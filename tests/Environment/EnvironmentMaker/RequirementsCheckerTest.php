<?php

declare(strict_types=1);

namespace App\Tests\Environment\EnvironmentMaker;

use App\Environment\EnvironmentMaker\RequirementsChecker;
use App\Helper\ProcessFactory;
use App\Tests\CustomProphecyTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @covers \App\Environment\EnvironmentMaker\RequirementsChecker
 */
final class RequirementsCheckerTest extends TestCase
{
    use CustomProphecyTrait;

    public function testItDetectsMandatoryBinaryStatus(): void
    {
        [$processFactory] = $this->prophesizeObjectArguments();

        $processWithDocker = $this->prophesize(Process::class);
        $processWithDocker->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $processFactory->runBackgroundProcess(['which', 'docker'])->shouldBeCalledOnce()->willReturn($processWithDocker->reveal());

        $processWithDockerCompose = $this->prophesize(Process::class);
        $processWithDockerCompose->isSuccessful()->shouldBeCalledOnce()->willReturn(false);
        $processFactory->runBackgroundProcess(['which', 'docker-compose'])->shouldBeCalledOnce()->willReturn($processWithDockerCompose->reveal());

        $processWithMutagen = $this->prophesize(Process::class);
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
        [$processFactory] = $this->prophesizeObjectArguments();

        $processWithDocker = $this->prophesize(Process::class);
        $processWithDocker->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $processFactory->runBackgroundProcess(['which', 'docker'])->shouldBeCalledOnce()->willReturn($processWithDocker->reveal());

        $processWithDockerCompose = $this->prophesize(Process::class);
        $processWithDockerCompose->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $processFactory->runBackgroundProcess(['which', 'docker-compose'])->shouldBeCalledOnce()->willReturn($processWithDockerCompose->reveal());

        $processWithMutagen = $this->prophesize(Process::class);
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
        [$processFactory] = $this->prophesizeObjectArguments();

        $processWithMkcert = $this->prophesize(Process::class);
        $processWithMkcert->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $processFactory->runBackgroundProcess(['which', 'mkcert'])->shouldBeCalledOnce()->willReturn($processWithMkcert->reveal());

        $requirementsChecker = new RequirementsChecker($processFactory->reveal());
        static::assertSame([
            [
                'name' => 'mkcert',
                'description' => 'A simple zero-config tool to make locally trusted development certificates.',
                'status' => true,
            ],
        ], $requirementsChecker->checkNonMandatoryRequirements());
    }

    public function testItDetectsCertificatesBinaryNotFoundStatus(): void
    {
        [$processFactory] = $this->prophesizeObjectArguments();

        $processWithMkcert = $this->prophesize(Process::class);
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
