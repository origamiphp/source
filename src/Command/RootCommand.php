<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RootCommand extends AbstractBaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('origami:root');
        $this->setAliases(['root']);

        $this->setDescription('Display instructions to set up the environment variables');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);

        try {
            $this->environment = $this->getActiveEnvironment();
            $this->writeInstructions();
        } catch (OrigamiExceptionInterface $e) {
            $this->io->error($e->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }

    /**
     * Writes instructions to the console output.
     *
     * @throws InvalidEnvironmentException
     */
    private function writeInstructions(): void
    {
        $result = '';
        foreach ($this->getRequiredVariables($this->environment) as $key => $value) {
            $result .= "export $key=\"$value\"\n";
        }

        $this->io->writeln($result);
        $this->io->writeln('# Run this command to configure your shell:');
        $this->io->writeln('# eval $(origami root)');
    }
}
