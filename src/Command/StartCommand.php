<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Project;
use App\Event\EnvironmentStartedEvent;
use App\Exception\ConfigurationException;
use App\Helper\CommandExitCode;
use App\Manager\EnvironmentVariables;
use App\Manager\Process\DockerCompose;
use App\Manager\ProjectManager;
use App\Traits\CustomCommandsTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StartCommand extends Command
{
    use CustomCommandsTrait;

    /** @var DockerCompose */
    private $dockerCompose;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * StartCommand constructor.
     *
     * @param string|null              $name
     * @param ProjectManager           $projectManager
     * @param EnvironmentVariables     $environmentVariables
     * @param ValidatorInterface       $validator
     * @param DockerCompose            $dockerCompose
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ?string $name = null,
        ProjectManager $projectManager,
        EnvironmentVariables $environmentVariables,
        ValidatorInterface $validator,
        DockerCompose $dockerCompose,
        EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($name);

        $this->projectManager = $projectManager;
        $this->environmentVariables = $environmentVariables;
        $this->validator = $validator;
        $this->dockerCompose = $dockerCompose;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('origami:start');
        $this->setAliases(['start']);

        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Forces the startup of the environment'
        );

        $this->setDescription('Starts an environment previously installed in the current directory');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);

        try {
            if (!$location = getcwd()) {
                throw new ConfigurationException(
                    'Unable to retrieve the current working directory.'
                );
            }

            $locationProject = $this->projectManager->getLocationProject($location);
            if (!$locationProject instanceof Project) {
                throw new ConfigurationException(
                    'An environment must be installed, please consider using the install command instead.'
                );
            }

            $activeProject = $this->projectManager->getActiveProject();
            if (!$activeProject || $input->getOption('force')) {
                $this->project = $locationProject;

                $this->checkEnvironmentConfiguration(true);
                $environmentVariables = $this->environmentVariables->getRequiredVariables($this->project);

                if ($this->dockerCompose->startDockerServices($environmentVariables)) {
                    $this->io->success('Docker services successfully started.');

                    $event = new EnvironmentStartedEvent($this->project, $environmentVariables, $this->io);
                    $this->eventDispatcher->dispatch($event);
                } else {
                    $this->io->error('An error occurred while starting the Docker services.');
                }
            } else {
                $this->io->error(
                    $locationProject === $activeProject
                        ? 'The environment is already running.'
                        : 'Unable to start an environment when another is still running.'
                );
                $exitCode = CommandExitCode::INVALID;
            }
        } catch (\Exception $e) {
            $this->io->error($e->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
