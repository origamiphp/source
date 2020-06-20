<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Environment\EnvironmentMaker\RequirementsChecker;
use App\EventSubscriber\CommandSubscriber;
use App\Exception\MissingRequirementException;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophet;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 *
 * @covers \App\EventSubscriber\CommandSubscriber
 */
final class CommandSubscriberTest extends WebTestCase
{
    /** @var Prophet */
    private $prophet;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prophet = new Prophet();
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
     * @throws MissingRequirementException
     */
    public function testItDoesNotCheckRequirementsWithSymfonyCommands(): void
    {
        $requirementsChecker = $this->prophet->prophesize(RequirementsChecker::class);
        (new MethodProphecy($requirementsChecker, 'checkMandatoryRequirements', []))
            ->shouldNotBeCalled()
        ;
        (new MethodProphecy($requirementsChecker, 'checkNonMandatoryRequirements', []))
            ->shouldNotBeCalled()
        ;

        $command = $this->prophet->prophesize(Command::class);
        (new MethodProphecy($command, 'getName', []))
            ->shouldBeCalledOnce()
            ->willReturn('app:fake-command')
        ;

        $input = $this->prophet->prophesize(InputInterface::class);
        $output = $this->prophet->prophesize(OutputInterface::class);
        (new MethodProphecy($output, 'isVerbose', []))
            ->shouldNotBeCalled()
        ;

        $subscriber = new CommandSubscriber($requirementsChecker->reveal());
        $subscriber->onConsoleCommand(new ConsoleCommandEvent($command->reveal(), $input->reveal(), $output->reveal()));

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }

    /**
     * @throws MissingRequirementException
     */
    public function testItDetectsMissingMandatoryBinaryWithOrigamiCommands(): void
    {
        $requirementsChecker = $this->prophet->prophesize(RequirementsChecker::class);
        (new MethodProphecy($requirementsChecker, 'checkMandatoryRequirements', []))
            ->shouldBeCalled()
            ->willReturn([
                ['name' => 'docker', 'description' => '', 'status' => true],
                ['name' => 'docker-compose', 'description' => '', 'status' => false],
            ])
        ;
        (new MethodProphecy($requirementsChecker, 'checkNonMandatoryRequirements', []))
            ->shouldBeCalled()
            ->willReturn([
                ['name' => 'mutagen', 'description' => '', 'status' => true],
                ['name' => 'mkcert', 'description' => '', 'status' => true],
            ])
        ;

        $command = $this->prophet->prophesize(Command::class);
        (new MethodProphecy($command, 'getName', []))
            ->shouldBeCalledOnce()
            ->willReturn('origami:fake-command')
        ;

        $this->expectExceptionObject(
            new MissingRequirementException('At least one mandatory binary is missing from your system.')
        );

        $input = new ArgvInput();
        $output = new BufferedOutput();
        $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);

        $subscriber = new CommandSubscriber($requirementsChecker->reveal());
        $subscriber->onConsoleCommand(new ConsoleCommandEvent($command->reveal(), $input, $output));
    }

    /**
     * @throws MissingRequirementException
     */
    public function testItDetectsMissingNonMandatoryBinaryWithOrigamiCommands(): void
    {
        $requirementsChecker = $this->prophet->prophesize(RequirementsChecker::class);
        (new MethodProphecy($requirementsChecker, 'checkMandatoryRequirements', []))
            ->shouldBeCalled()
            ->willReturn([
                ['name' => 'docker', 'description' => '', 'status' => true],
                ['name' => 'docker-compose', 'description' => '', 'status' => true],
            ])
        ;
        (new MethodProphecy($requirementsChecker, 'checkNonMandatoryRequirements', []))
            ->shouldBeCalled()
            ->willReturn([
                ['name' => 'mutagen', 'description' => '', 'status' => true],
                ['name' => 'mkcert', 'description' => '', 'status' => false],
            ])
        ;

        $command = $this->prophet->prophesize(Command::class);
        (new MethodProphecy($command, 'getName', []))
            ->shouldBeCalledOnce()
            ->willReturn('origami:fake-command')
        ;

        $input = new ArgvInput();
        $output = new BufferedOutput();
        $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);

        $subscriber = new CommandSubscriber($requirementsChecker->reveal());
        $subscriber->onConsoleCommand(new ConsoleCommandEvent($command->reveal(), $input, $output));

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }
}
