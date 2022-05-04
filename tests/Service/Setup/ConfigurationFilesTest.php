<?php

declare(strict_types=1);

namespace App\Tests\Service\Setup;

use App\Service\Middleware\Binary\Mkcert;
use App\Service\RequirementsChecker;
use App\Service\Setup\ConfigurationFiles;
use App\Tests\TestEnvironmentTrait;
use App\ValueObject\EnvironmentEntity;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Finder\Finder;

/**
 * @covers \App\Service\Setup\ConfigurationFiles
 *
 * @internal
 */
final class ConfigurationFilesTest extends TestCase
{
    use ProphecyTrait;
    use TestEnvironmentTrait;

    public function testItInstallsConfigurationFilesWithBlackfireCredentials(): void
    {
        $mkcert = $this->prophesize(Mkcert::class);
        $requirementsChecker = $this->prophesize(RequirementsChecker::class);

        $environment = $this->createEnvironment();
        $settings = ['database' => 'mariadb:10.5', 'php' => 'ajardin/php:8.0'];
        $destination = $this->location.ConfigurationFiles::INSTALLATION_DIRECTORY;

        $requirementsChecker
            ->canMakeLocallyTrustedCertificates()
            ->willReturn(true)
        ;

        $mkcert
            ->generateCertificate($destination)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $installer = new ConfigurationFiles($mkcert->reveal(), $requirementsChecker->reveal());
        $installer->install($environment, $settings);

        $this->assertConfigurationIsInstalled($environment, $destination);
    }

    public function testItUpdatesAnEnvironment(): void
    {
        $mkcert = $this->prophesize(Mkcert::class);
        $requirementsChecker = $this->prophesize(RequirementsChecker::class);

        $environment = $this->createEnvironment();
        $settings = ['database' => 'mariadb:10.5', 'php' => 'ajardin/php:8.0'];
        $this->installEnvironmentConfiguration($environment);
        $destination = $this->location.ConfigurationFiles::INSTALLATION_DIRECTORY;

        $requirementsChecker
            ->canMakeLocallyTrustedCertificates()
            ->willReturn(true)
        ;

        $mkcert
            ->generateCertificate($destination)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $updater = new ConfigurationFiles($mkcert->reveal(), $requirementsChecker->reveal());
        $updater->install($environment, $settings);

        $this->assertConfigurationIsInstalled($environment, $destination);
    }

    public function testItUninstallsEnvironment(): void
    {
        $mkcert = $this->prophesize(Mkcert::class);
        $requirementsChecker = $this->prophesize(RequirementsChecker::class);

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);
        $destination = $this->location.ConfigurationFiles::INSTALLATION_DIRECTORY;

        static::assertDirectoryExists($destination);

        $uninstaller = new ConfigurationFiles($mkcert->reveal(), $requirementsChecker->reveal());
        $uninstaller->uninstall($environment);

        static::assertDirectoryDoesNotExist($destination);
    }

    private function assertConfigurationIsInstalled(EnvironmentEntity $environment, string $destination): void
    {
        $type = $environment->getType();

        $finder = new Finder();
        $finder->files()->in(__DIR__."/../../../src/Resources/docker-templates/{$type}");

        foreach ($finder as $file) {
            $pathname = $file->getPathname();
            $relativePath = substr($pathname, (strpos($pathname, $type) ?: 0) + \strlen($type) + 1);

            static::assertFileExists($destination.'/'.$relativePath);
        }

        static::assertFileExists($destination.'/docker-compose.override.yml');

        /** @var string $projectConfiguration */
        $projectConfiguration = file_get_contents("{$destination}/docker-compose.yml");
        static::assertStringNotContainsString('${DOCKER_PHP_IMAGE}', $projectConfiguration);
    }
}
