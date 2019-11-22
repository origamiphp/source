<?php

declare(strict_types=1);

namespace App\Tests\Command;

use Liip\FunctionalTestBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @covers \App\Command\DefaultCommand
 *
 * @uses \App\Command\AbstractBaseCommand
 * @uses \App\Command\Additional\CheckCommand
 * @uses \App\Command\Additional\RegistryCommand
 * @uses \App\Command\Contextual\DataCommand
 * @uses \App\Command\Contextual\LogsCommand
 * @uses \App\Command\Contextual\PsCommand
 * @uses \App\Command\Contextual\RestartCommand
 * @uses \App\Command\Contextual\RootCommand
 * @uses \App\Command\Contextual\Services\AbstractServiceCommand
 * @uses \App\Command\Contextual\Services\ElasticsearchCommand
 * @uses \App\Command\Contextual\Services\MysqlCommand
 * @uses \App\Command\Contextual\Services\NginxCommand
 * @uses \App\Command\Contextual\Services\PhpCommand
 * @uses \App\Command\Contextual\Services\RedisCommand
 * @uses \App\Command\Contextual\StopCommand
 * @uses \App\Command\Main\InstallCommand
 * @uses \App\Command\Main\PrepareCommand
 * @uses \App\Command\Main\StartCommand
 * @uses \App\Command\Main\UninstallCommand
 * @uses \App\Helper\ProcessFactory
 * @uses \App\Kernel
 * @uses \App\Middleware\Binary\DockerCompose
 * @uses \App\Middleware\Binary\Mkcert
 * @uses \App\Middleware\SystemManager
 * @uses \App\Repository\EnvironmentRepository
 */
final class DefaultCommandTest extends WebTestCase
{
    public function testItNotPrintsDefaultCommandInList(): void
    {
        $output = $this->runCommand('list')->getDisplay();
        static::assertStringNotContainsString('origami:default', $output);
    }

    public function testItPrintsOnlyOrigamiCommands(): void
    {
        static::assertSame(
            $this->runCommand('list', ['namespace' => 'origami'])->getDisplay(),
            $this->runCommand('origami:default')->getDisplay()
        );
    }
}
