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
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

class StartCommand extends Command
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
     * StartCommand constructor.
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
        $this->setName('origami:start');
        $this->setAliases(['start']);

        $this->setDescription('Starts an environment previously installed in the current directory');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->project = getcwd();

        if (!$lock = $this->applicationLock->getCurrentLock()) {
            try {
                $this->checkEnvironmentConfiguration();

                $environmentVariables = $this->environmentVariables->getRequiredVariables($this->project);
                $this->startDockerSynchronization($environmentVariables);
                $this->startDockerServices($environmentVariables);

                $this->applicationLock->generateLock($this->project);
            } catch (\InvalidArgumentException|\Psr\SimpleCache\InvalidArgumentException $e) {
                $this->io->warning('An error occurred while generating the lock entry.');
            }
        } else {
            $this->io->error(
                $lock === $this->project
                    ? 'The environment is already running.'
                    : 'Unable to start an environment when another is still running.'
            );
        }
    }

    /**
     * Check whether the environment has been installed and correctly configured.
     *
     * @throws \InvalidArgumentException
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

        // 3. Check whether all configuration files are present
        $finder = new Finder();
        $finder->files()->in(__DIR__ . "/../Resources/$environment")->depth(0);

        foreach ($finder as $file) {
            $filename = str_replace(
                'custom-',
                '',
                "{$this->project}/var/docker/{$file->getFilename()}"
            );

            if (!$filesystem->exists($filename)) {
                throw new \InvalidArgumentException(
                    'At least one of the configuration files is missing, consider executing the "install" command.'
                );
            }
        }
    }

    /**
     * Starts the Docker synchronization needed to share the project source code.
     *
     * @param array $environmentVariables
     */
    private function startDockerSynchronization(array $environmentVariables): void
    {
        $process = new Process(
            ['docker-sync', 'start', "--config={$this->project}/var/docker/docker-sync.yml", '--dir="${HOME}/.docker-sync'],
            null,
            $environmentVariables,
            null,
            3600.00
        );
        $this->foreground($process);

        if ($process->isSuccessful()) {
            $this->io->success('Docker synchronization successfully started.');
        } else {
            $this->io->error('An error occurred while starting the Docker synchronization.');
        }
    }

    /**
     * Starts the Docker services after building the associated images.
     *
     * @param array $environmentVariables
     */
    private function startDockerServices(array $environmentVariables): void
    {
        $process = new Process(
            ['docker-compose', 'up', '--build', '--detach', '--remove-orphans'],
            null,
            $environmentVariables,
            null,
            3600.00
        );
        $this->foreground($process);

        if ($process->isSuccessful()) {
            $this->io->success('Docker services successfully started.');
        } else {
            $this->io->error('An error occurred while starting the Docker services.');
        }
    }
}
