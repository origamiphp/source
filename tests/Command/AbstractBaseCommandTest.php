<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\AbstractBaseCommand;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\SystemManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 */
final class AbstractBaseCommandTest extends WebTestCase
{
    use CustomCommandsTrait;

    private $systemManager;
    private $validator;
    private $dockerCompose;
    private $eventDispatcher;

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

        putenv('COLUMNS=120'); // Required by tests running with Github Actions
    }

    public function testItSuccessfullyRunsWithActiveEnvironment(): void
    {
        $this->systemManager->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($this->getFakeEnvironment())
        ;

        $commandTester = new CommandTester($this->getFakeCommand());
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItSuccessfullyRunsWithUserInput(): void
    {
        $this->systemManager->getActiveEnvironment()->shouldBeCalledOnce()->willReturn(null);

        $environment = $this->getFakeEnvironment();
        $this->systemManager->getEnvironmentByName($environment->getName())
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $commandTester = new CommandTester($this->getFakeCommand());
        $commandTester->execute(
            ['environment' => $environment->getName()],
            ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]
        );

        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItSuccessfullyRunsFromLocation(): void
    {
        $this->systemManager->getActiveEnvironment()->shouldBeCalledOnce()->willReturn(null);

        $environment = $this->getFakeEnvironment();
        $this->systemManager->getEnvironmentByLocation(getcwd())
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $commandTester = new CommandTester($this->getFakeCommand());
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        self::assertExceptionIsHandled(
            $this->getFakeCommand(),
            'An environment must be given, please consider using the install command instead.'
        );
    }

    /**
     * Retrieves a fake command based on AbstractBaseCommand with previously defined prophecies.
     */
    private function getFakeCommand(): AbstractBaseCommand
    {
        return new class($this->systemManager->reveal(), $this->validator->reveal(), $this->dockerCompose->reveal(), $this->eventDispatcher->reveal()) extends AbstractBaseCommand {
            /**
             * {@inheritdoc}
             */
            protected function configure(): void
            {
                $this->setName('origami:test');
                $this->setAliases(['test']);

                $this->addArgument(
                    'environment',
                    InputArgument::OPTIONAL,
                    'Name of the environment to prepare'
                );

                $this->setDescription('Dummy description for a temporary test command');
            }

            /**
             * {@inheritdoc}
             */
            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                try {
                    $this->checkPrequisites($input);
                } catch (OrigamiExceptionInterface $e) {
                    $this->io->error($e->getMessage());
                    $exitCode = CommandExitCode::EXCEPTION;
                }

                return $exitCode ?? CommandExitCode::SUCCESS;
            }
        };
    }
}
