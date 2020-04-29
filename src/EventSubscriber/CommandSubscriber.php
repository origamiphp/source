<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\InvalidConfigurationException;
use App\Middleware\SystemManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CommandSubscriber implements EventSubscriberInterface
{
    /** @var array */
    private $requirements;

    /** @var SystemManager */
    private $systemManager;

    public function __construct(array $requirements, SystemManager $systemManager)
    {
        $this->requirements = $requirements;
        $this->systemManager = $systemManager;
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
     * @throws InvalidConfigurationException
     */
    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        // Allow to only trigger the check on environment variables with custom commands.
        $command = $event->getCommand();
        if ($command instanceof Command && strpos($command->getName() ?? '', 'origami:') === false) {
            return;
        }

        $result = [];
        foreach ($this->requirements as $binary => $description) {
            $result[] = [
                'binary' => $binary,
                'description' => $description,
                'status' => $this->systemManager->isBinaryInstalled($binary),
            ];
        }

        if (\count($this->requirements) !== \count(array_filter(array_column($result, 'status')))) {
            $io = new SymfonyStyle($event->getInput(), $event->getOutput());
            $io->title('Origami Requirements Checker');

            $io->listing(
                array_map(static function ($item) {
                    $icon = $item['status'] ? '✅' : '❌';

                    return "{$icon} {$item['binary']} - {$item['description']}";
                }, $result)
            );

            throw new InvalidConfigurationException('At least one binary is missing from your system.');
        }
    }
}
