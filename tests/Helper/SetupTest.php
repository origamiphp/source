<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Helper\ApplicationFactory;
use App\Helper\Setup;
use App\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 *
 * @covers \App\Helper\Setup
 */
final class SetupTest extends TestCase
{
    private $location;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->location = sys_get_temp_dir().'/origami/SetupTest';
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

    public function testItCreatesTheGivenDirectory(): void
    {
        static::assertDirectoryNotExists($this->location);

        $kernel = $this->prophesize(Kernel::class);
        $kernel->getCustomDir()->shouldBeCalledOnce()->willReturn($this->location);

        $setup = new Setup($kernel->reveal(), new ApplicationFactory(), 'tests.sqlite');
        $setup->createProjectDirectory();

        static::assertDirectoryExists($this->location);
    }

    public function testItExecutesDoctrineSchemaCreation(): void
    {
        mkdir($this->location, 0777, true);

        $kernel = $this->prophesize(Kernel::class);
        $kernel->getCustomDir()->shouldBeCalledOnce()->willReturn($this->location);

        $application = $this->prophesize(Application::class);
        $application->setAutoExit(false)->shouldBeCalledOnce();
        $application->run(
            new ArrayInput(['command' => 'doctrine:schema:create', '--force']),
            new NullOutput()
        )->shouldBeCalledOnce();

        $applicationFactory = $this->prophesize(ApplicationFactory::class);
        $applicationFactory->create($kernel->reveal())->shouldBeCalledOnce()->willReturn($application->reveal());

        $setup = new Setup($kernel->reveal(), $applicationFactory->reveal(), 'tests.sqlite');
        $setup->initializeProjectDatabase();
    }
}
