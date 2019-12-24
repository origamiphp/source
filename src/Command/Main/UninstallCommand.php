<?php

declare(strict_types=1);

namespace App\Command\Main;

use App\Command\AbstractBaseCommand;
use App\Event\EnvironmentUninstallEvent;
use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UninstallCommand extends AbstractBaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('origami:uninstall');
        $this->setAliases(['uninstall']);

        $this->addArgument(
            'environment',
            InputArgument::OPTIONAL,
            'Name of the environment to uninstall'
        );

        $this->setDescription('Uninstalls a specific environment');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $environment = $this->getEnvironment($input);

            $question = sprintf(
                'Are you sure you want to uninstall the "%s" environment?',
                $this->environment->getName()
            );

            if ($this->io->confirm($question, false)) {
                if ($this->environment->isActive()) {
                    throw new InvalidEnvironmentException('Unable to uninstall a running environment.');
                }

                if (!$this->dockerCompose->removeServices()) {
                    throw new InvalidEnvironmentException('An error occurred while removing the Docker services.');
                }

                $event = new EnvironmentUninstallEvent($environment, $this->io);
                $this->eventDispatcher->dispatch($event);

                $this->systemManager->uninstall($environment);

                $this->io->success('Environment successfully uninstalled.');
            }
        } catch (OrigamiExceptionInterface $e) {
            $this->io->error($e->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
