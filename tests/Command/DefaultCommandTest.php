<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\DefaultCommand;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

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
        $application = new Application();

        $commandTester = new CommandTester($application->get('list'));
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringNotContainsString('origami:default', $display);
    }

    public function testItPrintsOnlyOrigamiCommands(): void
    {
        $application = new Application();
        $application->add(new DefaultCommand());
        $application->add($this->getFakeCommand());

        $commandTesterWithNativeCommand = new CommandTester($application->get('list'));
        $commandTesterWithNativeCommand->execute(['namespace' => 'origami']);

        $commandTesterWithCustomCommand = new CommandTester($application->get('origami:default'));
        $commandTesterWithCustomCommand->execute([]);

        static::assertSame($commandTesterWithNativeCommand->getDisplay(), $commandTesterWithCustomCommand->getDisplay());
    }

    /**
     * Retrieves a fake command based on AbstractBaseCommand with previously defined prophecies.
     */
    private function getFakeCommand(): Command
    {
        return new class() extends Command {
            /**
             * {@inheritdoc}
             */
            protected function configure(): void
            {
                $this->setName('origami:test');
                $this->setAliases(['test']);

                $this->setDescription('Dummy description for a temporary test command');
            }
        };
    }
}
