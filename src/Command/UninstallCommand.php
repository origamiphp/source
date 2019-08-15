<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Environment;
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
        $this->setName('origami:uninstall');
        $this->setAliases(['uninstall']);

        $this->addArgument(
            'environment',
            InputArgument::REQUIRED,
            'Name of the environment to uninstall'
        );

        $this->setDescription('Uninstalls a specific environment');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);

        if ($this->io->confirm('Are you sure you want to uninstall this environment?', false)) {
            /** @var string $environment */
            $environment = $input->getArgument('environment');

            $activeEnvironment = $this->systemManager->getActiveEnvironment();
            if (!$activeEnvironment instanceof Environment || $activeEnvironment->getName() !== $environment) {
                try {
                    $this->systemManager->uninstall($environment);
                    $this->io->success('Environment successfully uninstalled.');
                } catch (OrigamiExceptionInterface $e) {
                    $this->io->error($e->getMessage());
                    $exitCode = CommandExitCode::EXCEPTION;
                }
            } else {
                $this->io->error('Unable to uninstall a running environment.');
                $exitCode = CommandExitCode::INVALID;
            }
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
