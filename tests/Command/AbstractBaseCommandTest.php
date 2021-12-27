<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\AbstractBaseCommand;
use App\Exception\OrigamiExceptionInterface;
use App\Service\ApplicationContext;
use App\Service\Wrapper\OrigamiStyle;
use App\Tests\TestCommandTrait;
use App\Tests\TestEnvironmentTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 */
final class AbstractBaseCommandTest extends TestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestEnvironmentTrait;

    public function testItDoesPrintDetailsWhenVerbose(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);

        $applicationContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->shouldBeCalledOnce()
        ;

        $applicationContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($this->createEnvironment())
        ;

        $commandTester = new CommandTester($this->createFakeOrigamiCommand($applicationContext));
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        static::assertStringContainsString('[OK] ', $commandTester->getDisplay());
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItDoesNotPrintDetailsWhenNotVerbose(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);

        $applicationContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->shouldBeCalledOnce()
        ;

        $applicationContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($this->createEnvironment())
        ;

        $commandTester = new CommandTester($this->createFakeOrigamiCommand($applicationContext));
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_NORMAL]);

        static::assertStringNotContainsString('[OK] ', $commandTester->getDisplay());
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * Creates a fake Origami command based on AbstractBaseCommand with previously defined prophecies.
     */
    private function createFakeOrigamiCommand(ObjectProphecy $applicationContext): AbstractBaseCommand
    {
        return new class($applicationContext->reveal()) extends AbstractBaseCommand {
            /**
             * {@inheritdoc}
             */
            protected static $defaultName = 'origami:test';

            public function __construct(protected ApplicationContext $applicationContext, string $name = null)
            {
                parent::__construct($name);
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
                $io = new OrigamiStyle($input, $output);

                try {
                    $this->applicationContext->loadEnvironment($input);
                    $environment = $this->applicationContext->getActiveEnvironment();

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
