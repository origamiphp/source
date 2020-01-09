<?php

declare(strict_types=1);

namespace App\Command\Main;

use App\Command\AbstractBaseCommand;
use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PrepareCommand extends AbstractBaseCommand
{
    protected static $defaultName = 'origami:prepare';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setAliases(['prepare']);
        $this->setDescription('Prepares an environment previously installed (i.e. pulls/builds Docker images)');

        $this->addArgument(
            'environment',
            InputArgument::OPTIONAL,
            'Name of the environment to prepare'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->getEnvironment($input);

            if ($output->isVerbose()) {
                $this->printEnvironmentDetails();
            }

            if (!$this->dockerCompose->prepareServices()) {
                throw new InvalidEnvironmentException('An error occurred while preparing the Docker services.');
            }

            $this->io->success('Docker services successfully prepared.');
        } catch (OrigamiExceptionInterface $exception) {
            $this->io->error($exception->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
