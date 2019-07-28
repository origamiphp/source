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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LogsCommand extends Command
{
    use CustomCommandsTrait;

    /** @var DockerCompose */
    private $dockerCompose;

    /**
     * LogsCommand constructor.
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
        $this->setName('origami:logs');
        $this->setAliases(['logs']);

        $this->addArgument(
            'service',
            InputArgument::OPTIONAL,
            'Name of the service for which the logs will be shown'
        );

        $this->addOption(
            'tail',
            't',
            InputOption::VALUE_OPTIONAL,
            'Number of lines to show from the end of the logs for each service',
            10
        );

        $this->setDescription('Shows the logs of an environment previously started');
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

                $environmentVariables = $this->environmentVariables->getRequiredVariables($this->project);
                $this->dockerCompose->showServicesLogs(
                    (($tail = $input->getOption('tail')) && \is_string($tail)) ? (int) $tail : 0,
                    (($argument = $input->getArgument('service')) && \is_string($argument)) ? $argument : '',
                    $environmentVariables
                );
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
