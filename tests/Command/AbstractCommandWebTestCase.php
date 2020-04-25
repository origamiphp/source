<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\AbstractBaseCommand;
use App\Environment\EnvironmentEntity;
use App\Helper\CommandExitCode;
use App\Helper\ProcessProxy;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\Database;
use App\Middleware\SystemManager;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
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
    /** @var Prophet */
    protected $prophet;

    /** @var ObjectProphecy */
    protected $systemManager;

    /** @var ObjectProphecy */
    protected $database;

    /** @var ObjectProphecy */
    protected $validator;

    /** @var ObjectProphecy */
    protected $dockerCompose;

    /** @var ObjectProphecy */
    protected $eventDispatcher;

    /** @var ObjectProphecy */
    protected $processProxy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prophet = new Prophet();
        $this->systemManager = $this->prophet->prophesize(SystemManager::class);
        $this->database = $this->prophet->prophesize(Database::class);
        $this->validator = $this->prophet->prophesize(ValidatorInterface::class);
        $this->dockerCompose = $this->prophet->prophesize(DockerCompose::class);
        $this->eventDispatcher = $this->prophet->prophesize(EventDispatcherInterface::class);
        $this->processProxy = $this->prophet->prophesize(ProcessProxy::class);

        putenv('COLUMNS=120'); // Required by tests running with Github Actions
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->prophet->checkPredictions();
    }

    /**
     * Asserts that the environment details are displayed in verbose mode.
     */
    public static function assertDisplayIsVerbose(EnvironmentEntity $environment, string $display): void
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

    /**
     * Retrieves the \App\Command\AbstractBaseCommand instance to use within the tests according to the given class.
     */
    protected function getCommand(string $class): AbstractBaseCommand
    {
        if (!is_subclass_of($class, AbstractBaseCommand::class)) {
            throw new \RuntimeException(
                sprintf('Expected subclass of "%s", "%s" given.', AbstractBaseCommand::class, $class)
            );
        }

        return new $class(
            $this->database->reveal(),
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal(),
            $this->processProxy->reveal(),
        );
    }
}
