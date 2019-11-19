<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Helper\ApplicationFactory;
use App\Kernel;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class CommandSubscriber implements EventSubscriberInterface
{
    private $kernel;
    private $applicationFactory;
    private $databaseName;

    /**
     * CommandSubscriber constructor.
     *
     * @param Kernel $kernel
     */
    public function __construct(KernelInterface $kernel, ApplicationFactory $applicationFactory, string $databaseName)
    {
        $this->kernel = $kernel;
        $this->applicationFactory = $applicationFactory;
        $this->databaseName = $databaseName;
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

            if (\is_string($commandName) && strpos($commandName, 'origami') !== false
                && !is_file($this->kernel->getCustomDir().\DIRECTORY_SEPARATOR.$this->databaseName)
            ) {
                $application = $this->applicationFactory->create($this->kernel);
                $application->setAutoExit(false);

                $input = new ArrayInput(['command' => 'doctrine:schema:create', '--force']);
                $output = new NullOutput();

                $application->run($input, $output);
            }
        }
    }
}
