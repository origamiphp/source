<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CurrentContext;
use App\Middleware\Binary\DockerCompose;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RestartCommand extends AbstractBaseCommand
{
    /** {@inheritdoc} */
    protected static $defaultName = 'origami:restart';

    private CurrentContext $currentContext;
    private DockerCompose $dockerCompose;

    public function __construct(
        CurrentContext $currentContext,
        DockerCompose $dockerCompose,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->currentContext = $currentContext;
        $this->dockerCompose = $dockerCompose;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Restarts an environment previously started');
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

            if (!$this->dockerCompose->restartServices()) {
                throw new InvalidEnvironmentException('An error occurred while restarting the Docker services.');
            }

            $io->success('Docker services successfully restarted.');
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());
            $exitCode = Command::FAILURE;
        }

        return $exitCode ?? Command::SUCCESS;
    }
}
