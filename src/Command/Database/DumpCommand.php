<?php

declare(strict_types=1);

namespace App\Command\Database;

use App\Command\AbstractBaseCommand;
use App\Exception\OrigamiExceptionInterface;
use App\Service\CurrentContext;
use App\Service\Middleware\Database;
use App\Service\Wrapper\OrigamiStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DumpCommand extends AbstractBaseCommand
{
    protected static $defaultName = 'origami:database:dump';
    protected static $defaultDescription = 'Generates a dump of the environment database according to the engine in use';

    private CurrentContext $currentContext;
    private Database $database;

    public function __construct(CurrentContext $currentContext, Database $database, string $name = null)
    {
        parent::__construct($name);

        $this->currentContext = $currentContext;
        $this->database = $database;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new OrigamiStyle($input, $output);

        try {
            $this->currentContext->loadEnvironment($input);
            $environment = $this->currentContext->getActiveEnvironment();

            if ($output->isVerbose()) {
                $this->printEnvironmentDetails($environment, $io);
            }

            $this->database->dump($environment->getLocation().'/'.Database::DEFAULT_BACKUP_FILENAME);
            $io->success('Database dump successfully executed.');
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
