<?php

declare(strict_types=1);

namespace App\Command\Main;

use App\Command\AbstractBaseCommand;
use App\Event\EnvironmentStartedEvent;
use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use App\Helper\CurrentContext;
use App\Helper\ProcessProxy;
use App\Middleware\Binary\DockerCompose;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class StartCommand extends AbstractBaseCommand
{
    /** @var CurrentContext */
    private $currentContext;

    /** @var ProcessProxy */
    private $processProxy;

    /** @var DockerCompose */
    private $dockerCompose;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(
        CurrentContext $currentContext,
        ProcessProxy $processProxy,
        DockerCompose $dockerCompose,
        EventDispatcherInterface $eventDispatcher,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->currentContext = $currentContext;
        $this->processProxy = $processProxy;
        $this->dockerCompose = $dockerCompose;
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
        $io = new SymfonyStyle($input, $output);

        try {
            $environment = $this->currentContext->getEnvironment($input);

            if (!$environment->isActive() || $environment->getLocation() === $this->processProxy->getWorkingDirectory()) {
                if (!$this->dockerCompose->startServices()) {
                    throw new InvalidEnvironmentException('An error occurred while starting the Docker services.');
                }

                $io->success('Docker services successfully started.');

                $event = new EnvironmentStartedEvent($environment, $io);
                $this->eventDispatcher->dispatch($event);
            } else {
                $io->error('Unable to start an environment when there is already a running one.');
                $exitCode = CommandExitCode::INVALID;
            }
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
