<?php

declare(strict_types=1);

namespace App\Tests\Middleware;

use App\Entity\Environment;
use App\Helper\ProcessFactory;
use App\Middleware\Binary\Mkcert;
use App\Middleware\SystemManager;
use App\Repository\EnvironmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @covers \App\Middleware\SystemManager
 * @covers \App\Repository\EnvironmentRepository
 *
 * @uses \App\Kernel
 */
final class SystemManagerRepositoryTest extends KernelTestCase
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var SystemManager */
    private $systemManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        /** @var ContainerInterface $container */
        $container = $kernel->getContainer();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $container->get('doctrine');
        /** @var EntityManagerInterface entityManager */
        $entityManager = $doctrine->getManager();
        $this->entityManager = $entityManager;

        /** @var EnvironmentRepository $repository */
        $repository = $this->entityManager->getRepository(Environment::class);

        $this->systemManager = new SystemManager(
            $this->prophesize(Mkcert::class)->reveal(),
            $this->prophesize(ValidatorInterface::class)->reveal(),
            $this->entityManager,
            $repository,
            $this->prophesize(ProcessFactory::class)->reveal()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testItRetrievesEnvironmentByName(): void
    {
        /** @var Environment $environment */
        $environment = $this->systemManager->getEnvironmentByName('foo');

        static::assertInstanceOf(Environment::class, $environment);
        static::assertGreaterThanOrEqual(1, $environment->getId());
        static::assertSame('foo', $environment->getName());
        static::assertSame('path/to/foo', $environment->getLocation());
        static::assertTrue($environment->isActive());
        static::assertSame('magento2', $environment->getType());

        static::assertNull($this->systemManager->getEnvironmentByName('azerty'));
    }

    public function testItRetrievesEnvironmentByLocation(): void
    {
        /** @var Environment $environment */
        $environment = $this->systemManager->getEnvironmentByLocation('path/to/foo');

        static::assertInstanceOf(Environment::class, $environment);
        static::assertGreaterThanOrEqual(1, $environment->getId());
        static::assertSame('foo', $environment->getName());
        static::assertSame('path/to/foo', $environment->getLocation());
        static::assertTrue($environment->isActive());
        static::assertSame('magento2', $environment->getType());

        static::assertNull($this->systemManager->getEnvironmentByLocation('azerty'));
    }

    public function testItRetrievesActiveEnvironment(): void
    {
        /** @var Environment $environment */
        $environment = $this->systemManager->getActiveEnvironment();

        static::assertInstanceOf(Environment::class, $environment);
        static::assertTrue($environment->isActive());
    }

    public function testItRetrievesAllEnvironments(): void
    {
        $environments = $this->systemManager->getAllEnvironments();
        static::assertCount(2, $environments);
    }
}
