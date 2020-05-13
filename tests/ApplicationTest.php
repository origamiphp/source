<?php

declare(strict_types=1);

namespace App\Tests;

use App\Application;
use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Application
 *
 * @uses \App\Command\Additional\RegisterCommand
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
 * @uses \App\Command\Main\UpdateCommand
 * @uses \App\Command\AbstractBaseCommand
 * @uses \App\Command\DefaultCommand
 * @uses \App\Helper\ProcessFactory
 * @uses \App\Helper\CurrentContext
 * @uses \App\Kernel
 * @uses \App\Middleware\Binary\DockerCompose
 * @uses \App\Middleware\Binary\Mkcert
 * @uses \App\Middleware\Configuration\AbstractConfiguration
 * @uses \App\Middleware\Configuration\ConfigurationInstaller
 * @uses \App\Middleware\Configuration\ConfigurationUninstaller
 * @uses \App\Middleware\Configuration\ConfigurationUpdater
 * @uses \App\Middleware\Database
 * @uses \App\Middleware\DockerHub
 */
final class ApplicationTest extends WebTestCase
{
    public function testItPrintTheApplicationHeader(): void
    {
        /** @var Kernel $kernel */
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $commandTester = new CommandTester($application->get('list'));
        $commandTester->execute([]);

        static::assertStringContainsString(Application::CONSOLE_LOGO, $commandTester->getDisplay());
        static::assertStringContainsString(Application::CONSOLE_NAME.' @app_version@', $commandTester->getDisplay());
    }

    public function testItDoesNotPrintTheApplicationHeader(): void
    {
        /** @var Kernel $kernel */
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $commandTester = new CommandTester($application->get('registry'));
        $commandTester->execute([]);

        static::assertStringNotContainsString(Application::CONSOLE_LOGO, $commandTester->getDisplay());
        static::assertStringNotContainsString(Application::CONSOLE_NAME.' @app_version@', $commandTester->getDisplay());
    }
}
