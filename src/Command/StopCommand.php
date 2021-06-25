<?php

declare(strict_types=1);

namespace App\Command;

use App\Event\EnvironmentStoppedEvent;
use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Service\CurrentContext;
use App\Service\Middleware\Binary\Docker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class StopCommand extends AbstractBaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected static $defaultName = 'origami:stop';

    private CurrentContext $currentContext;
    private Docker $docker;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        CurrentContext $currentContext,
        Docker $docker,
        EventDispatcherInterface $eventDispatcher,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->currentContext = $currentContext;
        $this->docker = $docker;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Stops an environment previously started');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->currentContext->loadEnvironment($input);
            $environment = $this->currentContext->getActiveEnvironment();

            if ($output->isVerbose()) {
                $this->printEnvironmentDetails($environment, $io);
            }

            if (!$this->docker->stopServices()) {
                throw new InvalidEnvironmentException('An error occurred while stopping the Docker services.');
            }

            $event = new EnvironmentStoppedEvent($environment, $io);
            $this->eventDispatcher->dispatch($event);

            $io->success('Docker services successfully stopped.');
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
