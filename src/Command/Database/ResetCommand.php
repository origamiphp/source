<?php

declare(strict_types=1);

namespace App\Command\Database;

use App\Command\AbstractBaseCommand;
use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Service\ApplicationContext;
use App\Service\Middleware\Binary\Docker;
use App\Service\Wrapper\OrigamiStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ResetCommand extends AbstractBaseCommand
{
    /** {@inheritdoc} */
    protected static $defaultName = 'origami:database:reset';
    /** {@inheritdoc} */
    protected static $defaultDescription = 'Recreates the database volume of the running environment';

    public function __construct(
        private ApplicationContext $applicationContext,
        private Docker $docker,
        string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new OrigamiStyle($input, $output);

        try {
            if ($io->confirm('Are you really sure you want to reset the database?', false)) {
                $this->applicationContext->loadEnvironment($input);
                $environment = $this->applicationContext->getActiveEnvironment();

                if ($output->isVerbose()) {
                    $this->printEnvironmentDetails($environment, $io);
                }

                // First, we need to stop and remove the existing service.
                if (!$this->docker->removeDatabaseService() || !$this->docker->removeDatabaseVolume()) {
                    throw new InvalidEnvironmentException('An error occurred while removing the database service.');
                }

                // Finally, we can recreate the service with a new volume.
                if (!$this->docker->startServices()) {
                    throw new InvalidEnvironmentException('An error occurred while starting the Docker services.');
                }

                $io->success('Database reset successfully executed.');
            }
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
