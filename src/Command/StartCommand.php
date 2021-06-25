<?php

declare(strict_types=1);

namespace App\Command;

use App\Event\EnvironmentStartedEvent;
use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Service\CurrentContext;
use App\Service\Middleware\Binary\Docker;
use App\Service\Middleware\Wrapper\OrigamiStyle;
use App\Service\Middleware\Wrapper\ProcessProxy;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class StartCommand extends AbstractBaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected static $defaultName = 'origami:start';

    private CurrentContext $currentContext;
    private ProcessProxy $processProxy;
    private Docker $docker;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        CurrentContext $currentContext,
        ProcessProxy $processProxy,
        Docker $docker,
        EventDispatcherInterface $eventDispatcher,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->currentContext = $currentContext;
        $this->processProxy = $processProxy;
        $this->docker = $docker;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Starts an environment previously installed in the current directory');

        $this->addArgument(
            'environment',
            InputArgument::OPTIONAL,
            'Name of the environment to start'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new OrigamiStyle($input, $output);

        try {
            $this->currentContext->loadEnvironment($input);
            $environment = $this->currentContext->getActiveEnvironment();

            if (!$environment->isActive() || $environment->getLocation() === $this->processProxy->getWorkingDirectory()) {
                if (!$this->docker->startServices()) {
                    throw new InvalidEnvironmentException('An error occurred while starting the Docker services.');
                }

                $domains = $environment->getDomains();

                $event = new EnvironmentStartedEvent($environment, $io);
                $this->eventDispatcher->dispatch($event);

                $io->success('Docker services successfully started.');

                $message = sprintf(
                    'Please visit %s to access your environment.',
                    ($domains !== null ? "https://{$domains}" : 'https://127.0.0.1')
                );
                $io->info($message);
            } else {
                throw new InvalidEnvironmentException('Unable to start an environment when there is already a running one.');
            }
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
