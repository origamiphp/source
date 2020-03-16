<?php

declare(strict_types=1);

namespace App\Tests\Middleware;

use App\Entity\Environment;
use App\Exception\InvalidEnvironmentException;
use App\Helper\ProcessFactory;
use App\Helper\ProcessProxy;
use App\Middleware\Binary\Mkcert;
use App\Middleware\SystemManager;
use App\Repository\EnvironmentRepository;
use App\Tests\TestLocationTrait;
use Doctrine\ORM\EntityManagerInterface;
use Generator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
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
    use TestLocationTrait;

    /** @var Mkcert|ObjectProphecy */
    private $mkcert;

    /** @var ObjectProphecy|ValidatorInterface */
    private $validator;

    /** @var EntityManagerInterface|ObjectProphecy */
    private $entityManager;

    /** @var EnvironmentRepository|ObjectProphecy */
    private $environmentRepository;

    /** @var ObjectProphecy|ProcessFactory */
    private $processFactory;

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
            $this->validator->reveal(),
            $this->entityManager->reveal(),
            $this->environmentRepository->reveal(),
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

        $this->validator->validate(Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn(new ConstraintViolationList())
        ;

        $environment = new Environment(basename($this->location), $this->location, $type, $domains);
        $this->entityManager->persist($environment)->shouldBeCalledOnce();
        $this->entityManager->flush()->shouldBeCalledOnce();

        $systemManager = new SystemManager(
            $this->mkcert->reveal(),
            $this->validator->reveal(),
            $this->entityManager->reveal(),
            $this->environmentRepository->reveal(),
            $this->processFactory->reveal()
        );

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
    public function testItThrowsAnExceptionWithInvalidEnvironment(string $type, ?string $domains = null): void
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

        $violation = $this->prophesize(ConstraintViolation::class);
        $violation->getMessage()->shouldBeCalledOnce()->willReturn('Dummy exception');

        $errors = new ConstraintViolationList();
        $errors->add($violation->reveal());

        $this->validator->validate(Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn($errors)
        ;

        $environment = new Environment(basename($this->location), $this->location, $type, $domains);
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
        $systemManager->install($this->location, $type, $domains);
    }

    /**
     * @dataProvider provideMultipleInstallContexts
     */
    public function testItUninstallsEnvironment(string $type, ?string $domains = null): void
    {
        $environment = new Environment(basename($this->location), $this->location, $type, $domains);
        $this->entityManager->remove($environment)->shouldBeCalledOnce();
        $this->entityManager->flush()->shouldBeCalledOnce();

        $systemManager = new SystemManager(
            $this->mkcert->reveal(),
            $this->validator->reveal(),
            $this->entityManager->reveal(),
            $this->environmentRepository->reveal(),
            $this->processFactory->reveal()
        );

        $destination = sprintf('%s/var/docker', $this->location);
        mkdir($destination, 0777, true);
        static::assertDirectoryExists($destination);

        $systemManager->uninstall($environment);
        static::assertDirectoryNotExists($destination);
    }

    public function provideMultipleInstallContexts(): Generator
    {
        yield [Environment::TYPE_MAGENTO2, 'www.magento.localhost magento.localhost'];
        yield [Environment::TYPE_MAGENTO2, ''];

        yield [Environment::TYPE_SYMFONY, 'www.symfony.localhost symfony.localhost'];
        yield [Environment::TYPE_SYMFONY, ''];
    }
}
