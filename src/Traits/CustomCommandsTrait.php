<?php

declare(strict_types=1);

namespace App\Traits;

use App\Exception\EnvironmentException;
use App\Manager\ApplicationLock;
use App\Manager\EnvironmentVariables;
use App\Validator\Constraints\ConfigurationFiles;
use App\Validator\Constraints\DotEnvExists;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

trait CustomCommandsTrait
{
    /** @var ApplicationLock */
    private $applicationLock;

    /** @var EnvironmentVariables */
    private $environmentVariables;

    /** @var ValidatorInterface */
    private $validator;

    /** @var SymfonyStyle */
    private $io;

    /** @var string */
    private $project;

    /**
     * Checks whether the environment has been installed and correctly configured.
     *
     * @param bool $checkFiles
     *
     * @throws EnvironmentException
     */
    private function checkEnvironmentConfiguration(bool $checkFiles = false): void
    {
        $dotEnvConstraint = new DotEnvExists();
        $errors = $this->validator->validate($this->project, $dotEnvConstraint);
        if ($errors->has(0) !== true) {
            $this->environmentVariables->loadFromDotEnv("{$this->project}/var/docker/.env");
        } else {
            throw new EnvironmentException($errors[0]->getMessage());
        }

        if (!getenv('DOCKER_ENVIRONMENT')) {
            throw new EnvironmentException(
                'The environment is not properly configured, consider executing the "install" command.'
            );
        }

        if ($checkFiles === true) {
            $filesConstraint = new ConfigurationFiles();
            $errors = $this->validator->validate($this->project, $filesConstraint);
            if ($errors->has(0) === true) {
                throw new EnvironmentException($errors[0]->getMessage());
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
                "Project location: {$this->project}",
                'Environment type: '.getenv('DOCKER_ENVIRONMENT'),
            ]
        );
    }
}
