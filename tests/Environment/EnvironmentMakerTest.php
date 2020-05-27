<?php

declare(strict_types=1);

namespace App\Tests\Environment;

use App\Environment\EnvironmentMaker;
use App\Environment\EnvironmentMaker\DockerHub;
use App\Environment\EnvironmentMaker\RequirementsChecker;
use App\Environment\EnvironmentMaker\TechnologyIdentifier;
use App\Helper\CommandExitCode;
use App\Validator\Constraints\LocalDomains;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @covers \App\Environment\EnvironmentMaker
 */
final class EnvironmentMakerTest extends TestCase
{
    /** @var Prophet */
    private $prophet;

    /** @var ObjectProphecy */
    private $technologyIdentifier;

    /** @var ObjectProphecy */
    private $dockerHub;

    /** @var ObjectProphecy */
    private $requirementsChecker;

    /** @var ObjectProphecy */
    private $validator;

    /** @var EnvironmentMaker */
    private $environmentMaker;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prophet = new Prophet();
        $this->technologyIdentifier = $this->prophet->prophesize(TechnologyIdentifier::class);
        $this->dockerHub = $this->prophet->prophesize(DockerHub::class);
        $this->requirementsChecker = $this->prophet->prophesize(RequirementsChecker::class);
        $this->validator = $this->prophet->prophesize(ValidatorInterface::class);

        $this->environmentMaker = new EnvironmentMaker(
            $this->technologyIdentifier->reveal(),
            $this->dockerHub->reveal(),
            $this->requirementsChecker->reveal(),
            $this->validator->reveal()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->prophet->checkPredictions();
    }

    public function testItAsksAndReturnsDefaultEnvironmentName(): void
    {
        $command = new class($this->environmentMaker) extends Command {
            use FakeCommandConstructor;
            protected static $defaultName = 'origami:test';

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $io = new SymfonyStyle($input, $output);
                $io->writeln('Result = '.$this->environmentMaker->askEnvironmentName($io, 'default-name'));

                return CommandExitCode::SUCCESS;
            }
        };

