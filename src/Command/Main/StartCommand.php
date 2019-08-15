<?php

declare(strict_types=1);

namespace App\Command\Main;

use App\Command\AbstractBaseCommand;
use App\Event\EnvironmentStartedEvent;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends AbstractBaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('origami:start');
        $this->setAliases(['start']);

        $this->addArgument(
            'environment',
            InputArgument::OPTIONAL,
            'Name of the environment to start'
        );

        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Forces the startup of the environment'
        );

        $this->setDescription('Starts an environment previously installed in the current directory');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->checkPrequisites($input);

            if (!$this->environment->isActive() || $input->getOption('force')) {
                if ($this->dockerCompose->startDockerServices()) {
                    $this->io->success('Docker services successfully started.');

                    $event = new EnvironmentStartedEvent($this->environment, $this->io);
                    $this->eventDispatcher->dispatch($event);
                } else {
                    $this->io->error('An error occurred while starting the Docker services.');
                }
            } else {
                $this->io->error('Unable to start an environment when there is already a running environment.');
                $exitCode = CommandExitCode::INVALID;
            }
        } catch (OrigamiExceptionInterface $e) {
            $this->io->error($e->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
