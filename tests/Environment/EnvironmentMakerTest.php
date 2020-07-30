<?php

declare(strict_types=1);

namespace App\Tests\Environment;

use App\Environment\EnvironmentMaker;
use App\Environment\EnvironmentMaker\DockerHub;
use App\Environment\EnvironmentMaker\RequirementsChecker;
use App\Environment\EnvironmentMaker\TechnologyIdentifier;
use App\Helper\Validator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Environment\EnvironmentMaker
 */
final class EnvironmentMakerTest extends TestCase
{
    use ProphecyTrait;

    public function testItAsksAndReturnsDefaultEnvironmentName(): void
    {
        [$technologyIdentifier, $dockerHub, $requirementsChecker, $validator] = $this->prophesizeEnvironmentMakerArguments();

        $environmentMaker = new EnvironmentMaker(
            $technologyIdentifier->reveal(),
            $dockerHub->reveal(),
            $requirementsChecker->reveal(),
            $validator->reveal()
        );

        $command = $this->createEnvironmentNameCommand($environmentMaker);
        $this->assertCommandOutput($command, [''], "Result = default-name\n");
    }

    public function testItAsksAndReturnsCustomEnvironmentName(): void
    {
        [$technologyIdentifier, $dockerHub, $requirementsChecker, $validator] = $this->prophesizeEnvironmentMakerArguments();

        $environmentMaker = new EnvironmentMaker(
            $technologyIdentifier->reveal(),
            $dockerHub->reveal(),
            $requirementsChecker->reveal(),
            $validator->reveal()
        );

        $command = $this->createEnvironmentNameCommand($environmentMaker);
        $this->assertCommandOutput($command, ['custom-name'], "Result = custom-name\n");
    }

    public function testItAsksAndReturnsDefaultEnvironmentType(): void
    {
        [$technologyIdentifier, $dockerHub, $requirementsChecker, $validator] = $this->prophesizeEnvironmentMakerArguments();

        $environmentMaker = new EnvironmentMaker(
            $technologyIdentifier->reveal(),
            $dockerHub->reveal(),
            $requirementsChecker->reveal(),
            $validator->reveal()
        );

        $technologyIdentifier->identify(Argument::type('string'))->shouldBeCalledOnce()->willReturn('symfony');

        $command = $this->createEnvironmentTypeCommand($environmentMaker);
        $this->assertCommandOutput($command, [''], "Result = symfony\n");
    }

    public function testItAsksAndReturnsCustomEnvironmentType(): void
    {
        [$technologyIdentifier, $dockerHub, $requirementsChecker, $validator] = $this->prophesizeEnvironmentMakerArguments();

        $environmentMaker = new EnvironmentMaker(
            $technologyIdentifier->reveal(),
            $dockerHub->reveal(),
            $requirementsChecker->reveal(),
            $validator->reveal()
        );

        $technologyIdentifier->identify(Argument::type('string'))->shouldBeCalledOnce()->willReturn('symfony');

        $command = $this->createEnvironmentTypeCommand($environmentMaker);
        $this->assertCommandOutput($command, ['sylius'], "Result = sylius\n");
    }

    public function testItAsksAndReturnsDefaultPhpVersion(): void
    {
        [$technologyIdentifier, $dockerHub, $requirementsChecker, $validator] = $this->prophesizeEnvironmentMakerArguments();

        $environmentMaker = new EnvironmentMaker(
            $technologyIdentifier->reveal(),
            $dockerHub->reveal(),
            $requirementsChecker->reveal(),
            $validator->reveal()
        );

        $dockerHub->getImageTags(Argument::type('string'))->shouldBeCalledOnce()->willReturn(['7.3', '7.4', 'latest']);

        $command = $this->createEnvironmentPhpVersionCommand($environmentMaker);
        $this->assertCommandOutput($command, [''], "Result = latest\n");
    }

    public function testItAsksAndReturnsCustomPhpVersion(): void
    {
        [$technologyIdentifier, $dockerHub, $requirementsChecker, $validator] = $this->prophesizeEnvironmentMakerArguments();

        $environmentMaker = new EnvironmentMaker(
            $technologyIdentifier->reveal(),
            $dockerHub->reveal(),
            $requirementsChecker->reveal(),
            $validator->reveal()
        );

        $dockerHub->getImageTags(Argument::type('string'))->shouldBeCalledOnce()->willReturn(['7.3', '7.4', 'latest']);

        $command = $this->createEnvironmentPhpVersionCommand($environmentMaker);
        $this->assertCommandOutput($command, ['7.4'], "Result = 7.4\n");
    }

    public function testItAsksAndReturnsNoDomains(): void
    {
        [$technologyIdentifier, $dockerHub, $requirementsChecker, $validator] = $this->prophesizeEnvironmentMakerArguments();

        $environmentMaker = new EnvironmentMaker(
            $technologyIdentifier->reveal(),
            $dockerHub->reveal(),
            $requirementsChecker->reveal(),
            $validator->reveal()
        );

        $requirementsChecker->canMakeLocallyTrustedCertificates()->shouldBeCalledOnce()->willReturn(true);

        $command = $this->createEnvironmentDomainsCommand($environmentMaker);
        $this->assertCommandOutput($command, [''], "Result = N/A\n");
    }

