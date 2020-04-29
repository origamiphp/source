<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\CommandSubscriber;
use App\Exception\InvalidConfigurationException;
use App\Middleware\SystemManager;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophet;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 *
 * @covers \App\EventSubscriber\CommandSubscriber
 */
final class CommandSubscriberTest extends WebTestCase
{
    /** @var Prophet */
    protected $prophet;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prophet = new Prophet();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->prophet->checkPredictions();
    }

    public function testItDoesNotCheckRequirementsWithSymfonyCommands(): void
    {
        $requirements = ['fake-binary' => 'A dummy binary.'];

        $systemManager = $this->prophet->prophesize(SystemManager::class);
        (new MethodProphecy($systemManager, 'isBinaryInstalled', [Argument::any()]))
            ->shouldNotBeCalled()
        ;

        $command = $this->prophet->prophesize(Command::class);
        (new MethodProphecy($command, 'getName', []))
            ->shouldBeCalledOnce()
            ->willReturn('app:fake-command')
        ;
        $input = $this->prophet->prophesize(InputInterface::class);
        $output = $this->prophet->prophesize(OutputInterface::class);

        $subscriber = new CommandSubscriber($requirements, $systemManager->reveal());
        $subscriber->onConsoleCommand(new ConsoleCommandEvent($command->reveal(), $input->reveal(), $output->reveal()));

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    public function testItDetectsExistingBinaryWithOrigamiCommands(): void
    {
        $requirements = ['fake-binary' => 'A dummy binary.'];

        $systemManager = $this->prophet->prophesize(SystemManager::class);
        (new MethodProphecy($systemManager, 'isBinaryInstalled', ['fake-binary']))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $command = $this->prophet->prophesize(Command::class);
        (new MethodProphecy($command, 'getName', []))
            ->shouldBeCalledOnce()
            ->willReturn('origami:fake-command')
        ;
        $input = $this->prophet->prophesize(InputInterface::class);
        $output = $this->prophet->prophesize(OutputInterface::class);

        $subscriber = new CommandSubscriber($requirements, $systemManager->reveal());
        $subscriber->onConsoleCommand(new ConsoleCommandEvent($command->reveal(), $input->reveal(), $output->reveal()));

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    public function testItDetectsMissingBinaryWithOrigamiCommands(): void
    {
        $requirements = ['fake-binary' => 'A dummy binary.'];

        $systemManager = $this->prophet->prophesize(SystemManager::class);
        (new MethodProphecy($systemManager, 'isBinaryInstalled', ['fake-binary']))
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $command = $this->prophet->prophesize(Command::class);
        (new MethodProphecy($command, 'getName', []))
            ->shouldBeCalledOnce()
            ->willReturn('origami:fake-command')
        ;

        $this->expectExceptionObject(
            new InvalidConfigurationException('At least one binary is missing from your system.')
        );

        $subscriber = new CommandSubscriber($requirements, $systemManager->reveal());
        $subscriber->onConsoleCommand(new ConsoleCommandEvent($command->reveal(), new ArgvInput(), new BufferedOutput()));
    }
}
