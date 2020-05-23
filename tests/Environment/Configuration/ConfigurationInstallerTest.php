<?php

declare(strict_types=1);

namespace App\Tests\Environment\Configuration;

use App\Environment\Configuration\ConfigurationInstaller;
use App\Exception\FilesystemException;
use App\Middleware\Binary\Mkcert;
use App\Tests\TestConfigurationTrait;
use App\Tests\TestLocationTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;

/**
 * @internal
 *
 * @covers \App\Environment\Configuration\AbstractConfiguration
 * @covers \App\Environment\Configuration\ConfigurationInstaller
 */
final class ConfigurationInstallerTest extends TestCase
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
     *
     * @throws FilesystemException
     */
    public function testItInstallsConfigurationFiles(string $name, string $type, ?string $domains = null): void
    {
        $phpVersion = 'azerty';

        $source = __DIR__."/../../../src/Resources/{$type}";
        $destination = "{$this->location}/var/docker";

        /** @var string $defaultConfiguration */
        $defaultConfiguration = file_get_contents("{$source}/.env");
        static::assertStringNotContainsString($phpVersion, $defaultConfiguration);

        if ($domains !== null) {
            $certificate = sprintf('%s/nginx/certs/custom.pem', $destination);
            $privateKey = sprintf('%s/nginx/certs/custom.key', $destination);

            (new MethodProphecy($this->mkcert, 'generateCertificate', [$certificate, $privateKey, explode(' ', $domains)]))
                ->shouldBeCalledOnce()
                ->willReturn(true)
            ;
        } else {
            (new MethodProphecy($this->mkcert, 'generateCertificate', []))
                ->shouldNotBeCalled()
            ;
        }

        $installer = new ConfigurationInstaller($this->mkcert->reveal());
        $installer->install($name, $this->location, $type, $phpVersion, $domains);

        $this->assertConfigurationIsInstalled($type, $destination, $phpVersion);
    }
}
