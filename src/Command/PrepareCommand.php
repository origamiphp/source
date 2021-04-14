<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CurrentContext;
use App\Middleware\Binary\Docker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PrepareCommand extends AbstractBaseCommand
{
    /** {@inheritdoc} */
    protected static $defaultName = 'origami:prepare';

    private CurrentContext $currentContext;
    private Docker $docker;

    public function __construct(
        CurrentContext $currentContext,
        Docker $docker,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->currentContext = $currentContext;
        $this->docker = $docker;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Prepares an environment previously installed (i.e. pulls/builds Docker images)');

        $this->addArgument(
            'environment',
            InputArgument::OPTIONAL,
            'Name of the environment to prepare'
        );
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

            if (!$this->docker->prepareServices()) {
                throw new InvalidEnvironmentException('An error occurred while preparing the Docker services.');
            }

            $io->success('Docker services successfully prepared.');
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());
            $exitCode = Command::FAILURE;
        }

        return $exitCode ?? Command::SUCCESS;
    }
}
