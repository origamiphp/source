<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Entity\Environment;
use App\Helper\CommandExitCode;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

trait CustomCommandsTrait
{
    /**
     * Retrieves a new fake Environment instance.
     *
     * @return Environment
     */
    public function getFakeEnvironment(): Environment
    {
        $environment = new Environment();
        $environment->setName('origami');
        $environment->setLocation('~/Sites/origami');
        $environment->setType('symfony');

        return $environment;
    }

    /**
     * Asserts that the environment details are displayed in verbose mode.
     *
     * @param Environment $environment
     * @param string      $display
     */
    public static function assertDisplayIsVerbose(Environment $environment, string $display): void
    {
        static::assertStringContainsString('[OK] An environment is currently running.', $display);
        static::assertStringContainsString("Environment location: {$environment->getLocation()}", $display);
        static::assertStringContainsString("Environment type: {$environment->getType()}", $display);
    }

    /**
     * Executes the given command and asserts the exception is properly handled.
     *
     * @param Command $command
     * @param string  $message
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
