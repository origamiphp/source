<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Environment;
use App\Event\EnvironmentStartedEvent;
use App\Exception\InvalidConfigurationException;
use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);

        try {
            $this->environment = $this->getRequestedEnvironment($input);
            $activeEnvironment = $this->environmentManager->getActiveEnvironment();

            if (!$activeEnvironment || $input->hasOption('force')) {
                $environmentVariables = $this->getRequiredVariables($this->environment);

                if ($this->dockerCompose->startDockerServices($environmentVariables)) {
                    $this->io->success('Docker services successfully started.');

                    $event = new EnvironmentStartedEvent($this->environment, $environmentVariables, $this->io);
                    $this->eventDispatcher->dispatch($event);
                } else {
                    $this->io->error('An error occurred while starting the Docker services.');
                }
            } else {
                $this->io->error(
                    $this->environment === $activeEnvironment
                        ? 'The environment is already running.'
                        : 'Unable to start an environment when another is still running.'
                );
                $exitCode = CommandExitCode::INVALID;
            }
        } catch (OrigamiExceptionInterface $e) {
            $this->io->error($e->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }

    /**
     * Retrieves the requested environment, either by user input or current directory.
     *
     * @param InputInterface $input
     *
     * @throws InvalidConfigurationException
     * @throws InvalidEnvironmentException
     *
     * @return Environment
     */
    private function getRequestedEnvironment(InputInterface $input): Environment
    {
        if ($input->hasArgument('environment')) {
            /** @var string $argument */
            $argument = $input->getArgument('environment');

            $environment = $this->environmentManager->getEnvironmentByName($argument);
            if (!$environment instanceof Environment) {
                throw new InvalidEnvironmentException('There is no environment associated to the given name.');
            }
        } else {
            if (!$location = getcwd()) {
                throw new InvalidConfigurationException(
                    'Unable to retrieve the current working directory.'
                );
            }

            $environment = $this->environmentManager->getEnvironmentByLocation($location);
            if (!$environment instanceof Environment) {
                throw new InvalidConfigurationException(
                    'An environment must be installed, please consider using the install command instead.'
                );
            }
        }

        return $environment;
    }
}
