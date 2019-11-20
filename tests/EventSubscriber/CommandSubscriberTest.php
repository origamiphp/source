<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\CommandSubscriber;
use App\Helper\ApplicationFactory;
use App\Kernel;
use App\Tests\TestLocationTrait;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Command\ConfigDebugCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * @internal
 *
 * @covers \App\EventSubscriber\CommandSubscriber
 */
final class CommandSubscriberTest extends WebTestCase
{
    use TestLocationTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->createLocation();
    }

    /**
     * {@inheritDoc).
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->removeLocation();
    }

    public function testItInitializesTheDatabase(): void
    {
        $kernel = $this->prophesize(Kernel::class);
        $kernel->getCustomDir()->shouldBeCalledOnce()->willReturn($this->location);

        $databaseName = 'tests.sqlite';
        $databasePath = $this->location.\DIRECTORY_SEPARATOR.$databaseName;
        static::assertFileNotExists($databasePath);

        $application = $this->prophesize(Application::class);
        $application->setAutoExit(false)->shouldBeCalledOnce();
        $application->run(
            new ArrayInput(['command' => 'doctrine:schema:create', '--force']),
            new NullOutput()
        )->shouldBeCalledOnce();

        $applicationFactory = $this->prophesize(ApplicationFactory::class);
        $applicationFactory->create($kernel->reveal())->shouldBeCalledOnce()->willReturn($application->reveal());

        $event = $this->prophesize(ConsoleCommandEvent::class);
        $event->getCommand()->shouldBeCalledOnce()->willReturn($this->getFakeCommand());

        $subscriber = new CommandSubscriber($kernel->reveal(), $applicationFactory->reveal(), $databaseName);
        $subscriber->onConsoleCommand($event->reveal());
    }

    public function testItDoesNotInitializeTwiceTheDatabase(): void
    {
        $kernel = $this->prophesize(Kernel::class);
        $kernel->getCustomDir()->shouldBeCalledOnce()->willReturn($this->location);

        $databaseName = 'tests.sqlite';
        $databasePath = $this->location.\DIRECTORY_SEPARATOR.$databaseName;
        touch($databasePath);

        $applicationFactory = $this->prophesize(ApplicationFactory::class);
        $applicationFactory->create()->shouldNotBeCalled();

        $event = $this->prophesize(ConsoleCommandEvent::class);
        $event->getCommand()->shouldBeCalledOnce()->willReturn($this->getFakeCommand());

        $subscriber = new CommandSubscriber($kernel->reveal(), $applicationFactory->reveal(), $databaseName);
        $subscriber->onConsoleCommand($event->reveal());
    }

    public function testItDoesNotInitializeTheDatabaseWithExternalCommands(): void
    {
        $kernel = $this->prophesize(Kernel::class);
        $kernel->getCustomDir()->shouldNotBeCalled();

        $databaseName = 'tests.sqlite';
        $databasePath = $this->location.\DIRECTORY_SEPARATOR.$databaseName;
        touch($databasePath);

        $applicationFactory = $this->prophesize(ApplicationFactory::class);
        $applicationFactory->create()->shouldNotBeCalled();

        $event = $this->prophesize(ConsoleCommandEvent::class);
        $event->getCommand()->shouldBeCalledOnce()->willReturn(new ConfigDebugCommand());

        $subscriber = new CommandSubscriber($kernel->reveal(), $applicationFactory->reveal(), $databaseName);
        $subscriber->onConsoleCommand($event->reveal());
    }

    /**
     * Retrieves a fake command based on AbstractBaseCommand with previously defined prophecies.
     */
    private function getFakeCommand(): Command
    {
        return new class() extends Command {
            /**
             * {@inheritdoc}
             */
            protected function configure(): void
            {
                $this->setName('origami:test');
                $this->setAliases(['test']);

                $this->setDescription('Dummy description for a temporary test command');
            }
        };
    }
}
