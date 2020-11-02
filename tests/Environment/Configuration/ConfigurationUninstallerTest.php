<?php

declare(strict_types=1);

namespace App\Tests\Environment\Configuration;

use App\Environment\Configuration\AbstractConfiguration;
use App\Environment\Configuration\ConfigurationUninstaller;
use App\Middleware\Binary\Mkcert;
use App\Tests\CustomProphecyTrait;
use App\Tests\TestConfigurationTrait;
use App\Tests\TestLocationTrait;
use Ergebnis\Environment\FakeVariables;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \App\Environment\Configuration\AbstractConfiguration
 * @covers \App\Environment\Configuration\ConfigurationUninstaller
 */
final class ConfigurationUninstallerTest extends TestCase
{
    use CustomProphecyTrait;
    use TestConfigurationTrait;
    use TestLocationTrait;

    public function testItUninstallsEnvironment(): void
    {
        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $destination = $this->location.AbstractConfiguration::INSTALLATION_DIRECTORY;
        static::assertDirectoryExists($destination);

        [$mkcert, $environmentVariables] = $this->prophesizeObjectArguments();

        $uninstaller = new ConfigurationUninstaller($mkcert->reveal(), $environmentVariables);
        $uninstaller->uninstall($environment);

        static::assertDirectoryDoesNotExist($destination);
    }

    /**
     * {@inheritdoc}
     */
    protected function prophesizeObjectArguments(): array
    {
        return [
            $this->prophesize(Mkcert::class),
            FakeVariables::empty(),
        ];
    }
}
