<?php

declare(strict_types=1);

namespace App\Tests;

use App\ValueObject\EnvironmentEntity;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

trait TestCommandTrait
{
    /**
     * Asserts that the environment details are displayed in verbose mode.
     */
    public static function assertDisplayIsVerbose(EnvironmentEntity $environment, string $display): void
    {
        static::assertStringContainsString('[OK] ', $display);
        static::assertStringContainsString(sprintf('Environment location: %s', $environment->getLocation()), $display);
        static::assertStringContainsString(sprintf('Environment type: %s', $environment->getType()), $display);
    }

    /**
     * Asserts that the result of the given command is successful.
     */
    public static function assertResultIsSuccessful(Command $command, EnvironmentEntity $environment): void
    {
        $commandTester = new CommandTester($command);
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] ', $display);
        static::assertStringContainsString(sprintf('Environment location: %s', $environment->getLocation()), $display);
        static::assertStringContainsString(sprintf('Environment type: %s', $environment->getType()), $display);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * Asserts that the exception of the given command is properly handled.
     */
    public static function assertExceptionIsHandled(Command $command): void
    {
        $commandTester = new CommandTester($command);
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] ', $display);
        static::assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }
}
