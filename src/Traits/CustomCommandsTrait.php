<?php

declare(strict_types=1);

namespace App\Traits;

use App\Entity\Environment;
use App\Exception\InvalidEnvironmentException;
use App\Manager\EnvironmentManager;
use App\Validator\Constraints\ConfigurationFiles;
use App\Validator\Constraints\DotEnvExists;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Validator\Validator\ValidatorInterface;

trait CustomCommandsTrait
{
    /** @var EnvironmentManager */
    private $environmentManager;

    /** @var ValidatorInterface */
    private $validator;

    /** @var SymfonyStyle */
    private $io;

    /** @var Environment */
    private $environment;

    /**
     * Retrieves environment variables required to run processes.
     *
     * @param Environment $environment
     *
     * @return array
     */
    public function getRequiredVariables(Environment $environment): array
    {
        return [
            'COMPOSE_FILE' => "{$environment->getLocation()}/var/docker/docker-compose.yml",
            'COMPOSE_PROJECT_NAME' => $environment->getType().'_'.$environment->getName(),
            'DOCKER_PHP_IMAGE' => getenv('DOCKER_PHP_IMAGE'),
            'PROJECT_LOCATION' => $environment->getLocation(),
        ];
    }

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
        $errors = $this->validator->validate($this->environment, $dotEnvConstraint);
        if ($errors->has(0) !== true) {
            $dotenv = new Dotenv();
            $dotenv->overload("{$this->environment->getLocation()}/var/docker/.env");
        } else {
            throw new InvalidEnvironmentException($errors[0]->getMessage());
        }

        if ($checkFiles === true) {
            $filesConstraint = new ConfigurationFiles();
            $errors = $this->validator->validate($this->environment, $filesConstraint);
            if ($errors->has(0) === true) {
                throw new InvalidEnvironmentException($errors[0]->getMessage());
            }
        }
    }

    /**
     * Prints additional details to the console: environment location and environment type.
     */
    private function printEnvironmentDetails(): void
    {
        $this->io->success('An environment is currently running.');
        $this->io->listing(
            [
                "Environment location: {$this->environment->getLocation()}",
                "Environment type: {$this->environment->getType()}",
            ]
        );
    }
}
