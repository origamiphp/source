<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Configuration;

use App\Environment\EnvironmentEntity;
use App\Middleware\Binary\Mkcert;
use App\Middleware\Configuration\ConfigurationUninstaller;
use App\Tests\TestConfigurationTrait;
use App\Tests\TestLocationTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;

/**
 * @internal
 *
 * @covers \App\Middleware\Configuration\AbstractConfiguration
 * @covers \App\Middleware\Configuration\ConfigurationUninstaller
 */
final class ConfigurationUninstallerTest extends TestCase
{
    use TestConfigurationTrait;
    use TestLocationTrait;

    /** @var Prophet */
    private $prophet;

    /** @var ObjectProphecy */
    private $mkcert;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prophet = new Prophet();
        $this->mkcert = $this->prophet->prophesize(Mkcert::class);

        $this->createLocation();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->prophet->checkPredictions();
        $this->removeLocation();
    }

    /**
     * @dataProvider provideMultipleInstallContexts
     */
    public function testItUninstallsEnvironment(string $name, string $type, ?string $domains = null): void
    {
        $environment = new EnvironmentEntity($name, $this->location, $type, $domains);

        $destination = sprintf('%s/var/docker', $this->location);
        mkdir($destination, 0777, true);
        static::assertDirectoryExists($destination);

        $uninstaller = new ConfigurationUninstaller($this->mkcert->reveal());
        $uninstaller->uninstall($environment);

        static::assertDirectoryDoesNotExist($destination);
    }
}
