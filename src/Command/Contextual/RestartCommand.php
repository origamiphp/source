<?php

declare(strict_types=1);

namespace App\Command\Contextual;

use App\Command\AbstractBaseCommand;
use App\Event\EnvironmentRestartedEvent;
use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use App\Helper\CurrentContext;
use App\Middleware\Binary\DockerCompose;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RestartCommand extends AbstractBaseCommand
{
    /** @var CurrentContext */
    private $currentContext;

    /** @var DockerCompose */
    private $dockerCompose;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(
        CurrentContext $currentContext,
        DockerCompose $dockerCompose,
        EventDispatcherInterface $eventDispatcher,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->currentContext = $currentContext;
        $this->dockerCompose = $dockerCompose;
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
        $io = new SymfonyStyle($input, $output);

        try {
            $environment = $this->currentContext->getEnvironment($input);

            if ($output->isVerbose()) {
                $this->printEnvironmentDetails($environment, $io);
            }

            if (!$this->dockerCompose->restartServices()) {
                throw new InvalidEnvironmentException('An error occurred while restarting the Docker services.');
            }

            $io->success('Docker services successfully restarted.');

            $event = new EnvironmentRestartedEvent($environment, $io);
            $this->eventDispatcher->dispatch($event);
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
