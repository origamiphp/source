<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Helper\ProcessFactory;
use App\Tests\CustomProphecyTrait;
use App\Tests\TestDockerTrait;
use App\Tests\TestLocationTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @covers \App\Middleware\Binary\Docker
 */
final class DockerLogsTest extends WebTestCase
{
    use CustomProphecyTrait;
    use TestDockerTrait;
    use TestLocationTrait;

    public function testItShowServicesLogsWithDefaultArguments(): void
    {
        $command = ['docker', 'compose', 'logs', '--follow', '--tail=0'];
        $docker = $this->prepareForegroundCommand($command);

        static::assertTrue($docker->showServicesLogs());
    }

    public function testItShowServicesLogsWithSpecificService(): void
    {
        $command = ['docker', 'compose', 'logs', '--follow', '--tail=0', 'php'];
        $docker = $this->prepareForegroundCommand($command);

        static::assertTrue($docker->showServicesLogs(0, 'php'));
    }

    public function testItShowServicesLogsWithSpecificTail(): void
    {
        $command = ['docker', 'compose', 'logs', '--follow', '--tail=42'];
        $docker = $this->prepareForegroundCommand($command);

        static::assertTrue($docker->showServicesLogs(42));
    }

    public function testItShowServicesLogsWithSpecificServiceAndTail(): void
    {
        $command = ['docker', 'compose', 'logs', '--follow', '--tail=42', 'php'];
        $docker = $this->prepareForegroundCommand($command);

        static::assertTrue($docker->showServicesLogs(42, 'php'));
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
