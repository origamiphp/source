<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Environment;
use App\Exception\InvalidEnvironmentException;
use App\Manager\EnvironmentManager;
use App\Manager\Process\DockerCompose;
use App\Validator\Constraints\ConfigurationFiles;
use App\Validator\Constraints\DotEnvExists;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractBaseCommand extends Command
{
    /** @var EnvironmentManager */
    protected $environmentManager;

    /** @var ValidatorInterface */
    protected $validator;

    /** @var DockerCompose */
    protected $dockerCompose;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var Environment */
    protected $environment;

    /** @var SymfonyStyle */
    protected $io;

    /**
     * AbstractBaseCommand constructor.
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
     * Retrieves the active environment or throw an exception is there is no running environment.
     *
     * @throws InvalidEnvironmentException
     *
     * @return Environment
     */
    protected function getActiveEnvironment(): Environment
    {
        $activeEnvironment = $this->environmentManager->getActiveEnvironment();
        if (!$activeEnvironment instanceof Environment) {
            throw new InvalidEnvironmentException('There is no running environment.');
        }

        return $activeEnvironment;
    }

    /**
     * Retrieves environment variables required to run processes.
     *
     * @param Environment $environment
     *
     * @throws InvalidEnvironmentException
     *
     * @return array
     */
    protected function getRequiredVariables(Environment $environment): array
    {
        $this->checkEnvironmentConfiguration();

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
     * @throws InvalidEnvironmentException
     */
    protected function checkEnvironmentConfiguration(): void
    {
        $dotEnvConstraint = new DotEnvExists();
        $errors = $this->validator->validate($this->environment, $dotEnvConstraint);
        if ($errors->has(0) !== true) {
            $dotenv = new Dotenv();
            $dotenv->overload("{$this->environment->getLocation()}/var/docker/.env");
        } else {
            throw new InvalidEnvironmentException($errors[0]->getMessage());
        }

        $filesConstraint = new ConfigurationFiles();
        $errors = $this->validator->validate($this->environment, $filesConstraint);
        if ($errors->has(0) === true) {
            throw new InvalidEnvironmentException($errors[0]->getMessage());
        }
    }

    /**
     * Prints additional details to the console: environment location and environment type.
     */
    protected function printEnvironmentDetails(): void
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
