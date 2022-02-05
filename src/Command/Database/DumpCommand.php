<?php

declare(strict_types=1);

namespace App\Command\Database;

use App\Command\AbstractBaseCommand;
use App\Exception\DatabaseException;
use App\Exception\FilesystemException;
use App\Exception\InvalidConfigurationException;
use App\Exception\OrigamiExceptionInterface;
use App\Service\ApplicationContext;
use App\Service\Middleware\Binary\Docker;
use App\Service\Middleware\Database;
use App\Service\Wrapper\OrigamiStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'origami:database:dump',
    description: 'Generates a database dump of the running environment'
)]
class DumpCommand extends AbstractBaseCommand
{
    public function __construct(
        private ApplicationContext $applicationContext,
        private Database $database,
        private Docker $docker,
        string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->addArgument(
            'path',
            InputArgument::OPTIONAL,
            'The relative path to the dump file in the project directory',
            Database::DEFAULT_BACKUP_FILENAME
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

            $path = ltrim($input->getArgument('path'), '/');
            $this->dump($environment->getLocation().'/'.$path);

            $io->success('Database dump successfully executed.');
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Triggers the database dump process according to the database type.
     *
     * @throws DatabaseException
     * @throws InvalidConfigurationException
     * @throws FilesystemException
     */
    private function dump(string $path): void
    {
        switch ($this->database->getDatabaseType()) {
            case 'mariadb':
            case 'mysql':
                $username = $this->database->getDatabaseUsername();
                $password = $this->database->getDatabasePassword();

                if (!$this->docker->dumpMysqlDatabase($username, $password, $path)) {
                    throw new DatabaseException('Unable to complete the MySQL dump process.');
                }
                break;

            case 'postgres':
                $username = $this->database->getDatabaseUsername();
                $password = $this->database->getDatabasePassword();

                if (!$this->docker->dumpPostgresDatabase($username, $password, $path)) {
                    throw new DatabaseException('Unable to complete the Postgres dump process.');
                }
                break;

            default:
                throw new DatabaseException('The database type in use is not yet supported.');
        }
    }
}
