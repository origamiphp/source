<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\DefaultCommand;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 * @covers \App\Command\DefaultCommand
 */
final class DefaultCommandTest extends WebTestCase
{
    public function testItNotPrintsDefaultCommandInList(): void
    {
        $application = new Application();

        $commandTester = new CommandTester($application->get('list'));
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringNotContainsString('origami:default', $display);
    }

    public function testItPrintsOnlyOrigamiCommands(): void
    {
        $application = new Application();
        $application->add(new DefaultCommand());
        $application->add($this->getFakeCommand());

        $commandTesterWithNativeCommand = new CommandTester($application->get('list'));
        $commandTesterWithNativeCommand->execute(['namespace' => 'origami']);

        $commandTesterWithCustomCommand = new CommandTester($application->get('origami:default'));
        $commandTesterWithCustomCommand->execute([]);

        static::assertSame($commandTesterWithNativeCommand->getDisplay(), $commandTesterWithCustomCommand->getDisplay());
    }

    /**
     * Retrieves a fake command based on AbstractBaseCommand with previously defined prophecies.
     */
    private function getFakeCommand(): Command
    {
        return new class() extends Command {
            protected static $defaultName = 'origami:test';

            /**
             * {@inheritdoc}
             */
            protected function configure(): void
            {
                $this->setAliases(['test']);
                $this->setDescription('Dummy description for a temporary test command');
            }
        };
    }
}
