<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Project;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use App\Manager\EnvironmentVariables;
use App\Manager\Process\DockerCompose;
use App\Manager\ProjectManager;
use App\Traits\CustomCommandsTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UninstallCommand extends Command
{
    use CustomCommandsTrait;

    /** @var DockerCompose */
    private $dockerCompose;

    /**
     * UninstallCommand constructor.
     *
     * @param ProjectManager       $projectManager
     * @param EnvironmentVariables $environmentVariables
     * @param string|null          $name
     */
    public function __construct(
        ProjectManager $projectManager,
        EnvironmentVariables $environmentVariables,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->projectManager = $projectManager;
        $this->environmentVariables = $environmentVariables;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('origami:uninstall');
        $this->setAliases(['uninstall']);

        $this->addArgument(
            'project',
            InputArgument::REQUIRED,
            'Name of the project for which the environment will be uninstalled'
        );

        $this->setDescription('Uninstalls the environment of the given project');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);

        if ($this->io->confirm('Are you sure you want to uninstall this environment?', false)) {
            /** @var string $projectToUninstall */
            $projectToUninstall = $input->getArgument('project');

            $activeProject = $this->projectManager->getActiveProject();
            if (!$activeProject instanceof Project || $activeProject->getName() !== $projectToUninstall) {
                try {
                    $this->projectManager->uninstall($projectToUninstall);
                } catch (OrigamiExceptionInterface $e) {
                    $this->io->error($e->getMessage());
                    $exitCode = CommandExitCode::EXCEPTION;
                }
            } else {
                $this->io->error('Unable to uninstall a project with a running environment.');
                $exitCode = CommandExitCode::INVALID;
            }
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
