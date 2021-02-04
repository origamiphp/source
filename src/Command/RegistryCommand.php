<?php

declare(strict_types=1);

namespace App\Command;

use App\Middleware\Database;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RegistryCommand extends AbstractBaseCommand
{
    /** {@inheritdoc} */
    protected static $defaultName = 'origami:registry';

    private Database $database;

    public function __construct(Database $database, ?string $name = null)
    {
        parent::__construct($name);

        $this->database = $database;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Shows the list of registered environments');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $environments = $this->database->getAllEnvironments();
        if ($environments->count() > 0) {
            $table = new Table($output);
            $table->setHeaders(['Name', 'Location', 'Type', 'Domains', 'Status']);

            foreach ($environments as $environment) {
                $table->addRow([
                    $environment->getName(),
                    $environment->getLocation(),
                    $environment->getType(),
                    $environment->getDomains() ?? '',
                    $environment->isActive() ? '<fg=green>Started</>' : '<fg=red>Stopped</>',
                ]);
            }

            $table->render();
        } else {
            $io->note('There is no registered environment at the moment.');
        }

        return Command::SUCCESS;
    }
}
