<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Environment\EnvironmentEntity;
use App\Middleware\Binary\Mkcert;
use App\Service\ConfigurationFiles;
use App\Tests\TestEnvironmentTrait;
use Ergebnis\Environment\FakeVariables;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Finder\Finder;

/**
 * @covers \App\Service\ConfigurationFiles
 *
 * @internal
 */
final class ConfigurationFilesTest extends TestCase
{
    use ProphecyTrait;
    use TestEnvironmentTrait;

    private const FAKE_BLACKFIRE_CREDENTIALS = [
        'BLACKFIRE_SERVER_ID' => 'server_foo',
        'BLACKFIRE_SERVER_TOKEN' => 'server_bar',
        'BLACKFIRE_CLIENT_ID' => 'client_foo',
        'BLACKFIRE_CLIENT_TOKEN' => 'client_bar',
    ];

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
        $environmentVariables = FakeVariables::fromArray(self::FAKE_BLACKFIRE_CREDENTIALS);

        $environment = new EnvironmentEntity($name, $this->location, $type, $domains);
        $destination = $this->location.ConfigurationFiles::INSTALLATION_DIRECTORY;

        if ($domains = $environment->getDomains()) {
            $certificate = sprintf('%s/nginx/certs/custom.pem', $destination);
            $privateKey = sprintf('%s/nginx/certs/custom.key', $destination);

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

        $installer = new ConfigurationFiles($mkcert->reveal(), $environmentVariables);
        $installer->install($environment, $settings);

        $this->assertConfigurationIsInstalled($environment, $destination, $settings);
        $this->assertConfigurationContainsBlackfireCredentials($destination);
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
        $environmentVariables = FakeVariables::fromArray(self::FAKE_BLACKFIRE_CREDENTIALS);

        $environment = new EnvironmentEntity($name, $this->location, $type, $domains);
        $this->installEnvironmentConfiguration($environment);
        $destination = $this->location.ConfigurationFiles::INSTALLATION_DIRECTORY;

        if ($domains = $environment->getDomains()) {
            $certificate = sprintf('%s/nginx/certs/custom.pem', $destination);
            $privateKey = sprintf('%s/nginx/certs/custom.key', $destination);

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

        $updater = new ConfigurationFiles($mkcert->reveal(), $environmentVariables);
        $updater->install($environment, $settings);

        $this->assertConfigurationIsInstalled($environment, $destination, $settings);
        $this->assertConfigurationContainsBlackfireCredentials($destination);
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
        $environmentVariables = FakeVariables::fromArray(self::FAKE_BLACKFIRE_CREDENTIALS);

        $environment = new EnvironmentEntity($name, $this->location, $type, $domains);
        $this->installEnvironmentConfiguration($environment);
        $destination = $this->location.ConfigurationFiles::INSTALLATION_DIRECTORY;

        static::assertDirectoryExists($destination);

        $uninstaller = new ConfigurationFiles($mkcert->reveal(), $environmentVariables);
        $uninstaller->uninstall($environment);

        static::assertDirectoryDoesNotExist($destination);
    }

    private function assertConfigurationIsInstalled(EnvironmentEntity $environment, string $destination, array $settings): void
    {
        $type = $environment->getType();

        $finder = new Finder();
        $finder->files()->in(__DIR__."/../../src/Resources/{$type}");

        foreach ($finder as $file) {
            $pathname = $file->getPathname();
            $relativePath = substr($pathname, (strpos($pathname, $type) ?: 0) + \strlen($type) + 1);

            static::assertFileEquals($file->getPathname(), $destination.'/'.$relativePath);
        }

        /** @var string $projectConfiguration */
        $projectConfiguration = file_get_contents(sprintf('%s/.env', $destination));

        foreach ($settings as $key => $value) {
            $entry = sprintf('DOCKER_%s_IMAGE=%s', strtoupper($key), $value);
            static::assertStringContainsString($entry, $projectConfiguration);
        }
    }

    private function assertConfigurationContainsBlackfireCredentials(string $destination): void
    {
        /** @var string $projectConfiguration */
        $projectConfiguration = file_get_contents("{$destination}/.env");

        foreach (self::FAKE_BLACKFIRE_CREDENTIALS as $key => $value) {
            static::assertStringContainsString("{$key}={$value}\n", $projectConfiguration);
        }
    }
}
