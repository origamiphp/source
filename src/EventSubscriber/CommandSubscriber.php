<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Environment\EnvironmentMaker\RequirementsChecker;
use App\Exception\MissingRequirementException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CommandSubscriber implements EventSubscriberInterface
{
    /** @var RequirementsChecker */
    private $requirementsChecker;

    public function __construct(RequirementsChecker $binaryChecker)
    {
        $this->requirementsChecker = $binaryChecker;
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
     * Checks whether all required environment variables are set before executing any commands.
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

        $mandatoryRequirements = $this->requirementsChecker->checkMandatoryRequirements();
        $nonMandatoryRequirements = $this->requirementsChecker->checkNonMandatoryRequirements();

        if ($event->getOutput()->isVeryVerbose()) {
            $io = new SymfonyStyle($event->getInput(), $event->getOutput());
            $io->title('Origami Requirements Checker');

            $io->listing(
                array_map(static function ($item) {
                    $icon = $item['status'] ? '✅' : '❌';

                    return "{$icon} {$item['name']} - {$item['description']}";
                }, array_merge($mandatoryRequirements, $nonMandatoryRequirements))
            );
        }

        if (\count($mandatoryRequirements) !== \count(array_filter(array_column($mandatoryRequirements, 'status')))) {
            throw new MissingRequirementException('At least one mandatory binary is missing from your system.');
        }
    }
}
