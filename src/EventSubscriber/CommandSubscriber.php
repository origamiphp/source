<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Kernel;
use Exception;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class CommandSubscriber implements EventSubscriberInterface
{
    /** @var Kernel */
    private $kernel;

    /** @var string */
    private $databaseName;

    /**
     * CommandSubscriber constructor.
     *
     * @param Kernel $kernel
     * @param string $databaseName
     */
    public function __construct(KernelInterface $kernel, string $databaseName)
    {
        $this->kernel = $kernel;
        $this->databaseName = $databaseName;
    }

    /**
     * {@inheritdoc}
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
     * @param ConsoleCommandEvent $event
     *
     * @throws Exception
     */
    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();

        if ($command instanceof Command) {
            $commandName = $command->getName();

            if (\is_string($commandName) && strpos($commandName, 'origami') !== false) {
                $projectDirectory = $this->kernel->getCustomDir();
                $this->createProjectDirectory($projectDirectory);

                if (!is_file("{$projectDirectory}/{$this->databaseName}")) {
                    $this->initializeProjectDatabase();
                }
            }
        }
    }

    /**
     * Creates the directory where the database, the cache and the logs will be stored.
     *
     * @param string $directory
     */
    private function createProjectDirectory(string $directory): void
    {
        if (!is_dir($directory)
            && !mkdir($concurrentDirectory = $directory, 0777, true) && !is_dir($concurrentDirectory)
        ) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
    }

    /**
     * Initializes the project database by creating the schema with Doctrine.
     *
     * @throws Exception
     */
    private function initializeProjectDatabase(): void
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(['command' => 'doctrine:schema:create', '--force']);
        $output = new NullOutput();

        $application->run($input, $output);
    }
}
