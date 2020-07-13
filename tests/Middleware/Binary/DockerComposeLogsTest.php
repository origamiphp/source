<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Environment\EnvironmentEntity;
use App\Exception\InvalidConfigurationException;
use App\Tests\TestDockerComposeTrait;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @covers \App\Middleware\Binary\DockerCompose
 */
final class DockerComposeLogsTest extends WebTestCase
{
    use ProphecyTrait;
    use TestDockerComposeTrait;

    /** @var EnvironmentEntity */
    protected $environment;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->createLocation();
        $this->prepareLocation();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeLocation();
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function testItShowServicesLogsWithDefaultArguments(): void
    {
        $command = ['docker-compose', 'logs', '--follow', '--tail=0'];
        $dockerCompose = $this->prepareForegroundCommand($command);

        static::assertTrue($dockerCompose->showServicesLogs());
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function testItShowServicesLogsWithSpecificService(): void
    {
        $command = ['docker-compose', 'logs', '--follow', '--tail=0', 'php'];
        $dockerCompose = $this->prepareForegroundCommand($command);

        static::assertTrue($dockerCompose->showServicesLogs(0, 'php'));
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function testItShowServicesLogsWithSpecificTail(): void
    {
        $command = ['docker-compose', 'logs', '--follow', '--tail=42'];
        $dockerCompose = $this->prepareForegroundCommand($command);

        static::assertTrue($dockerCompose->showServicesLogs(42));
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function testItShowServicesLogsWithSpecificServiceAndTail(): void
    {
        $command = ['docker-compose', 'logs', '--follow', '--tail=42', 'php'];
        $dockerCompose = $this->prepareForegroundCommand($command);

        static::assertTrue($dockerCompose->showServicesLogs(42, 'php'));
    }
}