        $this->assertCommandOutput($command, [''], "Result = default-name\n");
    }

    public function testItAsksAndReturnsCustomEnvironmentName(): void
    {
        $command = new class($this->environmentMaker) extends Command {
            use FakeCommandConstructor;
            protected static $defaultName = 'origami:test';

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $io = new SymfonyStyle($input, $output);
                $io->writeln('Result = '.$this->environmentMaker->askEnvironmentName($io, 'default-name'));

                return CommandExitCode::SUCCESS;
            }
        };

        $this->assertCommandOutput($command, ['custom-name'], "Result = custom-name\n");
    }

    public function testItAsksAndReturnsDefaultEnvironmentType(): void
    {
        $command = new class($this->environmentMaker) extends Command {
            use FakeCommandConstructor;
            protected static $defaultName = 'origami:test';

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $io = new SymfonyStyle($input, $output);
                $io->writeln('Result = '.$this->environmentMaker->askEnvironmentType($io, '.'));

                return CommandExitCode::SUCCESS;
            }
        };

        (new MethodProphecy($this->technologyIdentifier, 'identify', [Argument::type('string')]))
            ->shouldBeCalledOnce()
            ->willReturn('symfony')
        ;

        $this->assertCommandOutput($command, [''], "Result = symfony\n");
    }

    public function testItAsksAndReturnsCustomEnvironmentType(): void
    {
        $command = new class($this->environmentMaker) extends Command {
            use FakeCommandConstructor;
            protected static $defaultName = 'origami:test';

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $io = new SymfonyStyle($input, $output);
                $io->writeln('Result = '.$this->environmentMaker->askEnvironmentType($io, '.'));

                return CommandExitCode::SUCCESS;
            }
        };

        (new MethodProphecy($this->technologyIdentifier, 'identify', [Argument::type('string')]))
            ->shouldBeCalledOnce()
            ->willReturn('symfony')
        ;

        $this->assertCommandOutput($command, ['sylius'], "Result = sylius\n");
    }

    public function testItAsksAndReturnsDefaultPhpVersion(): void
    {
        $command = new class($this->environmentMaker) extends Command {
            use FakeCommandConstructor;
            protected static $defaultName = 'origami:test';

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $io = new SymfonyStyle($input, $output);
                $io->writeln('Result = '.$this->environmentMaker->askPhpVersion($io, 'symfony'));

                return CommandExitCode::SUCCESS;
            }
        };

        (new MethodProphecy($this->dockerHub, 'getImageTags', [Argument::type('string')]))
            ->shouldBeCalledOnce()
            ->willReturn(['7.3', '7.4', 'latest'])
        ;

        $this->assertCommandOutput($command, [''], "Result = latest\n");
    }

    public function testItAsksAndReturnsCustomPhpVersion(): void
    {
        $command = new class($this->environmentMaker) extends Command {
            use FakeCommandConstructor;
            protected static $defaultName = 'origami:test';

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $io = new SymfonyStyle($input, $output);
                $io->writeln('Result = '.$this->environmentMaker->askPhpVersion($io, 'symfony'));

                return CommandExitCode::SUCCESS;
            }
        };

        (new MethodProphecy($this->dockerHub, 'getImageTags', [Argument::type('string')]))
            ->shouldBeCalledOnce()
            ->willReturn(['7.3', '7.4', 'latest'])
        ;

        $this->assertCommandOutput($command, ['7.4'], "Result = 7.4\n");
    }

    public function testItAsksAndReturnsNoDomains(): void
    {
        $command = new class($this->environmentMaker) extends Command {
            use FakeCommandConstructor;
            protected static $defaultName = 'origami:test';

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $io = new SymfonyStyle($input, $output);
                $io->writeln('Result = '.$this->environmentMaker->askDomains($io, 'symfony'));

                return CommandExitCode::SUCCESS;
            }
        };

        (new MethodProphecy($this->requirementsChecker, 'canMakeLocallyTrustedCertificates', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $this->assertCommandOutput($command, [''], "Result = \n");
    }

    public function testItAsksAndReturnsDefaultDomains(): void
    {
        $command = new class($this->environmentMaker) extends Command {
            use FakeCommandConstructor;
            protected static $defaultName = 'origami:test';

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $io = new SymfonyStyle($input, $output);
                $io->writeln('Result = '.$this->environmentMaker->askDomains($io, 'symfony'));

                return CommandExitCode::SUCCESS;
            }
        };

        (new MethodProphecy($this->requirementsChecker, 'canMakeLocallyTrustedCertificates', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        (new MethodProphecy($this->validator, 'validate', [Argument::type('string'), Argument::type(LocalDomains::class)]))
            ->shouldBeCalledOnce()
            ->willReturn(new ConstraintViolationList())
        ;

        $this->assertCommandOutput($command, ['yes', ''], "Result = symfony.localhost www.symfony.localhost\n");
    }

    public function testItAsksAndReturnsCustomDomains(): void
    {
        $command = new class($this->environmentMaker) extends Command {
            use FakeCommandConstructor;
            protected static $defaultName = 'origami:test';

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $io = new SymfonyStyle($input, $output);
                $io->writeln('Result = '.$this->environmentMaker->askDomains($io, 'sylius'));

                return CommandExitCode::SUCCESS;
            }
        };

        (new MethodProphecy($this->requirementsChecker, 'canMakeLocallyTrustedCertificates', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        (new MethodProphecy($this->validator, 'validate', [Argument::type('string'), Argument::type(LocalDomains::class)]))
            ->shouldBeCalledOnce()
            ->willReturn(new ConstraintViolationList())
        ;

        $this->assertCommandOutput(
            $command,
            ['yes', 'custom-domain.localhost'],
            "Result = custom-domain.localhost\n"
        );
    }

    public function testItAsksAndRejectsInvalidCustomDomains(): void
    {
        $command = new class($this->environmentMaker) extends Command {
            use FakeCommandConstructor;
            protected static $defaultName = 'origami:test';

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $io = new SymfonyStyle($input, $output);
                $io->writeln('Result = '.$this->environmentMaker->askDomains($io, 'magento'));

                return CommandExitCode::SUCCESS;
            }
        };

        (new MethodProphecy($this->requirementsChecker, 'canMakeLocallyTrustedCertificates', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $violation = $this->prophet->prophesize(ConstraintViolation::class);
        (new MethodProphecy($violation, 'getMessage', []))
            ->shouldBeCalledOnce()
            ->willReturn('Dummy exception.')
        ;

        $errors = new ConstraintViolationList();
        $errors->add($violation->reveal());

        (new MethodProphecy($this->validator, 'validate', ['@#&!$€*', Argument::type(LocalDomains::class)]))
            ->shouldBeCalledOnce()
            ->willReturn($errors)
        ;

        (new MethodProphecy($this->validator, 'validate', ['custom-domain.localhost', Argument::type(LocalDomains::class)]))
            ->shouldBeCalledOnce()
            ->willReturn(new ConstraintViolationList())
        ;

        $this->assertCommandOutput(
            $command,
            ['yes', '@#&!$€*', 'custom-domain.localhost'],
            "Result = custom-domain.localhost\n"
        );
    }

    /**
     * Asserts that the command output will end with the given output for the given inputs.
     */
    private function assertCommandOutput(Command $command, array $inputs, string $output): void
    {
        $commandTester = new CommandTester($command);
        $commandTester->setInputs($inputs);
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_NORMAL]);

        static::assertStringEndsWith($output, $commandTester->getDisplay());
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }
}

trait FakeCommandConstructor
{
    /** @var EnvironmentMaker */
    protected $environmentMaker;

    public function __construct(EnvironmentMaker $environmentMaker, string $name = null)
    {
        parent::__construct($name);
        $this->environmentMaker = $environmentMaker;
    }
}
