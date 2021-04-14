<?php

declare(strict_types=1);

namespace App\Command;

use App\Environment\EnvironmentEntity;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CurrentContext;
use App\Middleware\Binary\Docker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RootCommand extends AbstractBaseCommand
{
    /** {@inheritdoc} */
    protected static $defaultName = 'origami:root';

    private CurrentContext $currentContext;
    private Docker $docker;

    public function __construct(CurrentContext $currentContext, Docker $docker, ?string $name = null)
    {
        parent::__construct($name);

        $this->currentContext = $currentContext;
        $this->docker = $docker;
    }

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
            $environment = $this->currentContext->getEnvironment($input);
            $this->currentContext->setActiveEnvironment($environment);

            if ($output->isVerbose()) {
                $this->printEnvironmentDetails($environment, $io);
            }

            $this->writeInstructions($environment, $io);
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());
            $exitCode = Command::FAILURE;
        }

        return $exitCode ?? Command::SUCCESS;
    }

    /**
     * Writes instructions to the console output.
     */
    private function writeInstructions(EnvironmentEntity $environment, SymfonyStyle $io): void
    {
        $result = '';

        foreach ($this->docker->getRequiredVariables($environment) as $key => $value) {
            $result .= "export {$key}=\"{$value}\"\n";
        }

        $io->writeln($result);
        $io->writeln('# Run this command to configure your shell:');
        $io->writeln('# eval $(origami root)');
    }
}
