<?php

declare(strict_types=1);

namespace App\Tests\Environment\Configuration;

use App\Environment\Configuration\AbstractConfiguration;
use App\Environment\Configuration\ConfigurationUninstaller;
use App\Environment\EnvironmentEntity;
use App\Middleware\Binary\Mkcert;
use App\Tests\TestLocationTrait;
use Ergebnis\Environment\FakeVariables;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 *
 * @covers \App\Environment\Configuration\AbstractConfiguration
 * @covers \App\Environment\Configuration\ConfigurationUninstaller
 */
final class ConfigurationUninstallerTest extends TestCase
{
    use ProphecyTrait;
    use TestConfigurationTrait;
    use TestLocationTrait;

    /**
     * @dataProvider provideMultipleInstallContexts
     */
    public function testItUninstallsEnvironment(string $name, string $type, ?string $domains = null): void
    {
        $environment = new EnvironmentEntity($name, $this->location, $type, $domains);
        $this->installEnvironmentConfiguration($environment);

        $destination = $this->location.AbstractConfiguration::INSTALLATION_DIRECTORY;
        static::assertDirectoryExists($destination);

        $mkcert = $this->prophesize(Mkcert::class);

        $uninstaller = new ConfigurationUninstaller($mkcert->reveal(), FakeVariables::empty());
        $uninstaller->uninstall($environment);

        static::assertDirectoryDoesNotExist($destination);
    }
}
