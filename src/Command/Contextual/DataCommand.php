<?php

declare(strict_types=1);

namespace App\Command\Contextual;

use App\Command\AbstractBaseCommand;
use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DataCommand extends AbstractBaseCommand
{
    protected static $defaultName = 'origami:data';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setAliases(['data']);
        $this->setDescription('Shows the usage statistics of running environment');
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

            if (!$this->dockerCompose->showResourcesUsage()) {
                throw new InvalidEnvironmentException('An error occurred while checking the resources usage.');
            }
        } catch (OrigamiExceptionInterface $exception) {
            $this->io->error($exception->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
