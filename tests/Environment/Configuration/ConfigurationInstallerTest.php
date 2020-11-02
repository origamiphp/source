<?php

declare(strict_types=1);

namespace App\Tests\Environment\Configuration;

use App\Environment\Configuration\AbstractConfiguration;
use App\Environment\Configuration\ConfigurationInstaller;
use App\Exception\FilesystemException;
use App\Middleware\Binary\Mkcert;
use App\Tests\CustomProphecyTrait;
use App\Tests\TestConfigurationTrait;
use App\Tests\TestLocationTrait;
use Ergebnis\Environment\FakeVariables;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @internal
 *
 * @covers \App\Environment\Configuration\AbstractConfiguration
 * @covers \App\Environment\Configuration\ConfigurationInstaller
 */
final class ConfigurationInstallerTest extends TestCase
{
    use CustomProphecyTrait;
    use TestConfigurationTrait;
    use TestLocationTrait;

    /**
     * @dataProvider provideMultipleInstallContexts
     *
     * @throws FilesystemException
     */
    public function testItInstallsConfigurationFilesWithBlackfireCredentials(
        string $name,
        string $type,
        string $phpVersion,
        string $databaseVersion,
        ?string $domains = null
    ): void {
        [$mkcert] = $this->prophesizeObjectArguments();

        $source = __DIR__."/../../../src/Resources/{$type}";
        $destination = $this->location.AbstractConfiguration::INSTALLATION_DIRECTORY;

        /** @var string $defaultConfiguration */
        $defaultConfiguration = file_get_contents("{$source}/../.env");
        static::assertStringNotContainsString($phpVersion, $defaultConfiguration);

        if ($domains !== null) {
            $certificate = sprintf('%s/nginx/certs/custom.pem', $destination);
            $privateKey = sprintf('%s/nginx/certs/custom.key', $destination);

            $mkcert->generateCertificate($certificate, $privateKey, explode(' ', $domains))->shouldBeCalledOnce()->willReturn(true);
        } else {
            $mkcert->generateCertificate(Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();
        }

        $credentials = $this->getFakeBlackfireCredentials();

        $installer = new ConfigurationInstaller($mkcert->reveal(), FakeVariables::fromArray($credentials));
        $installer->install($this->location, $name, $type, $phpVersion, $databaseVersion, $domains);

        $this->assertConfigurationIsInstalled($type, $destination, $phpVersion, $databaseVersion);
        $this->assertConfigurationContainsBlackfireCredentials($destination, $credentials);
    }

    /**
     * {@inheritdoc}
     */
    protected function prophesizeObjectArguments(): array
    {
        return [
            $this->prophesize(Mkcert::class),
        ];
    }
}
