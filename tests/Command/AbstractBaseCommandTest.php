<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\AbstractBaseCommand;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use App\Tests\TestFakeEnvironmentTrait;
use Prophecy\Prophecy\MethodProphecy;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 */
final class AbstractBaseCommandTest extends AbstractCommandWebTestCase
{
    use TestFakeEnvironmentTrait;

    public function testItSuccessfullyRunsWithActiveEnvironment(): void
    {
        (new MethodProphecy($this->database, 'getActiveEnvironment', []))
            ->shouldBeCalledOnce()
            ->willReturn($this->getFakeEnvironment())
        ;

        $commandTester = new CommandTester($this->getFakeCommand());
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItSuccessfullyRunsWithUserInput(): void
    {
        $environment = $this->getFakeEnvironment();

        (new MethodProphecy($this->database, 'getActiveEnvironment', []))
            ->shouldBeCalledOnce()
            ->willReturn(null)
        ;

        (new MethodProphecy($this->database, 'getEnvironmentByName', [$environment->getName()]))
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
        $environment = $this->getFakeEnvironment();

        (new MethodProphecy($this->database, 'getActiveEnvironment', []))
            ->shouldBeCalledOnce()
            ->willReturn(null)
        ;

        (new MethodProphecy($this->processProxy, 'getWorkingDirectory', []))
            ->shouldBeCalledOnce()
            ->willReturn('')
        ;

        (new MethodProphecy($this->database, 'getEnvironmentByLocation', ['']))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $commandTester = new CommandTester($this->getFakeCommand());
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        (new MethodProphecy($this->processProxy, 'getWorkingDirectory', []))
            ->shouldBeCalledOnce()
            ->willReturn('')
        ;

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
        return new class($this->database->reveal(), $this->systemManager->reveal(), $this->validator->reveal(), $this->dockerCompose->reveal(), $this->eventDispatcher->reveal(), $this->processProxy->reveal()) extends AbstractBaseCommand {
            protected static $defaultName = 'origami:test';

            /**
             * {@inheritdoc}
             */
            protected function configure(): void
            {
                $this->setAliases(['test']);
                $this->setDescription('Dummy description for a temporary test command');

                $this->addArgument(
                    'environment',
                    InputArgument::OPTIONAL,
                    'Name of the environment to prepare'
                );
            }

            /**
             * {@inheritdoc}
             */
            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $io = new SymfonyStyle($input, $output);

                try {
                    $this->getEnvironment($input);
                } catch (OrigamiExceptionInterface $exception) {
                    $io->error($exception->getMessage());
                    $exitCode = CommandExitCode::EXCEPTION;
                }

                return $exitCode ?? CommandExitCode::SUCCESS;
            }
        };
    }
}
