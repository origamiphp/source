<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Helper\ProcessFactory;
use App\Middleware\Binary\Docker;
use App\Tests\CustomProphecyTrait;
use App\Tests\TestDockerTrait;
use App\Tests\TestLocationTrait;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @covers \App\Middleware\Binary\Docker
 */
final class DockerDatabaseTest extends WebTestCase
{
    use CustomProphecyTrait;
    use TestDockerTrait;
    use TestLocationTrait;

    public function testItBackupsTheDatabaseVolume(): void
    {
        $docker = $this->prophesizeCommonInstructions();
        static::assertTrue($docker->backupDatabaseVolume());
    }

    public function testItResetsTheDatabaseVolume(): void
    {
        $docker = $this->prophesizeCommonInstructions();
        static::assertTrue($docker->resetDatabaseVolume());
    }

    public function testItRestoresTheDatabaseVolume(): void
    {
        $docker = $this->prophesizeCommonInstructions();
        static::assertTrue($docker->restoreDatabaseVolume());
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

    private function prophesizeCommonInstructions(): Docker
    {
        [$processFactory] = $this->prophesizeObjectArguments();

        $databaseContainerIdProcess = $this->prophesize(Process::class);
        $databaseContainerIdProcess->getOutput()->shouldBeCalledOnce()->willReturn(str_pad('', 64, 'x'));
        $processFactory->runBackgroundProcess(Argument::type('array'), Argument::type('array'))->shouldBeCalledOnce()->willReturn($databaseContainerIdProcess->reveal());

        $actionProcess = $this->prophesize(Process::class);
        $actionProcess->isSuccessful()->willReturn(true);
        $processFactory->runForegroundProcess(Argument::type('array'), Argument::type('array'))->shouldBeCalledOnce()->willReturn($actionProcess->reveal());

        $docker = new Docker($processFactory->reveal());
        $docker->refreshEnvironmentVariables($this->createEnvironment());

        return $docker;
    }
}
