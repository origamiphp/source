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

class RestartCommand extends Command
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
        $this->setName('origami:restart');
        $this->setAliases(['restart']);

        $this->setDescription('Restarts an environment previously started');
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

                $environmentVariables = $this->environmentVariables->getRequiredVariables($this->project);
                $this->restartDockerServices($environmentVariables);
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

        $configuration = "{$this->project}/var/docker/.env";
        if ($filesystem->exists($configuration)) {
            $this->environmentVariables->loadFromDotEnv($configuration);
        } else {
            throw new \InvalidArgumentException(
                sprintf('The running environment has been identified, but the "%s" file is missing.', $configuration)
            );
        }
    }

    /**
     * Restarts the Docker services of the current environment.
     *
     * @param array $environmentVariables
     */
    private function restartDockerServices(array $environmentVariables): void
    {
        $process = new Process(
            ['docker-compose', 'restart'],
            null,
            $environmentVariables,
            null,
            3600.00
        );
        $this->foreground($process);

        if ($process->isSuccessful()) {
            $this->io->success('Docker services successfully restarted.');
        } else {
            $this->io->error('An error occurred while restarting the Docker services.');
        }
    }
}
