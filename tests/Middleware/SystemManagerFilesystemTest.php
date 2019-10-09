<?php

declare(strict_types=1);

namespace App\Tests\Middleware;

use App\Entity\Environment;
use App\Exception\InvalidEnvironmentException;
use App\Helper\ProcessFactory;
use App\Middleware\Binary\Mkcert;
use App\Middleware\SystemManager;
use App\Repository\EnvironmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Generator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @covers \App\Middleware\SystemManager
 */
final class SystemManagerFilesystemTest extends TestCase
{
    private $mkcert;
    private $validator;
    private $entityManager;
    private $environmentRepository;
    private $processFactory;

    private $location;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mkcert = $this->prophesize(Mkcert::class);
        $this->validator = $this->prophesize(ValidatorInterface::class);
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->environmentRepository = $this->prophesize(EnvironmentRepository::class);
        $this->processFactory = $this->prophesize(ProcessFactory::class);

        $this->location = sys_get_temp_dir().'/origami/SystemManagerTest';
        mkdir($this->location, 0777, true);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_dir($this->location)) {
            $filesystem = new Filesystem();
            $filesystem->remove($this->location);
        }

        $this->location = null;
    }

    public function testItChecksInstalledBinary(): void
    {
        $systemManager = new SystemManager(
            $this->mkcert->reveal(),
            $this->validator->reveal(),
            $this->entityManager->reveal(),
            $this->environmentRepository->reveal(),
            new ProcessFactory($this->prophesize(LoggerInterface::class)->reveal())
        );

        static::assertTrue($systemManager->isBinaryInstalled('php'));
        static::assertFalse($systemManager->isBinaryInstalled('azerty'));
    }

    /**
     * @dataProvider provideMultipleInstallContexts
     */
    public function testItInstallsConfigurationFiles(string $location, string $type, ?string $domains = null): void
    {
        $destination = "{$location}/var/docker";

        if ($domains !== null) {
            $certificate = "{$destination}/nginx/certs/custom.pem";
            $privateKey = "{$destination}/nginx/certs/custom.key";

            $this->mkcert->generateCertificate($certificate, $privateKey, explode(' ', $domains))
                ->shouldBeCalledOnce()->willReturn(true);
        } else {
            $this->mkcert->generateCertificate()->shouldNotBeCalled();
        }

        $this->validator->validate(Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn(new ConstraintViolationList())
        ;

        $environment = new Environment();
        $environment->setName(basename($location));
        $environment->setLocation($location);
        $environment->setType($type);
        if ($domains !== null) {
            $environment->setDomains($domains);
        }

        $this->entityManager->persist($environment)->shouldBeCalledOnce();
        $this->entityManager->flush()->shouldBeCalledOnce();

        $systemManager = new SystemManager(
            $this->mkcert->reveal(),
            $this->validator->reveal(),
            $this->entityManager->reveal(),
            $this->environmentRepository->reveal(),
            $this->processFactory->reveal()
        );

        $systemManager->install($location, $type, $domains);

        $finder = new Finder();
        $finder->files()->in(__DIR__."/../../src/Resources/{$type}");

        foreach ($finder as $file) {
            $pathname = $file->getPathname();
            $relativePath = substr($pathname, strpos($pathname, $type) + \strlen($type) + 1);

            static::assertFileEquals($file->getPathname(), $destination.'/'.$relativePath);
        }
    }

    /**
     * @dataProvider provideMultipleInstallContexts
     */
    public function testItThrowsAnExceptionWithInvalidEnvironment(string $location, string $type, ?string $domains = null): void
    {
        $destination = "{$location}/var/docker";

        if ($domains !== null) {
            $certificate = "{$destination}/nginx/certs/custom.pem";
            $privateKey = "{$destination}/nginx/certs/custom.key";

            $this->mkcert->generateCertificate($certificate, $privateKey, explode(' ', $domains))
                ->shouldBeCalledOnce()->willReturn(true);
        } else {
            $this->mkcert->generateCertificate()->shouldNotBeCalled();
        }

        $violation = $this->prophesize(ConstraintViolation::class);
        $violation->getMessage()->shouldBeCalledOnce()->willReturn('Dummy exception');

        $errors = new ConstraintViolationList();
        $errors->add($violation->reveal());

        $this->validator->validate(Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn($errors)
        ;

        $environment = new Environment();
        $environment->setName(basename($location));
        $environment->setLocation($location);
        $environment->setType($type);
        if ($domains !== null) {
            $environment->setDomains($domains);
        }

        $this->entityManager->persist($environment)->shouldNotBeCalled();
        $this->entityManager->flush()->shouldNotBeCalled();

        $systemManager = new SystemManager(
            $this->mkcert->reveal(),
            $this->validator->reveal(),
            $this->entityManager->reveal(),
            $this->environmentRepository->reveal(),
            $this->processFactory->reveal()
        );

        $this->expectException(InvalidEnvironmentException::class);
        $systemManager->install($location, $type, $domains);
    }

    /**
     * @dataProvider provideMultipleInstallContexts
     */
    public function testItUninstallsEnvironment(string $location, string $type, ?string $domains = null): void
    {
        $destination = "{$location}/var/docker";
        mkdir($destination, 0777, true);

        $environment = new Environment();
        $environment->setName(basename($location));
        $environment->setLocation($location);
        $environment->setType($type);
        if ($domains !== null) {
            $environment->setDomains($domains);
        }

        $this->entityManager->remove($environment)->shouldBeCalledOnce();
        $this->entityManager->flush()->shouldBeCalledOnce();

        $systemManager = new SystemManager(
            $this->mkcert->reveal(),
            $this->validator->reveal(),
            $this->entityManager->reveal(),
            $this->environmentRepository->reveal(),
            $this->processFactory->reveal()
        );

        static::assertDirectoryExists($destination);
        $systemManager->uninstall($environment);
        static::assertDirectoryNotExists($destination);
    }

    public function provideMultipleInstallContexts(): ?Generator
    {
        $location = sys_get_temp_dir().'/origami/SystemManagerTest';

        yield [$location, 'magento2', 'www.magento.localhost magento.localhost'];
        yield [$location, 'magento2', ''];
    }
}
