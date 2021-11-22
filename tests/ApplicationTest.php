<?php

declare(strict_types=1);

namespace App\Tests;

use App\Application;
use App\Kernel;
use App\ValueObject\ApplicationVersion;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Application
 *
 * @uses \App\Command\AbstractBaseCommand
 * @uses \App\Command\DataCommand
 * @uses \App\Command\DebugCommand
 * @uses \App\Command\DefaultCommand
 * @uses \App\Command\InstallCommand
 * @uses \App\Command\LogsCommand
 * @uses \App\Command\PhpCommand
 * @uses \App\Command\PrepareCommand
 * @uses \App\Command\PsCommand
 * @uses \App\Command\RegistryCommand
 * @uses \App\Command\RestartCommand
 * @uses \App\Command\StartCommand
 * @uses \App\Command\StopCommand
 * @uses \App\Command\UninstallCommand
 * @uses \App\Command\UpdateCommand
 * @uses \App\Service\ApplicationContext
 * @uses \App\Service\Wrapper\ProcessFactory
 * @uses \App\Service\Setup\Validator
 * @uses \App\Service\Middleware\Binary\Docker
 * @uses \App\Service\Middleware\Binary\Mkcert
 * @uses \App\Service\ApplicationData
 * @uses \App\Service\Middleware\Database
 * @uses \App\Service\Middleware\Hosts
 * @uses \App\Service\RequirementsChecker
 * @uses \App\Service\Setup\ConfigurationFiles
 * @uses \App\Service\Setup\EnvironmentBuilder
 * @uses \App\Service\Setup\TechnologyIdentifier
 * @uses \App\Kernel
 */
final class ApplicationTest extends WebTestCase
{
    public function testItPrintTheApplicationHeader(): void
    {
        /** @var Kernel $kernel */
        $kernel = self::bootKernel();
        $application = new Application($kernel, new ApplicationVersion());

        $commandTester = new CommandTester($application->get('list'));
        $commandTester->execute([]);

        static::assertStringContainsString(Application::CONSOLE_LOGO, $commandTester->getDisplay());
        static::assertStringContainsString(Application::CONSOLE_NAME.' experimental', $commandTester->getDisplay());
    }

    public function testItDoesNotPrintTheApplicationHeader(): void
    {
        /** @var Kernel $kernel */
        $kernel = self::bootKernel();
        $application = new Application($kernel, new ApplicationVersion());

        $commandTester = new CommandTester($application->get('registry'));
        $commandTester->execute([]);

        static::assertStringNotContainsString(Application::CONSOLE_LOGO, $commandTester->getDisplay());
        static::assertStringNotContainsString(Application::CONSOLE_NAME.' experimental', $commandTester->getDisplay());
    }
}
