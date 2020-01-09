<?php

declare(strict_types=1);

namespace App\Command\Contextual;

use App\Command\AbstractBaseCommand;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RootCommand extends AbstractBaseCommand
{
    protected static $defaultName = 'origami:root';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setAliases(['root']);
        $this->setDescription('Display instructions to set up the environment variables');
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

            $this->writeInstructions();
        } catch (OrigamiExceptionInterface $exception) {
            $this->io->error($exception->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }

    /**
     * Writes instructions to the console output.
     */
    private function writeInstructions(): void
    {
        $result = '';
        foreach ($this->dockerCompose->getRequiredVariables() as $key => $value) {
            $result .= sprintf('export %s="%s"
', $key, $value);
        }

        $this->io->writeln($result);
        $this->io->writeln('# Run this command to configure your shell:');
        $this->io->writeln('# eval $(origami root)');
    }
}
