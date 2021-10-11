<?php

declare(strict_types=1);

namespace App\Command;

use App\Event\EnvironmentRestartedEvent;
use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Service\ApplicationContext;
use App\Service\Middleware\Binary\Docker;
use App\Service\Wrapper\OrigamiStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RestartCommand extends AbstractBaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected static $defaultName = 'origami:restart';

    private ApplicationContext $applicationContext;
    private Docker $docker;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        ApplicationContext $applicationContext,
        Docker $docker,
        EventDispatcherInterface $eventDispatcher,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->applicationContext = $applicationContext;
        $this->docker = $docker;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Restarts an environment previously started');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new OrigamiStyle($input, $output);

        try {
            $this->applicationContext->loadEnvironment($input);
            $environment = $this->applicationContext->getActiveEnvironment();

            if ($output->isVerbose()) {
                $this->printEnvironmentDetails($environment, $io);
            }

            if (!$this->docker->restartServices()) {
                throw new InvalidEnvironmentException('An error occurred while restarting the Docker services.');
            }

            $event = new EnvironmentRestartedEvent($environment, $io);
            $this->eventDispatcher->dispatch($event);

            $io->success('Docker services successfully restarted.');
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
