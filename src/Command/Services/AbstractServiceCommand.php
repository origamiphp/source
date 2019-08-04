<?php

declare(strict_types=1);

namespace App\Command\Services;

use App\Command\AbstractBaseCommand;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractServiceCommand extends AbstractBaseCommand implements ServiceCommandInterface
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $serviceName = $this->getServiceName();

        $this->setName("origami:services:$serviceName");
        $this->setAliases([$serviceName]);

        $this->setDescription("Opens a terminal on the \"$serviceName\" service");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);

        try {
            $this->environment = $this->getActiveEnvironment();

            if ($output->isVerbose()) {
                $this->printEnvironmentDetails();
            }

            $this->dockerCompose->openTerminal(
                $this->getServiceName(),
                $this->getUsername(),
                $this->getRequiredVariables($this->environment)
            );
        } catch (OrigamiExceptionInterface $e) {
            $this->io->error($e->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
