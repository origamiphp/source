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
final class DockerTerminalTest extends WebTestCase
{
    use CustomProphecyTrait;
    use TestDockerTrait;
    use TestLocationTrait;

    public function testItFixesPermissionsOnSharedSshAgent(): void
    {
        $command = ['docker', 'compose', 'exec', '-T', 'php', 'bash', '-c', 'chown www-data:www-data /run/host-services/ssh-auth.sock'];
        $docker = $this->prepareForegroundCommand($command);

        static::assertTrue($docker->fixPermissionsOnSharedSSHAgent());
    }

    public function testItOpensTerminalOnGivenServiceWithSpecificUser(): void
    {
        $command = 'docker exec -it --user=www-data:www-data symfony_origami_php_1 bash --login';
        $docker = $this->prepareForegroundFromShellCommand($command);

        static::assertTrue($docker->openTerminal('php', 'www-data:www-data'));
    }

    public function testItOpensTerminalOnGivenServiceWithoutSpecificUser(): void
    {
        $command = 'docker exec -it symfony_origami_php_1 bash --login';
        $docker = $this->prepareForegroundFromShellCommand($command);

        static::assertTrue($docker->openTerminal('php'));
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
