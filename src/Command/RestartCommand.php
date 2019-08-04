<?php

declare(strict_types=1);

namespace App\Command;

use App\Event\EnvironmentRestartedEvent;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RestartCommand extends AbstractBaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('origami:restart');
        $this->setAliases(['restart']);

        $this->setDescription('Restarts an environment previously started');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);

        try {
            $this->environment = $this->getActiveEnvironment();
            $environmentVariables = $this->getRequiredVariables($this->environment);

            if ($this->dockerCompose->restartDockerServices($environmentVariables)) {
                $this->io->success('Docker services successfully restarted.');

                $event = new EnvironmentRestartedEvent($this->environment, $environmentVariables, $this->io);
                $this->eventDispatcher->dispatch($event);
            } else {
                $this->io->error('An error occurred while restarting the Docker services.');
            }
        } catch (OrigamiExceptionInterface $e) {
            $this->io->error($e->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
