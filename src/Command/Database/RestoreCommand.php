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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RestoreCommand extends AbstractBaseCommand
{
    /** {@inheritdoc} */
    protected static $defaultName = 'origami:database:restore';
    /** {@inheritdoc} */
    protected static $defaultDescription = 'Restores a database dump of the running environment';

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
            $this->restore($environment->getLocation().'/'.$path);

            $io->success('Database restore successfully executes.');
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Triggers the database restore process according to the database type.
     *
     * @throws DatabaseException
     * @throws InvalidConfigurationException
     * @throws FilesystemException
     */
    private function restore(string $path): void
    {
        if (!is_file($path)) {
            throw new DatabaseException('Unable to find the backup file to restore.');
        }

        switch ($this->database->getDatabaseType()) {
            case 'mariadb':
            case 'mysql':
                $username = $this->database->getDatabaseUsername();
                $password = $this->database->getDatabasePassword();

                if (!$this->docker->restoreMysqlDatabase($username, $password, $path)) {
                    throw new DatabaseException('Unable to complete the MySQL restore process.');
                }
                break;

            case 'postgres':
                $username = $this->database->getDatabaseUsername();
                $password = $this->database->getDatabasePassword();

                if (!$this->docker->restorePostgresDatabase($username, $password, $path)) {
                    throw new DatabaseException('Unable to complete the Postgres restore process.');
                }
                break;

            default:
                throw new DatabaseException('The database type in use is not yet supported.');
        }
    }
}