    public function testItAsksAndReturnsDefaultDomains(): void
    {
        [$technologyIdentifier, $dockerHub, $requirementsChecker, $validator] = $this->prophesizeEnvironmentMakerArguments();

        $environmentMaker = new EnvironmentMaker(
            $technologyIdentifier->reveal(),
            $dockerHub->reveal(),
            $requirementsChecker->reveal(),
            $validator->reveal()
        );

        $requirementsChecker->canMakeLocallyTrustedCertificates()->shouldBeCalledOnce()->willReturn(true);
        $validator->validateHostname(Argument::type('string'))->shouldBeCalledOnce()->willReturn(true);

        $command = $this->createEnvironmentDomainsCommand($environmentMaker);
        $this->assertCommandOutput($command, ['yes', ''], "Result = symfony.localhost\n");
    }

    public function testItAsksAndReturnsCustomDomains(): void
    {
        [$technologyIdentifier, $dockerHub, $requirementsChecker, $validator] = $this->prophesizeEnvironmentMakerArguments();

        $environmentMaker = new EnvironmentMaker(
            $technologyIdentifier->reveal(),
            $dockerHub->reveal(),
            $requirementsChecker->reveal(),
            $validator->reveal()
        );

        $requirementsChecker->canMakeLocallyTrustedCertificates()->shouldBeCalledOnce()->willReturn(true);
        $validator->validateHostname(Argument::type('string'))->shouldBeCalledOnce()->willReturn(true);

        $command = $this->createEnvironmentDomainsCommand($environmentMaker);
        $this->assertCommandOutput(
            $command,
            ['yes', 'custom-domain.localhost'],
            "Result = custom-domain.localhost\n"
        );
    }

    public function testItAsksAndRejectsInvalidCustomDomains(): void
    {
        [$technologyIdentifier, $dockerHub, $requirementsChecker, $validator] = $this->prophesizeEnvironmentMakerArguments();

        $environmentMaker = new EnvironmentMaker(
            $technologyIdentifier->reveal(),
            $dockerHub->reveal(),
            $requirementsChecker->reveal(),
            $validator->reveal()
        );

        $requirementsChecker->canMakeLocallyTrustedCertificates()->shouldBeCalledOnce()->willReturn(true);
        $validator->validateHostname('@#&!$€*')->shouldBeCalledOnce()->willReturn(false);
        $validator->validateHostname('custom-domain.localhost')->shouldBeCalledOnce()->willReturn(true);

        $command = $this->createEnvironmentDomainsCommand($environmentMaker);
        $this->assertCommandOutput(
            $command,
            ['yes', '@#&!$€*', 'custom-domain.localhost'],
            "Result = custom-domain.localhost\n"
        );
    }

    public function testItDoesNotAskDomainsWithoutMkcert(): void
    {
        [$technologyIdentifier, $dockerHub, $requirementsChecker, $validator] = $this->prophesizeEnvironmentMakerArguments();

        $environmentMaker = new EnvironmentMaker(
            $technologyIdentifier->reveal(),
            $dockerHub->reveal(),
            $requirementsChecker->reveal(),
            $validator->reveal()
        );

        $requirementsChecker->canMakeLocallyTrustedCertificates()->shouldBeCalledOnce()->willReturn(false);
        $validator->validateHostname(Argument::type('string'))->shouldNotBeCalled();

        $command = $this->createEnvironmentDomainsCommand($environmentMaker);
        $this->assertCommandOutput($command, [], "Result = N/A\n");
    }

    /**
     * Prophesizes arguments needed by the \App\Environment\EnvironmentMaker class.
     */
    private function prophesizeEnvironmentMakerArguments(): array
    {
        return [
            $this->prophesize(TechnologyIdentifier::class),
            $this->prophesize(DockerHub::class),
            $this->prophesize(RequirementsChecker::class),
            $this->prophesize(Validator::class),
        ];
    }

    /**
     * Creates a fake PHP class to simulate the question about the environment name.
     */
    private function createEnvironmentNameCommand(EnvironmentMaker $environmentMaker): Command
    {
        return new class($environmentMaker) extends Command {
            use FakeCommandConstructor;
            protected static $defaultName = 'origami:test';

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $io = new SymfonyStyle($input, $output);
                $io->writeln('Result = '.$this->environmentMaker->askEnvironmentName($io, 'default-name'));

                return Command::SUCCESS;
            }
        };
    }

    /**
     * Creates a fake PHP class to simulate the question about the environment type.
     */
    private function createEnvironmentTypeCommand(EnvironmentMaker $environmentMaker): Command
    {
        return new class($environmentMaker) extends Command {
            use FakeCommandConstructor;
            protected static $defaultName = 'origami:test';

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $io = new SymfonyStyle($input, $output);
                $io->writeln('Result = '.$this->environmentMaker->askEnvironmentType($io, '.'));

                return Command::SUCCESS;
            }
        };
    }

    /**
     * Creates a fake PHP class to simulate the question about the environment PHP version.
     */
    private function createEnvironmentPhpVersionCommand(EnvironmentMaker $environmentMaker): Command
    {
        return new class($environmentMaker) extends Command {
            use FakeCommandConstructor;
            protected static $defaultName = 'origami:test';

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $io = new SymfonyStyle($input, $output);
                $io->writeln('Result = '.$this->environmentMaker->askPhpVersion($io, 'symfony'));

                return Command::SUCCESS;
            }
        };
    }

    /**
     * Creates a fake PHP class to simulate the question about the environment domains.
     */
    private function createEnvironmentDomainsCommand(EnvironmentMaker $environmentMaker): Command
    {
        return new class($environmentMaker) extends Command {
            use FakeCommandConstructor;
            protected static $defaultName = 'origami:test';

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $io = new SymfonyStyle($input, $output);
                $io->writeln('Result = '.($this->environmentMaker->askDomains($io, 'symfony') ?? 'N/A'));

                return Command::SUCCESS;
            }
        };
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
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
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
