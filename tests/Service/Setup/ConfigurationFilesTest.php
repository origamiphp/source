<?php

declare(strict_types=1);

namespace App\Tests\Service\Setup;

use App\Service\Middleware\Binary\Mkcert;
use App\Service\Middleware\Database;
use App\Service\Setup\ConfigurationFiles;
use App\Tests\TestEnvironmentTrait;
use App\ValueObject\EnvironmentEntity;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
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

    /**
     * @dataProvider provideMultipleInstallContexts
     */
    public function testItInstallsConfigurationFilesWithBlackfireCredentials(
        string $name,
        string $type,
        ?string $domains = null,
        array $settings = []
    ): void {
        $mkcert = $this->prophesize(Mkcert::class);
        $database = $this->prophesize(Database::class);

        $environment = new EnvironmentEntity($name, $this->location, $type, $domains);
        $destination = $this->location.ConfigurationFiles::INSTALLATION_DIRECTORY;

        $database
            ->replaceDatabasePlaceholder(Argument::type('string'), Argument::type('string'))
            ->shouldBeCalledOnce()
        ;

        if ($domains = $environment->getDomains()) {
            $certificate = "{$destination}/nginx/certs/custom.pem";
            $privateKey = "{$destination}/nginx/certs/custom.key";

            $mkcert
                ->generateCertificate($certificate, $privateKey, explode(' ', $domains))
                ->shouldBeCalledOnce()
                ->willReturn(true)
            ;
        } else {
            $mkcert
                ->generateCertificate(Argument::any(), Argument::any(), Argument::any())
                ->shouldNotBeCalled()
            ;
        }

        $installer = new ConfigurationFiles($mkcert->reveal(), $database->reveal());
        $installer->install($environment, $settings);

        $this->assertConfigurationIsInstalled($environment, $destination);
    }

    /**
     * @dataProvider provideMultipleInstallContexts
     */
    public function testItUpdatesAnEnvironment(
        string $name,
        string $type,
        ?string $domains = null,
        array $settings = []
    ): void {
        $mkcert = $this->prophesize(Mkcert::class);
        $database = $this->prophesize(Database::class);

        $environment = new EnvironmentEntity($name, $this->location, $type, $domains);
        $this->installEnvironmentConfiguration($environment);
        $destination = $this->location.ConfigurationFiles::INSTALLATION_DIRECTORY;

        $database
            ->replaceDatabasePlaceholder(Argument::type('string'), Argument::type('string'))
            ->shouldBeCalledOnce()
        ;

        if ($domains = $environment->getDomains()) {
            $certificate = "{$destination}/nginx/certs/custom.pem";
            $privateKey = "{$destination}/nginx/certs/custom.key";

            $mkcert
                ->generateCertificate($certificate, $privateKey, explode(' ', $domains))
                ->shouldBeCalledOnce()
                ->willReturn(true)
            ;
        } else {
            $mkcert
                ->generateCertificate(Argument::any(), Argument::any(), Argument::any())
                ->shouldNotBeCalled()
            ;
        }

        $updater = new ConfigurationFiles($mkcert->reveal(), $database->reveal());
        $updater->install($environment, $settings);

        $this->assertConfigurationIsInstalled($environment, $destination);
    }

    /**
     * @dataProvider provideMultipleInstallContexts
     */
    public function testItUninstallsEnvironment(
        string $name,
        string $type,
        ?string $domains = null
    ): void {
        $mkcert = $this->prophesize(Mkcert::class);
        $database = $this->prophesize(Database::class);

        $environment = new EnvironmentEntity($name, $this->location, $type, $domains);
        $this->installEnvironmentConfiguration($environment);
        $destination = $this->location.ConfigurationFiles::INSTALLATION_DIRECTORY;

        static::assertDirectoryExists($destination);

        $uninstaller = new ConfigurationFiles($mkcert->reveal(), $database->reveal());
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

        /** @var string $projectConfiguration */
        $projectConfiguration = file_get_contents("{$destination}/docker-compose.yml");
        static::assertStringNotContainsString('${DOCKER_PHP_IMAGE}', $projectConfiguration);
    }
}
