<?php

declare(strict_types=1);

namespace App\Command\Contextual\Services;

use App\Command\AbstractBaseCommand;
use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractServiceCommand extends AbstractBaseCommand implements ServiceCommandInterface
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $serviceName = $this->getServiceName();

        $this->setName("origami:services:{$serviceName}");
        $this->setAliases([$serviceName]);

        $this->setDescription("Opens a terminal on the \"{$serviceName}\" service");
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

            if (!$this->dockerCompose->openTerminal($this->getServiceName(), $this->getUsername())) {
                throw new InvalidEnvironmentException('An error occurred while opening a terminal.');
            }
        } catch (OrigamiExceptionInterface $e) {
            $this->io->error($e->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
