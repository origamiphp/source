<?php

declare(strict_types=1);

namespace App\Command\Database;

use App\Command\AbstractBaseCommand;
use App\Exception\OrigamiExceptionInterface;
use App\Service\ApplicationContext;
use App\Service\Middleware\Database;
use App\Service\Wrapper\OrigamiStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DetailsCommand extends AbstractBaseCommand
{
    /** {@inheritdoc} */
    protected static $defaultName = 'origami:database:details';
    /** {@inheritdoc} */
    protected static $defaultDescription = 'Shows the database details of the running environment';

    public function __construct(
        private ApplicationContext $applicationContext,
        private Database $database,
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
            $this->applicationContext->loadEnvironment($input);

            $io->listing([
                "Type: {$this->database->getDatabaseType()}",
                "Version: {$this->database->getDatabaseVersion()}",
                "Username: {$this->database->getDatabaseUsername()}",
                "Password: {$this->database->getDatabasePassword()}",
            ]);
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
