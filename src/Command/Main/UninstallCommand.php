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
use Symfony\Component\Console\Style\SymfonyStyle;

class UninstallCommand extends AbstractBaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Uninstalls a specific environment');

        $this->addArgument(
            'environment',
            InputArgument::OPTIONAL,
            'Name of the environment to uninstall'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $environment = $this->getEnvironment($input);

            $question = sprintf(
                'Are you sure you want to uninstall the "%s" environment?',
                $environment->getName()
            );

            if ($io->confirm($question, false)) {
                if ($environment->isActive()) {
                    throw new InvalidEnvironmentException('Unable to uninstall a running environment.');
                }

                if (!$this->dockerCompose->removeServices()) {
                    throw new InvalidEnvironmentException('An error occurred while removing the Docker services.');
                }

                $event = new EnvironmentUninstallEvent($environment, $io);
                $this->eventDispatcher->dispatch($event);

                $this->systemManager->uninstall($environment);
                $this->database->remove($environment);
                $this->database->save();

                $io->success('Environment successfully uninstalled.');
            }
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
