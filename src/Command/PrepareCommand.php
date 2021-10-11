<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Service\ApplicationContext;
use App\Service\Middleware\Binary\Docker;
use App\Service\Wrapper\OrigamiStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PrepareCommand extends AbstractBaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected static $defaultName = 'origami:prepare';

    private ApplicationContext $applicationContext;
    private Docker $docker;

    public function __construct(
        ApplicationContext $applicationContext,
        Docker $docker,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->applicationContext = $applicationContext;
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
        $io = new OrigamiStyle($input, $output);

        try {
            $this->applicationContext->loadEnvironment($input);
            $environment = $this->applicationContext->getActiveEnvironment();

            if ($output->isVerbose()) {
                $this->printEnvironmentDetails($environment, $io);
            }

            if (!$this->docker->pullServices() || !$this->docker->buildServices()) {
                throw new InvalidEnvironmentException('An error occurred while preparing the Docker services.');
            }

            $io->success('Docker services successfully prepared.');
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
