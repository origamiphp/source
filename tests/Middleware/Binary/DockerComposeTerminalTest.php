<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Tests\TestLocationTrait;
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
    use TestLocationTrait;

    public function testItFixesPermissionsOnSharedSshAgent(): void
    {
        $command = ['docker-compose', 'exec', 'php', 'sh', '-c', 'chown www-data:www-data /run/host-services/ssh-auth.sock'];
        $dockerCompose = $this->prepareForegroundCommand($command);

        static::assertTrue($dockerCompose->fixPermissionsOnSharedSSHAgent());
    }

    public function testItOpensTerminalOnGivenServiceWithSpecificUser(): void
    {
        $command = ['docker-compose', 'exec', '-u', 'www-data:www-data', 'php', 'sh', '-l'];
        $dockerCompose = $this->prepareForegroundCommand($command);

        static::assertTrue($dockerCompose->openTerminal('php', 'www-data:www-data'));
    }

    public function testItOpensTerminalOnGivenServiceWithoutSpecificUser(): void
    {
        $command = ['docker-compose', 'exec', 'php', 'sh', '-l'];
        $dockerCompose = $this->prepareForegroundCommand($command);

        static::assertTrue($dockerCompose->openTerminal('php'));
    }
}
