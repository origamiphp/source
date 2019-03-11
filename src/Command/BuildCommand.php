<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\EnvironmentException;
use App\Manager\ApplicationLock;
use App\Manager\EnvironmentVariables;
use App\Traits\CustomCommandsTrait;
use App\Traits\SymfonyProcessTrait;
use App\Validator\Constraints\DotEnvExists;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BuildCommand extends Command
{
    use SymfonyProcessTrait;
    use CustomCommandsTrait;

    /**
     * BuildCommand constructor.
     *
     * @param string|null $name
     * @param ApplicationLock $applicationLock
     * @param EnvironmentVariables $environmentVariables
     */
    public function __construct(?string $name = null, ApplicationLock $applicationLock, EnvironmentVariables $environmentVariables, ValidatorInterface $validator)
    {
        parent::__construct($name);

        $this->applicationLock = $applicationLock;
        $this->environmentVariables = $environmentVariables;
        $this->validator = $validator;
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
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->project = getcwd();

        try {
            $this->checkEnvironmentConfiguration();

            $environmentVariables = $this->environmentVariables->getRequiredVariables($this->project);
            $this->showServicesStatus($environmentVariables);
        } catch (\Exception $e) {
            $this->io->error($e->getMessage());
        }
    }

    /**
     * Checks whether the environment has been installed and correctly configured.
     *
     * @throws EnvironmentException
     */
    private function checkEnvironmentConfiguration(): void
    {
        $dotEnvConstraint = new DotEnvExists();
        $errors = $this->validator->validate($this->project, $dotEnvConstraint);
        if ($errors->has(0) !== true) {
            $this->environmentVariables->loadFromDotEnv("{$this->project}/var/docker/.env");
        } else {
            throw new EnvironmentException($errors[0]->getMessage());
        }

        if (!$environment = getenv('DOCKER_ENVIRONMENT')) {
            throw new EnvironmentException(
                'The environment is not properly configured, executing the "install" command.'
            );
        }
    }

    /**
     * Builds or rebuilds the Docker services associated to the current environment.
     *
     * @param array $environmentVariables
     */
    private function showServicesStatus(array $environmentVariables): void
    {
        $command = ['docker-compose', 'build', '--pull', '--parallel'];

        $process = new Process($command, null, $environmentVariables, null, 3600.00);
        $this->foreground($process);
    }
}
