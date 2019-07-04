<?php

declare(strict_types=1);

namespace App\Command\Services;

use App\Entity\Project;
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

abstract class AbstractServiceCommand extends Command implements ServiceCommandInterface
{
    use CustomCommandsTrait;

    /** @var DockerCompose */
    private $dockerCompose;

    /**
     * AbstractServiceCommand constructor.
     *
     * @param string|null          $name
     * @param ProjectManager       $projectManager
     * @param EnvironmentVariables $environmentVariables
     * @param ValidatorInterface   $validator
     * @param DockerCompose        $dockerCompose
     */
    public function __construct(
        ?string $name = null,
        ProjectManager $projectManager,
        EnvironmentVariables $environmentVariables,
        ValidatorInterface $validator,
        DockerCompose $dockerCompose
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
        $serviceName = $this->getServiceName();

        $this->setName("origami:services:$serviceName");
        $this->setAliases([$serviceName]);

        $this->setDescription("Opens a terminal on the \"$serviceName\" service");
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

                if ($output->isVerbose()) {
                    $this->printEnvironmentDetails();
                }

                $this->dockerCompose->openTerminal(
                    $this->getServiceName(),
                    $this->getUsername(),
                    $this->environmentVariables->getRequiredVariables($this->project)
                );
            } catch (\Exception $e) {
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
