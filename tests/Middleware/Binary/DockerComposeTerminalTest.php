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
final class DockerComposeTerminalTest extends WebTestCase
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
    public function testItFixesPermissionsOnSharedSSHAgent(): void
    {
        $command = ['docker-compose', 'exec', 'php', 'sh', '-c', 'chown www-data:www-data /run/host-services/ssh-auth.sock'];
        $dockerCompose = $this->prepareForegroundCommand($command);

        static::assertTrue($dockerCompose->fixPermissionsOnSharedSSHAgent());
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function testItOpensTerminalOnGivenServiceWithSpecificUser(): void
    {
        $command = ['docker-compose', 'exec', '-u', 'www-data:www-data', 'php', 'sh', '-l'];
        $dockerCompose = $this->prepareForegroundCommand($command);

        static::assertTrue($dockerCompose->openTerminal('php', 'www-data:www-data'));
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function testItOpensTerminalOnGivenServiceWithoutSpecificUser(): void
    {
        $command = ['docker-compose', 'exec', 'php', 'sh', '-l'];
        $dockerCompose = $this->prepareForegroundCommand($command);

        static::assertTrue($dockerCompose->openTerminal('php'));
    }
}
