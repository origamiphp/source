<?php

declare(strict_types=1);

namespace App\Traits;

use App\Entity\Project;
use App\Exception\InvalidEnvironmentException;
use App\Manager\EnvironmentVariables;
use App\Manager\ProjectManager;
use App\Validator\Constraints\ConfigurationFiles;
use App\Validator\Constraints\DotEnvExists;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

trait CustomCommandsTrait
{
    /** @var ProjectManager */
    private $projectManager;

    /** @var EnvironmentVariables */
    private $environmentVariables;

    /** @var ValidatorInterface */
    private $validator;

    /** @var SymfonyStyle */
    private $io;

    /** @var Project */
    private $project;

    /**
     * Checks whether the environment has been installed and correctly configured.
     *
     * @param bool $checkFiles
     *
     * @throws InvalidEnvironmentException
     */
    private function checkEnvironmentConfiguration(bool $checkFiles = false): void
    {
        $dotEnvConstraint = new DotEnvExists();
        $errors = $this->validator->validate($this->project, $dotEnvConstraint);
        if ($errors->has(0) !== true) {
            $this->environmentVariables->loadFromDotEnv("{$this->project->getLocation()}/var/docker/.env");
        } else {
            throw new InvalidEnvironmentException($errors[0]->getMessage());
        }

        if (!getenv('DOCKER_ENVIRONMENT')) {
            throw new InvalidEnvironmentException(
                'The environment is not properly configured, consider executing the "install" command.'
            );
        }

        if ($checkFiles === true) {
            $filesConstraint = new ConfigurationFiles();
            $errors = $this->validator->validate($this->project, $filesConstraint);
            if ($errors->has(0) === true) {
                throw new InvalidEnvironmentException($errors[0]->getMessage());
            }
        }
    }

    /**
     * Prints additional details to the console: project location and environment type.
     */
    private function printEnvironmentDetails(): void
    {
        $this->io->success('An environment is currently running.');
        $this->io->listing(
            [
                "Project location: {$this->project->getLocation()}",
                'Environment type: '.getenv('DOCKER_ENVIRONMENT'),
            ]
        );
    }
}
