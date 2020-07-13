<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\AbstractBaseCommand;
use App\Exception\FilesystemException;
use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use App\Helper\CurrentContext;
use App\Tests\TestCommandTrait;
use App\Tests\TestFakeEnvironmentTrait;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
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
final class AbstractBaseCommandTest extends WebTestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestFakeEnvironmentTrait;

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItDoesPrintDetailsWhenVerbose(): void
    {
        $environment = $this->getFakeEnvironment();
        $currentContext = $this->prophesize(CurrentContext::class);

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);

        $commandTester = new CommandTester($this->createFakeOrigamiCommand($currentContext));
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        static::assertStringContainsString('[OK] An environment is currently running.', $commandTester->getDisplay());
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItDoesNotPrintDetailsWhenNotVerbose(): void
    {
        $environment = $this->getFakeEnvironment();
        $currentContext = $this->prophesize(CurrentContext::class);

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);

        $commandTester = new CommandTester($this->createFakeOrigamiCommand($currentContext));
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_NORMAL]);

        static::assertStringNotContainsString('[OK] An environment is currently running.', $commandTester->getDisplay());
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * Creates a fake Origami command based on AbstractBaseCommand with previously defined prophecies.
     */
    private function createFakeOrigamiCommand(ObjectProphecy $currentContext): AbstractBaseCommand
    {
        return new class($currentContext->reveal()) extends AbstractBaseCommand {
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
