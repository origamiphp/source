<?php

declare(strict_types=1);

namespace App\Command\Contextual;

use App\Command\AbstractBaseCommand;
use App\Environment\EnvironmentEntity;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use App\Helper\CurrentContext;
use App\Middleware\Binary\DockerCompose;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RootCommand extends AbstractBaseCommand
{
    /** @var CurrentContext */
    private $currentContext;

    /** @var DockerCompose */
    private $dockerCompose;

    public function __construct(CurrentContext $currentContext, DockerCompose $dockerCompose, ?string $name = null)
    {
        parent::__construct($name);

        $this->currentContext = $currentContext;
        $this->dockerCompose = $dockerCompose;
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
            $result .= "export {$key}=\"{$value}\"\n";
        }

        $io->writeln($result);
        $io->writeln('# Run this command to configure your shell:');
        $io->writeln('# eval $(origami root)');
    }
}
