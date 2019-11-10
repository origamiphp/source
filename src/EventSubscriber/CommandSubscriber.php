<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Helper\Setup;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CommandSubscriber implements EventSubscriberInterface
{
    /** @var Setup */
    private $setup;

    /**
     * CommandSubscriber constructor.
     */
    public function __construct(Setup $setup)
    {
        $this->setup = $setup;
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
            ConsoleCommandEvent::class => 'onConsoleCommand',
        ];
    }

    /**
     * Listener which prepares the application database.
     *
     * @throws Exception
     */
    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();

        if ($command instanceof Command) {
            $commandName = $command->getName();

            if (\is_string($commandName) && strpos($commandName, 'origami') !== false) {
                $this->setup->createProjectDirectory();
                $this->setup->initializeProjectDatabase();
            }
        }
    }
}
