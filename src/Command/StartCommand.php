<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Environment;
use App\Event\EnvironmentStartedEvent;
use App\Exception\InvalidConfigurationException;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use App\Manager\EnvironmentManager;
use App\Manager\Process\DockerCompose;
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
     * @param EnvironmentManager       $environmentManager
     * @param ValidatorInterface       $validator
     * @param DockerCompose            $dockerCompose
     * @param EventDispatcherInterface $eventDispatcher
     * @param string|null              $name
     */
    public function __construct(
        EnvironmentManager $environmentManager,
        ValidatorInterface $validator,
        DockerCompose $dockerCompose,
        EventDispatcherInterface $eventDispatcher,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->environmentManager = $environmentManager;
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
                throw new InvalidConfigurationException(
                    'Unable to retrieve the current working directory.'
                );
            }

            $environment = $this->environmentManager->getEnvironmentByLocation($location);
            if (!$environment instanceof Environment) {
                throw new InvalidConfigurationException(
                    'An environment must be installed, please consider using the install command instead.'
                );
            }

            $activeEnvironment = $this->environmentManager->getActiveEnvironment();
            if (!$activeEnvironment || $input->getOption('force')) {
                $this->environment = $environment;

                $this->checkEnvironmentConfiguration(true);
                $environmentVariables = $this->getRequiredVariables($this->environment);

                if ($this->dockerCompose->startDockerServices($environmentVariables)) {
                    $this->io->success('Docker services successfully started.');

                    $event = new EnvironmentStartedEvent($this->environment, $environmentVariables, $this->io);
                    $this->eventDispatcher->dispatch($event);
                } else {
                    $this->io->error('An error occurred while starting the Docker services.');
                }
            } else {
                $this->io->error(
                    $environment === $activeEnvironment
                        ? 'The environment is already running.'
                        : 'Unable to start an environment when another is still running.'
                );
                $exitCode = CommandExitCode::INVALID;
            }
        } catch (OrigamiExceptionInterface $e) {
            $this->io->error($e->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
