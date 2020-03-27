<?php

declare(strict_types=1);

namespace App\Tests\Middleware;

use App\Environment\EnvironmentEntity;
use App\Helper\ProcessFactory;
use App\Helper\ProcessProxy;
use App\Middleware\Binary\Mkcert;
use App\Middleware\SystemManager;
use App\Tests\TestLocationTrait;
use Generator;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

/**
 * @internal
 *
 * @covers \App\Middleware\SystemManager
 */
final class SystemManagerTest extends TestCase
{
    use TestLocationTrait;

    /** @var Mkcert|ObjectProphecy */
    private $mkcert;

    /** @var ObjectProphecy|ProcessFactory */
    private $processFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mkcert = $this->prophesize(Mkcert::class);
        $this->processFactory = $this->prophesize(ProcessFactory::class);

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
     * @uses \App\Helper\ProcessFactory
     */
    public function testItChecksInstalledBinary(): void
    {
        $systemManager = new SystemManager(
            $this->mkcert->reveal(),
            new ProcessFactory(
                $this->prophesize(ProcessProxy::class)->reveal(),
                $this->prophesize(LoggerInterface::class)->reveal()
            )
        );

        static::assertTrue($systemManager->isBinaryInstalled('php'));
        static::assertFalse($systemManager->isBinaryInstalled('azerty'));
    }

    /**
     * @dataProvider provideMultipleInstallContexts
     */
    public function testItInstallsConfigurationFiles(string $type, ?string $domains = null): void
    {
        $destination = sprintf('%s/var/docker', $this->location);

        if ($domains !== null) {
            $certificate = sprintf('%s/nginx/certs/custom.pem', $destination);
            $privateKey = sprintf('%s/nginx/certs/custom.key', $destination);

            $this->mkcert->generateCertificate($certificate, $privateKey, explode(' ', $domains))
                ->shouldBeCalledOnce()->willReturn(true);
        } else {
            $this->mkcert->generateCertificate()->shouldNotBeCalled();
        }

        $systemManager = new SystemManager($this->mkcert->reveal(), $this->processFactory->reveal());
        $systemManager->install($this->location, $type, $domains);

        $finder = new Finder();
        $finder->files()->in(__DIR__.sprintf('/../../src/Resources/%s', $type));

        foreach ($finder as $file) {
            $pathname = $file->getPathname();
            $relativePath = substr($pathname, strpos($pathname, $type) + \strlen($type) + 1);

            static::assertFileEquals($file->getPathname(), $destination.'/'.$relativePath);
        }
    }

    /**
     * @dataProvider provideMultipleInstallContexts
     */
    public function testItUninstallsEnvironment(string $type, ?string $domains = null): void
    {
        $environment = new EnvironmentEntity(basename($this->location), $this->location, $type, $domains);

        $systemManager = new SystemManager($this->mkcert->reveal(), $this->processFactory->reveal());

        $destination = sprintf('%s/var/docker', $this->location);
        mkdir($destination, 0777, true);
        static::assertDirectoryExists($destination);

        $systemManager->uninstall($environment);
        static::assertDirectoryNotExists($destination);
    }

    public function provideMultipleInstallContexts(): Generator
    {
        yield [EnvironmentEntity::TYPE_MAGENTO2, 'www.magento.localhost magento.localhost'];
        yield [EnvironmentEntity::TYPE_MAGENTO2, ''];

        yield [EnvironmentEntity::TYPE_SYMFONY, 'www.symfony.localhost symfony.localhost'];
        yield [EnvironmentEntity::TYPE_SYMFONY, ''];
    }
}
