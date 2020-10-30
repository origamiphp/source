<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Helper\ProcessFactory;
use App\Tests\CustomProphecyTrait;
use App\Tests\TestLocationTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @covers \App\Middleware\Binary\DockerCompose
 */
final class DockerComposeLogsTest extends WebTestCase
{
    use CustomProphecyTrait;
    use TestDockerComposeTrait;
    use TestLocationTrait;

    public function testItShowServicesLogsWithDefaultArguments(): void
    {
        $command = ['mutagen', 'compose', 'logs', '--follow', '--tail=0'];
        $dockerCompose = $this->prepareForegroundCommand($command);

        static::assertTrue($dockerCompose->showServicesLogs());
    }

    public function testItShowServicesLogsWithSpecificService(): void
    {
        $command = ['mutagen', 'compose', 'logs', '--follow', '--tail=0', 'php'];
        $dockerCompose = $this->prepareForegroundCommand($command);

        static::assertTrue($dockerCompose->showServicesLogs(0, 'php'));
    }

    public function testItShowServicesLogsWithSpecificTail(): void
    {
        $command = ['mutagen', 'compose', 'logs', '--follow', '--tail=42'];
        $dockerCompose = $this->prepareForegroundCommand($command);

        static::assertTrue($dockerCompose->showServicesLogs(42));
    }

    public function testItShowServicesLogsWithSpecificServiceAndTail(): void
    {
        $command = ['mutagen', 'compose', 'logs', '--follow', '--tail=42', 'php'];
        $dockerCompose = $this->prepareForegroundCommand($command);

        static::assertTrue($dockerCompose->showServicesLogs(42, 'php'));
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
