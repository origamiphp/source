<?php

declare(strict_types=1);

namespace App\Command\Additional;

use App\Command\AbstractBaseCommand;
use App\Entity\Environment;
use App\Helper\CommandExitCode;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RegistryCommand extends AbstractBaseCommand
{
    protected static $defaultName = 'origami:registry';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setAliases(['registry']);
        $this->setDescription('Shows the list of registered environments');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $environments = $this->systemManager->getAllEnvironments();
        if (\count($environments) > 0) {
            $table = new Table($output);
            $table->setHeaders(['Name', 'Location', 'Type', 'Domains']);

            /** @var Environment $environment */
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
            $this->io->note('There is no registered environment at the moment.');
        }

        return CommandExitCode::SUCCESS;
    }
}
