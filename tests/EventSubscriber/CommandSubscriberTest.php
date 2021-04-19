<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Environment\EnvironmentMaker\RequirementsChecker;
use App\EventSubscriber\CommandSubscriber;
use App\Exception\MissingRequirementException;
use App\Tests\CustomProphecyTrait;
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
    use CustomProphecyTrait;

    /**
     * @throws MissingRequirementException
     */
    public function testItDoesNotCheckRequirementsWithSymfonyCommands(): void
    {
        [$requirementsChecker] = $this->prophesizeObjectArguments();

        $command = $this->prophesize(Command::class);
        $input = $this->prophesize(InputInterface::class);
        $output = $this->prophesize(OutputInterface::class);

        $requirementsChecker->checkMandatoryRequirements()->shouldNotBeCalled();
        $requirementsChecker->checkNonMandatoryRequirements()->shouldNotBeCalled();
        $command->getName()->shouldBeCalledOnce()->willReturn('app:fake-command');

        $subscriber = new CommandSubscriber($requirementsChecker->reveal());
        $subscriber->onConsoleCommand(new ConsoleCommandEvent($command->reveal(), $input->reveal(), $output->reveal()));
    }

    /**
     * @throws MissingRequirementException
     */
    public function testItDetectsMissingMandatoryBinaryWithOrigamiCommands(): void
    {
        [$requirementsChecker] = $this->prophesizeObjectArguments();

        $mandatoryRequirementsStatus = [
            ['name' => 'docker', 'description' => '', 'status' => true],
            ['name' => 'mutagen', 'description' => '', 'status' => false],
        ];
        $nonMandatoryRequirementsStatus = [['name' => 'mkcert', 'description' => '', 'status' => true]];
        $command = $this->prophesize(Command::class);

        $requirementsChecker->checkMandatoryRequirements()->shouldBeCalledOnce()->willReturn($mandatoryRequirementsStatus);
        $requirementsChecker->checkNonMandatoryRequirements()->shouldBeCalledOnce()->willReturn($nonMandatoryRequirementsStatus);
        $command->getName()->shouldBeCalledOnce()->willReturn('origami:fake-command');
        $this->expectException(MissingRequirementException::class);

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
        [$requirementsChecker] = $this->prophesizeObjectArguments();

        $mandatoryRequirementsStatus = [
            ['name' => 'docker', 'description' => '', 'status' => true],
            ['name' => 'mutagen', 'description' => '', 'status' => true],
        ];
        $nonMandatoryRequirementsStatus = [['name' => 'mkcert', 'description' => '', 'status' => false]];
        $command = $this->prophesize(Command::class);

        $requirementsChecker->checkMandatoryRequirements()->shouldBeCalledOnce()->willReturn($mandatoryRequirementsStatus);
        $requirementsChecker->checkNonMandatoryRequirements()->shouldBeCalledOnce()->willReturn($nonMandatoryRequirementsStatus);
        $command->getName()->shouldBeCalledOnce()->willReturn('origami:fake-command');

        $input = new ArgvInput();
        $output = new BufferedOutput();
        $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);

        $subscriber = new CommandSubscriber($requirementsChecker->reveal());
        $subscriber->onConsoleCommand(new ConsoleCommandEvent($command->reveal(), $input, $output));
    }

    /**
     * {@inheritdoc}
     */
    protected function prophesizeObjectArguments(): array
    {
        return [
            $this->prophesize(RequirementsChecker::class),
        ];
    }
}
