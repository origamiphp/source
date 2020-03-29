<?php

declare(strict_types=1);

namespace App\Tests\Command\Additional;

use App\Command\Additional\CheckCommand;
use App\Helper\CommandExitCode;
use App\Tests\AbstractCommandWebTestCase;
use Generator;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Additional\CheckCommand
 */
final class CheckCommandTest extends AbstractCommandWebTestCase
{
    /**
     * @dataProvider provideSystemRequirements
     */
    public function testItPrintsSuccessWithAllRequirements(array $requirements): void
    {
        foreach ($requirements as $name => $description) {
            $this->systemManager->isBinaryInstalled($name)
                ->shouldBeCalledOnce()
                ->willReturn(true)
            ;
        }

        $commandTester = new CommandTester($this->getCheckCommand($requirements));
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Your system is ready.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * @dataProvider provideSystemRequirements
     */
    public function testItPrintsErrorWithoutAllRequirements(array $requirements): void
    {
        foreach ($requirements as $name => $description) {
            $this->systemManager->isBinaryInstalled($name)
                ->shouldBeCalledOnce()
                ->willReturn(false)
            ;
        }

        $commandTester = new CommandTester($this->getCheckCommand($requirements));
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] At least one system requirement is missing.', $display);
        static::assertGreaterThan(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function provideSystemRequirements(): Generator
    {
        yield [
            [
                'php' => 'The programming language used by Origami.',
                'composer' => 'The dependency manager for PHP.',
                'homebrew' => 'The package manager for macOS.',
            ],
        ];
    }

    /**
     * Retrieves the \App\Command\Additional\CheckCommand instance to use within the tests.
     */
    protected function getCheckCommand(array $requirements): CheckCommand
    {
        return new CheckCommand(
            $this->database->reveal(),
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal(),
            $this->processProxy->reveal(),
            $requirements
        );
    }
}
