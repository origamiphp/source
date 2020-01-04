<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Environment;
use App\Helper\CommandExitCode;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\SystemManager;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

trait TestCustomCommandsTrait
{
    /** @var ObjectProphecy|SystemManager */
    private ObjectProphecy $systemManager;

    /** @var ObjectProphecy|ValidatorInterface */
    private ObjectProphecy $validator;

    /** @var DockerCompose|ObjectProphecy */
    private ObjectProphecy $dockerCompose;

    /** @var EventDispatcherInterface|ObjectProphecy */
    private ObjectProphecy $eventDispatcher;

    /**
     * Asserts that the environment details are displayed in verbose mode.
     */
    public static function assertDisplayIsVerbose(Environment $environment, string $display): void
    {
        static::assertStringContainsString('[OK] An environment is currently running.', $display);
        static::assertStringContainsString("Environment location: {$environment->getLocation()}", $display);
        static::assertStringContainsString("Environment type: {$environment->getType()}", $display);
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
