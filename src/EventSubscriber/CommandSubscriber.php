<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\MissingRequirementException;
use App\Helper\OrigamiStyle;
use App\Service\ReleaseChecker;
use App\Service\RequirementsChecker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CommandSubscriber implements EventSubscriberInterface
{
    private RequirementsChecker $requirementsChecker;
    private ReleaseChecker $releaseChecker;

    public function __construct(RequirementsChecker $requirementsChecker, ReleaseChecker $releaseChecker)
    {
        $this->requirementsChecker = $requirementsChecker;
        $this->releaseChecker = $releaseChecker;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     *
     * @uses \App\EventSubscriber\CommandSubscriber::onConsoleCommand
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
        // Allow to only trigger the check on environment variables with custom commands.
        $command = $event->getCommand();
        if ($command instanceof Command && strpos($command->getName() ?? '', 'origami:') === false) {
            return;
        }

        $io = new OrigamiStyle($event->getInput(), $event->getOutput());

        $this->requirementsChecker->validate($io, $event->getOutput()->isVerbose());
        $this->releaseChecker->validate($io);
    }
}
