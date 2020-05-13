<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\AbstractBaseCommand;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use App\Helper\CurrentContext;
use App\Tests\TestFakeEnvironmentTrait;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
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

    /** @var ObjectProphecy */
    protected $currentContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currentContext = $this->prophet->prophesize(CurrentContext::class);
    }

    public function testItDoesPrintDetailsWhenVerbose(): void
    {
        $environment = $this->getFakeEnvironment();

        (new MethodProphecy($this->currentContext, 'getEnvironment', [Argument::type(InputInterface::class)]))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $commandTester = new CommandTester($this->getFakeCommand());
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        static::assertStringContainsString('[OK] An environment is currently running.', $commandTester->getDisplay());
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItDoesNotPrintDetailsWhenNotVerbose(): void
    {
        $environment = $this->getFakeEnvironment();

        (new MethodProphecy($this->currentContext, 'getEnvironment', [Argument::type(InputInterface::class)]))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $commandTester = new CommandTester($this->getFakeCommand());
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_NORMAL]);

        static::assertStringNotContainsString('[OK] An environment is currently running.', $commandTester->getDisplay());
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * Retrieves a fake command based on AbstractBaseCommand with previously defined prophecies.
     */
    private function getFakeCommand(): AbstractBaseCommand
    {
        return new class($this->currentContext->reveal()) extends AbstractBaseCommand {
            protected static $defaultName = 'origami:test';

            /** @var CurrentContext */
            protected $currentContext;

            public function __construct(CurrentContext $currentContext, string $name = null)
            {
                parent::__construct($name);

                $this->currentContext = $currentContext;
            }

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
                    $environment = $this->currentContext->getEnvironment($input);

                    if ($output->isVerbose()) {
                        $this->printEnvironmentDetails($environment, $io);
                    }

                    // ...
                } catch (OrigamiExceptionInterface $exception) {
                    $io->error($exception->getMessage());
                    $exitCode = CommandExitCode::EXCEPTION;
                }

                return $exitCode ?? CommandExitCode::SUCCESS;
            }
        };
    }
}
