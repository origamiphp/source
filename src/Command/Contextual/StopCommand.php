<?php

declare(strict_types=1);

namespace App\Command\Contextual;

use App\Command\AbstractBaseCommand;
use App\Event\EnvironmentStoppedEvent;
use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StopCommand extends AbstractBaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('origami:stop');
        $this->setAliases(['stop']);

        $this->setDescription('Stops an environment previously started');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $environment = $this->getEnvironment($input);

            if ($output->isVerbose()) {
                $this->printEnvironmentDetails();
            }

            if (!$this->dockerCompose->stopServices()) {
                throw new InvalidEnvironmentException('An error occurred while stopping the Docker services.');
            }

            $this->io->success('Docker services successfully stopped.');

            $event = new EnvironmentStoppedEvent($environment, $this->io);
            $this->eventDispatcher->dispatch($event);
        } catch (OrigamiExceptionInterface $e) {
            $this->io->error($e->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
