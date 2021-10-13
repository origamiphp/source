<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ApplicationData;
use App\Service\Wrapper\OrigamiStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RegistryCommand extends AbstractBaseCommand
{
    /** {@inheritdoc} */
    protected static $defaultName = 'origami:registry';
    /** {@inheritdoc} */
    protected static $defaultDescription = 'Shows the list of registered environments';

    private ApplicationData $applicationData;

    public function __construct(ApplicationData $applicationData, ?string $name = null)
    {
        parent::__construct($name);

        $this->applicationData = $applicationData;
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
