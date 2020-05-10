<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Configuration;

use App\Environment\EnvironmentEntity;
use App\Exception\FilesystemException;
use App\Exception\InvalidEnvironmentException;
use App\Middleware\Binary\Mkcert;
use App\Middleware\Configuration\ConfigurationUpdater;
use App\Middleware\DockerHub;
use App\Tests\TestConfigurationTrait;
use App\Tests\TestLocationTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;

/**
 * @internal
 *
 * @covers \App\Middleware\Configuration\AbstractConfiguration
 * @covers \App\Middleware\Configuration\ConfigurationUpdater
 */
final class ConfigurationUpdaterTest extends TestCase
{
    use TestConfigurationTrait;
    use TestLocationTrait;

    /** @var Prophet */
    private $prophet;

    /** @var ObjectProphecy */
    private $mkcert;

    /** @var string */
    private $fakePhpVersion = 'azerty';

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
     *
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItUpdatesAnEnvironmentWithPhpImage(string $type, ?string $domains = null): void
    {
        $environment = new EnvironmentEntity(basename($this->location), $this->location, $type, $domains);

        $destination = "{$this->location}/var/docker";
        mkdir($destination, 0777, true);
        file_put_contents("{$destination}/.env", "DOCKER_PHP_IMAGE={$this->fakePhpVersion}");

        $updater = new ConfigurationUpdater($this->mkcert->reveal());
        $updater->update($environment);

        $this->assertConfigurationIsInstalled($type, $destination, $this->fakePhpVersion);
    }

    /**
     * @dataProvider provideMultipleInstallContexts
     *
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItUpdatesAnEnvironmentWithoutPhpImage(string $type, ?string $domains = null): void
    {
        $environment = new EnvironmentEntity(basename($this->location), $this->location, $type, $domains);

        $destination = "{$this->location}/var/docker";
        mkdir($destination, 0777, true);
        file_put_contents("{$destination}/.env", 'DOCKER_PHP_IMAGE=');

        $updater = new ConfigurationUpdater($this->mkcert->reveal());
        $updater->update($environment);

        $this->assertConfigurationIsInstalled($type, $destination, DockerHub::DEFAULT_IMAGE_VERSION);
    }

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItDoesNotUpdateACustomEnvironment(): void
    {
        $environment = new EnvironmentEntity(basename($this->location), $this->location, EnvironmentEntity::TYPE_CUSTOM, null);

        $destination = "{$this->location}/var/docker";
        mkdir($destination, 0777, true);
        file_put_contents("{$destination}/.env", "DOCKER_PHP_IMAGE={$this->fakePhpVersion}");

        $this->expectExceptionObject(new InvalidEnvironmentException('Unable to update a custom environment.'));

        $updater = new ConfigurationUpdater($this->mkcert->reveal());
        $updater->update($environment);
    }

    /**
     * @dataProvider provideMultipleInstallContexts
     *
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItDoesNotUpdateARunningEnvironment(string $type, ?string $domains = null): void
    {
        $environment = new EnvironmentEntity(basename($this->location), $this->location, $type, $domains, true);

        $destination = "{$this->location}/var/docker";
        mkdir($destination, 0777, true);
        file_put_contents("{$destination}/.env", "DOCKER_PHP_IMAGE={$this->fakePhpVersion}");

        $this->expectExceptionObject(new InvalidEnvironmentException('Unable to update a running environment.'));

        $updater = new ConfigurationUpdater($this->mkcert->reveal());
        $updater->update($environment);
    }
}
