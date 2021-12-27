<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Command\RootCommand;
use App\Exception\MissingRequirementException;
use App\Service\ReleaseChecker;
use App\Service\RequirementsChecker;
use App\Service\Wrapper\OrigamiStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CommandSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RequirementsChecker $requirementsChecker,
        private ReleaseChecker $releaseChecker
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     *
     * @uses \App\EventSubscriber\CommandSubscriber::onConsoleCommand
     *
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'console.command' => 'onConsoleCommand',
        ];
    }

    /**
     * Triggers several checks before executing any custom commands.
     *
     * @throws MissingRequirementException
     */
    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();

        if (!$command instanceof Command) {
            return;
        }

        $commandName = $command->getName() ?? '';

        // Allow to only trigger the check on environment variables with custom commands.
        if (!str_contains($commandName, 'origami:') || $commandName === RootCommand::getDefaultName()) {
            return;
        }

        $io = new OrigamiStyle($event->getInput(), $event->getOutput());

        $this->requirementsChecker->validate($io, $event->getOutput()->isVerbose());
        $this->releaseChecker->validate($io);
    }
}
