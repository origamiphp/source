<?php

declare(strict_types=1);

namespace App\Command\Additional;

use App\Command\AbstractBaseCommand;
use App\Helper\CommandExitCode;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RegistryCommand extends AbstractBaseCommand
{
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
            $table->setHeaders(['Name', 'Location', 'Type', 'Domains']);

            foreach ($environments as $environment) {
                $table->addRow([
                    $environment->getName(),
                    $environment->getLocation(),
                    $environment->getType(),
                    $environment->getDomains() ?? '',
                ]);
            }

            $table->render();
        } else {
            $io->note('There is no registered environment at the moment.');
        }

        return CommandExitCode::SUCCESS;
    }
}
