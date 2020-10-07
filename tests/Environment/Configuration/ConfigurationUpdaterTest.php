<?php

declare(strict_types=1);

namespace App\Tests\Environment\Configuration;

use App\Environment\Configuration\AbstractConfiguration;
use App\Environment\Configuration\ConfigurationUpdater;
use App\Environment\EnvironmentEntity;
use App\Exception\FilesystemException;
use App\Exception\InvalidEnvironmentException;
use App\Middleware\Binary\Mkcert;
use App\Tests\TestLocationTrait;
use Ergebnis\Environment\FakeVariables;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
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
    public function testItUpdatesAnEnvironment(
        string $name,
        string $type,
        string $phpVersion,
        string $databaseVersion,
        ?string $domains = null
    ): void {
        $mkcert = $this->prophesize(Mkcert::class);

        $environment = new EnvironmentEntity($name, $this->location, $type, $domains);
        $this->installEnvironmentConfiguration($environment);

        $destination = $this->location.AbstractConfiguration::INSTALLATION_DIRECTORY;
        file_put_contents("{$destination}/.env", 'DOCKER_PHP_IMAGE=7.4');

        if ($domains !== null) {
            $certificate = sprintf('%s/nginx/certs/custom.pem', $destination);
            $privateKey = sprintf('%s/nginx/certs/custom.key', $destination);

            $mkcert->generateCertificate($certificate, $privateKey, explode(' ', $domains))->shouldBeCalledOnce()->willReturn(true);
        } else {
            $mkcert->generateCertificate(Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();
        }

        $credentials = $this->getFakeBlackfireCredentials();

        $updater = new ConfigurationUpdater($mkcert->reveal(), FakeVariables::fromArray($credentials));
        $updater->update($environment, $phpVersion, $databaseVersion, $domains);

        $this->assertConfigurationIsInstalled($type, $destination, $phpVersion, $databaseVersion);
        $this->assertConfigurationContainsBlackfireCredentials($destination, $credentials);
    }

    /**
     * @dataProvider provideMultipleInstallContexts
     *
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItDoesNotUpdateARunningEnvironment(
        string $name,
        string $type,
        string $phpVersion,
        string $databaseVersion,
        ?string $domains = null
    ): void {
        $environment = new EnvironmentEntity($name, $this->location, $type, $domains, true);
        $this->installEnvironmentConfiguration($environment);

        $mkcert = $this->prophesize(Mkcert::class);
        $this->expectException(InvalidEnvironmentException::class);

        $credentials = $this->getFakeBlackfireCredentials();

        $updater = new ConfigurationUpdater($mkcert->reveal(), FakeVariables::fromArray($credentials));
        $updater->update($environment, $phpVersion, $databaseVersion, $domains);
    }
}
