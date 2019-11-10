<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\CommandSubscriber;
use App\Helper\Setup;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Command\ConfigDebugCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * @internal
 *
 * @covers \App\EventSubscriber\CommandSubscriber
 */
final class CommandSubscriberTest extends WebTestCase
{
    public function testItInitializesTheApplication(): void
    {
        $setup = $this->prophesize(Setup::class);
        $setup->createProjectDirectory()->shouldBeCalledOnce();
        $setup->initializeProjectDatabase()->shouldBeCalledOnce();

        $event = $this->prophesize(ConsoleCommandEvent::class);
        $event->getCommand()->shouldBeCalledOnce()->willReturn($this->getFakeCommand());

        $subscriber = new CommandSubscriber($setup->reveal());
        $subscriber->onConsoleCommand($event->reveal());
    }

    public function testItDoesNotInitializeTheApplication(): void
    {
        $setup = $this->prophesize(Setup::class);
        $setup->createProjectDirectory()->shouldNotBeCalled();
        $setup->initializeProjectDatabase()->shouldNotBeCalled();

        $event = $this->prophesize(ConsoleCommandEvent::class);
        $event->getCommand()->shouldBeCalledOnce()->willReturn(new ConfigDebugCommand());

        $subscriber = new CommandSubscriber($setup->reveal());
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
