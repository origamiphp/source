<?php

declare(strict_types=1);

namespace App\Tests\Environment\Configuration;

use App\Environment\Configuration\AbstractConfiguration;
use App\Environment\Configuration\ConfigurationUpdater;
use App\Environment\EnvironmentEntity;
use App\Environment\EnvironmentMaker\DockerHub;
use App\Exception\FilesystemException;
use App\Exception\InvalidEnvironmentException;
use App\Middleware\Binary\Mkcert;
use App\Tests\TestLocationTrait;
use Ergebnis\Environment\FakeVariables;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 *
 * @covers \App\Environment\Configuration\AbstractConfiguration
 * @covers \App\Environment\Configuration\ConfigurationUpdater
 */
final class ConfigurationUpdaterTest extends TestCase
{
    use ProphecyTrait;
    use TestConfigurationTrait;
    use TestLocationTrait;

    /**
     * @dataProvider provideMultipleInstallContexts
     *
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItUpdatesAnEnvironmentWithPhpImage(string $name, string $type, ?string $domains = null): void
    {
        $environment = new EnvironmentEntity($name, $this->location, $type, $domains);
        $this->installEnvironmentConfiguration($environment);

        $destination = $this->location.AbstractConfiguration::INSTALLATION_DIRECTORY;
        file_put_contents("{$destination}/.env", 'DOCKER_PHP_IMAGE=7.4');

        $mkcert = $this->prophesize(Mkcert::class);

        $updater = new ConfigurationUpdater($mkcert->reveal(), FakeVariables::empty());
        $updater->update($environment);

        $this->assertConfigurationIsInstalled($type, $destination, '7.4');
    }

    /**
     * @dataProvider provideMultipleInstallContexts
     *
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItUpdatesAnEnvironmentWithoutPhpImage(string $name, string $type, ?string $domains = null): void
    {
        $environment = new EnvironmentEntity($name, $this->location, $type, $domains);
        $this->installEnvironmentConfiguration($environment);

        $destination = $this->location.AbstractConfiguration::INSTALLATION_DIRECTORY;

        $mkcert = $this->prophesize(Mkcert::class);

        $updater = new ConfigurationUpdater($mkcert->reveal(), FakeVariables::empty());
        $updater->update($environment);

        $this->assertConfigurationIsInstalled($type, $destination, DockerHub::DEFAULT_IMAGE_VERSION);
    }

    /**
     * @dataProvider provideMultipleInstallContexts
     *
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItUpdatesAnEnvironmentWithBlackfireCredentials(string $name, string $type, ?string $domains = null): void
    {
        $environment = new EnvironmentEntity($name, $this->location, $type, $domains);
        $this->installEnvironmentConfiguration($environment);

        $destination = $this->location.AbstractConfiguration::INSTALLATION_DIRECTORY;
        $credentials = $this->getFakeBlackfireCredentials();

        $mkcert = $this->prophesize(Mkcert::class);

        $updater = new ConfigurationUpdater($mkcert->reveal(), FakeVariables::fromArray($credentials));
        $updater->update($environment);

        $this->assertConfigurationIsInstalled($type, $destination, DockerHub::DEFAULT_IMAGE_VERSION);
        $this->assertConfigurationContainsBlackfireCredentials($destination, $credentials);
    }

    /**
     * @dataProvider provideMultipleInstallContexts
     *
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItDoesNotUpdateARunningEnvironment(string $name, string $type, ?string $domains = null): void
    {
        $environment = new EnvironmentEntity($name, $this->location, $type, $domains, true);
        $this->installEnvironmentConfiguration($environment);

        $mkcert = $this->prophesize(Mkcert::class);
        $this->expectExceptionObject(new InvalidEnvironmentException('Unable to update a running environment.'));

        $updater = new ConfigurationUpdater($mkcert->reveal(), FakeVariables::empty());
        $updater->update($environment);
    }
}
