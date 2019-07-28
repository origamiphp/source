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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BuildCommand extends Command
{
    use CustomCommandsTrait;

    /** @var DockerCompose */
    private $dockerCompose;

    /**
     * BuildCommand constructor.
     *
     * @param ProjectManager       $projectManager
     * @param EnvironmentVariables $environmentVariables
     * @param ValidatorInterface   $validator
     * @param DockerCompose        $dockerCompose
     * @param string|null          $name
     */
    public function __construct(
        ProjectManager $projectManager,
        EnvironmentVariables $environmentVariables,
        ValidatorInterface $validator,
        DockerCompose $dockerCompose,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->projectManager = $projectManager;
        $this->environmentVariables = $environmentVariables;
        $this->validator = $validator;
        $this->dockerCompose = $dockerCompose;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('origami:build');
        $this->setAliases(['build']);

        $this->setDescription('Builds or rebuilds an environment previously installed in the current directory');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);

        $activeProject = $this->projectManager->getActiveProject();
        if ($activeProject instanceof Project) {
            $this->project = $activeProject;

            try {
                $this->checkEnvironmentConfiguration();
                $environmentVariables = $this->environmentVariables->getRequiredVariables($this->project);
                $this->dockerCompose->buildServices($environmentVariables);
            } catch (OrigamiExceptionInterface $e) {
                $this->io->error($e->getMessage());
                $exitCode = CommandExitCode::EXCEPTION;
            }
        } else {
            $this->io->error('There is no running environment.');
            $exitCode = CommandExitCode::INVALID;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
