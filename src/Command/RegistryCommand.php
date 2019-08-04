<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Environment;
use App\Helper\CommandExitCode;
use App\Manager\EnvironmentManager;
use App\Traits\CustomCommandsTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RegistryCommand extends Command
{
    use CustomCommandsTrait;

    /**
     * RegistryCommand constructor.
     *
     * @param EnvironmentManager $environmentManager
     * @param string|null        $name
     */
    public function __construct(
        EnvironmentManager $environmentManager,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->environmentManager = $environmentManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('origami:registry');
        $this->setAliases(['registry']);
        $this->setDescription('Shows the list of registered environments');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);

        $environments = $this->environmentManager->getAllEnvironments();
        if (\count($environments) > 0) {
            $table = new Table($output);
            $table->setHeaders(['ID', 'Name', 'Location', 'Type', 'Domains']);

            /** @var Environment $environment */
            foreach ($environments as $environment) {
                $table->addRow([
                    $environment->getId(),
                    $environment->getName(),
                    $environment->getLocation(),
                    $environment->getType(),
                    $environment->getDomains() ?: 'N/A',
                ]);
            }

            $table->render();
        } else {
            $this->io->note('There is no registered environment at the moment.');
        }

        return CommandExitCode::SUCCESS;
    }
}
