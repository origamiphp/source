<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Environment\EnvironmentEntity;
use App\Helper\CommandExitCode;
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
     * Executes the given command and asserts the exception is properly handled.
     */
    public static function assertExceptionIsHandled(Command $command, string $message): void
    {
        $commandTester = new CommandTester($command);
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString($message, $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }
}
