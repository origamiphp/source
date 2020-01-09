<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Environment;
use App\Helper\CommandExitCode;
use App\Helper\ProcessProxy;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\SystemManager;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @coversNothing
 */
abstract class AbstractCommandWebTestCase extends WebTestCase
{
    /** @var ObjectProphecy|SystemManager */
    protected ObjectProphecy $systemManager;

    /** @var ObjectProphecy|ValidatorInterface */
    protected ObjectProphecy $validator;

    /** @var DockerCompose|ObjectProphecy */
    protected ObjectProphecy $dockerCompose;

    /** @var EventDispatcherInterface|ObjectProphecy */
    protected ObjectProphecy $eventDispatcher;

    /** @var ObjectProphecy|ProcessProxy */
    protected ObjectProphecy $processProxy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->systemManager = $this->prophesize(SystemManager::class);
        $this->validator = $this->prophesize(ValidatorInterface::class);
        $this->dockerCompose = $this->prophesize(DockerCompose::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->processProxy = $this->prophesize(ProcessProxy::class);

        putenv('COLUMNS=120'); // Required by tests running with Github Actions
    }

    /**
     * Asserts that the environment details are displayed in verbose mode.
     */
    public static function assertDisplayIsVerbose(Environment $environment, string $display): void
    {
        static::assertStringContainsString('[OK] An environment is currently running.', $display);
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
