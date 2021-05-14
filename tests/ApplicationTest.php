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
 * @uses \App\Command\RegistryCommand
 * @uses \App\Command\DataCommand
 * @uses \App\Command\LogsCommand
 * @uses \App\Command\PsCommand
 * @uses \App\Command\RestartCommand
 * @uses \App\Command\Services\AbstractServiceCommand
 * @uses \App\Command\Services\ElasticsearchCommand
 * @uses \App\Command\Services\MysqlCommand
 * @uses \App\Command\Services\NginxCommand
 * @uses \App\Command\Services\PhpCommand
 * @uses \App\Command\Services\RedisCommand
 * @uses \App\Command\StopCommand
 * @uses \App\Command\InstallCommand
 * @uses \App\Command\PrepareCommand
 * @uses \App\Command\StartCommand
 * @uses \App\Command\UninstallCommand
 * @uses \App\Command\UpdateCommand
 * @uses \App\Command\AbstractBaseCommand
 * @uses \App\Command\DefaultCommand
 * @uses \App\Helper\ProcessFactory
 * @uses \App\Helper\CurrentContext
 * @uses \App\Helper\Validator
 * @uses \App\Middleware\Binary\Docker
 * @uses \App\Middleware\Binary\Mkcert
 * @uses \App\Middleware\Database
 * @uses \App\Service\ApplicationRequirements
 * @uses \App\Service\ConfigurationFiles
 * @uses \App\Service\EnvironmentBuilder
 * @uses \App\Service\TechnologyIdentifier
 * @uses \App\Kernel
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
