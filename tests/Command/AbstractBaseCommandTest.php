<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\AbstractBaseCommand;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CurrentContext;
use App\Tests\CustomProphecyTrait;
use App\Tests\TestCommandTrait;
use App\Tests\TestEnvironmentTrait;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Command\Command;
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
    use CustomProphecyTrait;
    use TestCommandTrait;
    use TestEnvironmentTrait;

    public function testItDoesPrintDetailsWhenVerbose(): void
    {
        $environment = $this->createEnvironment();
        [$currentContext] = $this->prophesizeObjectArguments();

        $currentContext->loadEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce();
        $currentContext->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);

        $commandTester = new CommandTester($this->createFakeOrigamiCommand($currentContext));
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        static::assertStringContainsString('[OK] ', $commandTester->getDisplay());
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItDoesNotPrintDetailsWhenNotVerbose(): void
    {
        $environment = $this->createEnvironment();
        [$currentContext] = $this->prophesizeObjectArguments();

        $currentContext->loadEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce();
        $currentContext->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);

        $commandTester = new CommandTester($this->createFakeOrigamiCommand($currentContext));
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_NORMAL]);

        static::assertStringNotContainsString('[OK] ', $commandTester->getDisplay());
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * {@inheritdoc}
     */
    protected function prophesizeObjectArguments(): array
    {
        return [
            $this->prophesize(CurrentContext::class),
        ];
    }

    /**
     * Creates a fake Origami command based on AbstractBaseCommand with previously defined prophecies.
     */
    private function createFakeOrigamiCommand(ObjectProphecy $currentContext): AbstractBaseCommand
    {
        return new class($currentContext->reveal()) extends AbstractBaseCommand {
            protected static $defaultName = 'origami:test';
            protected CurrentContext $currentContext;

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
                    $this->currentContext->loadEnvironment($input);
                    $environment = $this->currentContext->getActiveEnvironment();

                    if ($output->isVerbose()) {
                        $this->printEnvironmentDetails($environment, $io);
                    }

                    // ...
                } catch (OrigamiExceptionInterface $exception) {
                    $io->error($exception->getMessage());
                    $exitCode = Command::FAILURE;
                }

                return $exitCode ?? Command::SUCCESS;
            }
        };
    }
}
