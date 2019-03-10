<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\EnvironmentException;
use App\Manager\ApplicationLock;
use App\Manager\EnvironmentVariables;
use App\Traits\SymfonyProcessTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class StopCommand extends Command
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
     * StopCommand constructor.
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
        $this->setName('origami:stop');
        $this->setAliases(['stop']);

        $this->setDescription('Stops an environment previously started');
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
                $this->stopDockerServices($environmentVariables);
                $this->stopDockerSynchronization($environmentVariables);

                $this->applicationLock->removeLock();
            } catch (\Exception $e) {
                $this->io->error($e->getMessage());
            }
        } else {
            $this->io->error('There is no running environment.');
        }
    }

    /**
     * Checks whether the environment has been installed and correctly configured.
     *
     * @throws EnvironmentException
     */
    private function checkEnvironmentConfiguration(): void
    {
        $filesystem = new Filesystem();

        $configuration = "{$this->project}/var/docker/.env";
        if ($filesystem->exists($configuration)) {
            $this->environmentVariables->loadFromDotEnv($configuration);
        } else {
            throw new EnvironmentException(
                sprintf('The running environment has been identified, but the "%s" file is missing.', $configuration)
            );
        }
    }

    /**
     * Stops the Docker services of the current environment.
     *
     * @param array $environmentVariables
     */
    private function stopDockerServices(array $environmentVariables): void
    {
        $process = new Process(
            ['docker-compose', 'stop'],
            null,
            $environmentVariables,
            null,
            3600.00
        );
        $this->foreground($process);

        if ($process->isSuccessful()) {
            $this->io->success('Docker services successfully stopped.');
        } else {
            $this->io->error('An error occurred while stoppping the Docker services.');
        }
    }

    /**
     * Stops the Docker synchronization needed to share the project source code.
     *
     * @param array $environmentVariables
     */
    private function stopDockerSynchronization(array $environmentVariables): void
    {
        $process = new Process(
            ['docker-sync', 'stop', "--config={$this->project}/var/docker/docker-sync.yml", '--dir="${HOME}/.docker-sync'],
            null,
            $environmentVariables,
            null,
            3600.00
        );
        $this->foreground($process);

        if ($process->isSuccessful()) {
            $this->io->success('Docker synchronization successfully stopped.');
        } else {
            $this->io->error('An error occurred while stopping the Docker synchronization.');
        }
    }
}
