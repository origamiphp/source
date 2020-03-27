<?php

declare(strict_types=1);

namespace App\Command\Additional;

use App\Command\AbstractBaseCommand;
use App\Environment\EnvironmentEntity;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RegisterCommand extends AbstractBaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Register an external environment which was not created by Origami.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            if ($this->io->confirm('Do you want to register the current directory as a custom environment?', false)) {
                $location = $this->processProxy->getWorkingDirectory();

                $environment = $this->systemManager->install($location, EnvironmentEntity::TYPE_CUSTOM, null);
                $this->database->add($environment);
                $this->database->save();

                $this->io->success('Environment successfully registered.');
            }
        } catch (OrigamiExceptionInterface $exception) {
            $this->io->error($exception->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
