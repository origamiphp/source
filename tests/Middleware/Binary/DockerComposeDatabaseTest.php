<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Helper\ProcessFactory;
use App\Middleware\Binary\DockerCompose;
use App\Tests\TestLocationTrait;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @covers \App\Middleware\Binary\DockerCompose
 */
final class DockerComposeDatabaseTest extends WebTestCase
{
    use ProphecyTrait;
    use TestDockerComposeTrait;
    use TestLocationTrait;

    public function testItBackupsTheDatabaseVolume(): void
    {
        $dockerCompose = $this->prophesizeCommonInstructions();
        static::assertTrue($dockerCompose->backupDatabaseVolume());
    }

    public function testItResetsTheDatabaseVolume(): void
    {
        $dockerCompose = $this->prophesizeCommonInstructions();
        static::assertTrue($dockerCompose->resetDatabaseVolume());
    }

    public function testItRestoresTheDatabaseVolume(): void
    {
        $dockerCompose = $this->prophesizeCommonInstructions();
        static::assertTrue($dockerCompose->restoreDatabaseVolume());
    }

    private function prophesizeCommonInstructions(): DockerCompose
    {
        $environment = $this->createEnvironment();
        $processFactory = $this->prophesize(ProcessFactory::class);

        $databaseContainerIdProcess = $this->prophesize(Process::class);
        $databaseContainerIdProcess->getOutput()->shouldBeCalledOnce()->willReturn(str_pad('', 64, 'x'));
        $processFactory->runBackgroundProcess(Argument::type('array'), Argument::type('array'))->shouldBeCalledOnce()->willReturn($databaseContainerIdProcess->reveal());

        $actionProcess = $this->prophesize(Process::class);
        $actionProcess->isSuccessful()->willReturn(true);
        $processFactory->runForegroundProcess(Argument::type('array'), Argument::type('array'))->shouldBeCalledOnce()->willReturn($actionProcess->reveal());

        $dockerCompose = new DockerCompose($processFactory->reveal());
        $dockerCompose->refreshEnvironmentVariables($environment);

        return $dockerCompose;
    }
}
