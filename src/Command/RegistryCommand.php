<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ApplicationData;
use App\Service\Wrapper\OrigamiStyle;
use App\ValueObject\EnvironmentEntity;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'origami:registry',
    description: 'Shows the list and status of all previously installed environments'
)]
class RegistryCommand extends AbstractBaseCommand
{
    public function __construct(
        private ApplicationData $applicationData,
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

        $environments = $this->applicationData->getAllEnvironments();
        if ($environments->count() > 0) {
            $table = new Table($output);
            $table->setHeaders(['Name', 'Location', 'Type', 'Status']);

            /** @var EnvironmentEntity $environment */
            foreach ($environments as $environment) {
                $table->addRow([
                    $environment->getName(),
                    $environment->getLocation(),
                    $environment->getType(),
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
