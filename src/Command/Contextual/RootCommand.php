<?php

declare(strict_types=1);

namespace App\Command\Contextual;

use App\Command\AbstractBaseCommand;
use App\Environment\EnvironmentEntity;
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
        $this->setDescription('Display instructions to set up the environment variables');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $environment = $this->getEnvironment($input);

            if ($output->isVerbose()) {
                $this->printEnvironmentDetails($environment, $io);
            }

            $this->writeInstructions($environment, $io);
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }

    /**
     * Writes instructions to the console output.
     */
    private function writeInstructions(EnvironmentEntity $environment, SymfonyStyle $io): void
    {
        $result = '';

        foreach ($this->dockerCompose->getRequiredVariables($environment) as $key => $value) {
            $result .= sprintf('export %s="%s"', $key, $value);
        }

        $io->writeln($result);
        $io->writeln('# Run this command to configure your shell:');
        $io->writeln('# eval $(origami root)');
    }
}
