<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Project;
use App\Helper\CommandExitCode;
use App\Manager\ProjectManager;
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
     * @param ProjectManager       $projectManager
     * @param string|null          $name
     */
    public function __construct(
        ProjectManager $projectManager,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->projectManager = $projectManager;
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

        $projects = $this->projectManager->getAllProjects();
        if (count($projects) > 0) {
            $table = new Table($output);
            $table->setHeaders(['ID', 'Name', 'Location', 'Type', 'Domains']);

            /** @var Project $project */
            foreach ($projects as $project) {
                $table->addRow([
                    $project->getId(),
                    $project->getName(),
                    $project->getLocation(),
                    $project->getType(),
                    $project->getDomains() ?: 'N/A'
                ]);
            }

            $table->render();
        } else {
            $this->io->note('There is no registered environment at the moment.');
        }

        return CommandExitCode::SUCCESS;
    }
}
