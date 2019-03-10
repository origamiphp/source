<?php

declare(strict_types=1);

namespace App\Command;

use App\Manager\ApplicationLock;
use App\Manager\EnvironmentVariables;
use App\Traits\SymfonyProcessTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class PsCommand extends Command
{
    use SymfonyProcessTrait;

    /** @var ApplicationLock */
    private $applicationLock;

    /** @var EnvironmentVariables */
    private $environmentVariables;

    /** @var SymfonyStyle */
    private $io;

    /** @var string */
    private $project;

    /**
     * RestartCommand constructor.
     *
     * @param string|null $name
     * @param ApplicationLock $applicationLock
     * @param EnvironmentVariables $environmentVariables
     */
    public function __construct(?string $name = null, ApplicationLock $applicationLock, EnvironmentVariables $environmentVariables)
    {
        parent::__construct($name);

        $this->applicationLock = $applicationLock;
        $this->environmentVariables = $environmentVariables;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('origami:ps');
        $this->setAliases(['ps']);

        $this->setDescription('Shows the status of an environment previously started');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);

        if ($this->project = $this->applicationLock->getCurrentLock()) {
            try {
                $this->checkEnvironmentConfiguration();

                $this->io->success('An environment is currently running.');
                $this->io->listing(
                    [
                        "Project location: {$this->project}",
                        'Environment type:' . getenv('DOCKER_ENVIRONMENT')
                    ]
                );

                $environmentVariables = $this->environmentVariables->getRequiredVariables($this->project);
                $this->showDockerServices($environmentVariables);
            } catch (\Exception $e) {
                $this->io->error($e->getMessage());
            }
        } else {
            $this->io->error('There is no running environment.');
        }
    }

    /**
     * Check whether the environment has been installed and correctly configured.
     */
    private function checkEnvironmentConfiguration(): void
    {
        $filesystem = new Filesystem();

        // 1. Check whether the file ".env" is present
        $configuration = "{$this->project}/var/docker/.env";
        if ($filesystem->exists($configuration)) {
            $this->environmentVariables->loadFromDotEnv($configuration);
        } else {
            throw new \InvalidArgumentException(
                'The environment is not configured, consider consider executing the "install" command.'
            );
        }

        // 2. Check whether the environment type can be identified
        if (!$environment = getenv('DOCKER_ENVIRONMENT')) {
            throw new \InvalidArgumentException(
                'The environment is not properly configured, consider consider executing the "install" command.'
            );
        }
    }

    /**
     * Shows the status of the Docker services associated to the current environment.
     *
     * @param array $environmentVariables
     */
    private function showDockerServices(array $environmentVariables): void
    {
        $process = new Process(
            ['docker-compose', 'ps'],
            null,
            $environmentVariables,
            null,
            3600.00
        );
        $this->foreground($process);
    }
}
