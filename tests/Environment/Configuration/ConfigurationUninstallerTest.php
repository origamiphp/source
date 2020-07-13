<?php

declare(strict_types=1);

namespace App\Tests\Environment\Configuration;

use App\Environment\Configuration\AbstractConfiguration;
use App\Environment\Configuration\ConfigurationUninstaller;
use App\Environment\EnvironmentEntity;
use App\Middleware\Binary\Mkcert;
use App\Tests\TestConfigurationTrait;
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
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->createLocation();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeLocation();
    }

    /**
     * @dataProvider provideMultipleInstallContexts
     */
    public function testItUninstallsEnvironment(string $name, string $type, ?string $domains = null): void
    {
        $environment = new EnvironmentEntity($name, $this->location, $type, $domains);

        $destination = $this->location.AbstractConfiguration::INSTALLATION_DIRECTORY;
        mkdir($destination, 0777, true);
        static::assertDirectoryExists($destination);

        $mkcert = $this->prophesize(Mkcert::class);

        $uninstaller = new ConfigurationUninstaller($mkcert->reveal(), FakeVariables::empty());
        $uninstaller->uninstall($environment);

        static::assertDirectoryDoesNotExist($destination);
    }
}
