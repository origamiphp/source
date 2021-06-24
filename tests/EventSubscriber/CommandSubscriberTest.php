<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\CommandSubscriber;
use App\Exception\MissingRequirementException;
use App\Service\Middleware\Wrapper\OrigamiStyle;
use App\Service\ReleaseChecker;
use App\Service\RequirementsChecker;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
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
final class CommandSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @throws MissingRequirementException
     */
    public function testItDoesNotTriggerChecksWithSymfonyCommands(): void
    {
        $requirementsChecker = $this->prophesize(RequirementsChecker::class);
        $releaseChecker = $this->prophesize(ReleaseChecker::class);

        $command = $this->prophesize(Command::class);
        $input = $this->prophesize(InputInterface::class);
        $output = $this->prophesize(OutputInterface::class);

        $command
            ->getName()
            ->shouldBeCalledOnce()
            ->willReturn('app:fake-command')
        ;

        $requirementsChecker
            ->validate(Argument::type(OrigamiStyle::class), Argument::type('bool'))
            ->shouldNotBeCalled()
        ;

        $releaseChecker
            ->validate(Argument::type(OrigamiStyle::class))
            ->shouldNotBeCalled()
        ;

        $subscriber = new CommandSubscriber($requirementsChecker->reveal(), $releaseChecker->reveal());
        $subscriber->onConsoleCommand(new ConsoleCommandEvent($command->reveal(), $input->reveal(), $output->reveal()));
    }

    /**
     * @throws MissingRequirementException
     */
    public function testItTriggersChecksWithOrigamiCommands(): void
    {
        $requirementsChecker = $this->prophesize(RequirementsChecker::class);
        $releaseChecker = $this->prophesize(ReleaseChecker::class);

        $command = $this->prophesize(Command::class);
        $command
            ->getName()
            ->shouldBeCalledOnce()
            ->willReturn('origami:fake-command')
        ;

        $requirementsChecker
            ->validate(Argument::type(OrigamiStyle::class), Argument::type('bool'))
            ->shouldBeCalledOnce()
        ;

        $releaseChecker
            ->validate(Argument::type(OrigamiStyle::class))
            ->shouldBeCalledOnce()
        ;

        $subscriber = new CommandSubscriber($requirementsChecker->reveal(), $releaseChecker->reveal());
        $subscriber->onConsoleCommand(new ConsoleCommandEvent($command->reveal(), new ArgvInput(), new BufferedOutput()));
    }
}
