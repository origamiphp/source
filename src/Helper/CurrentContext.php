<?php

declare(strict_types=1);

namespace App\Helper;

use App\Environment\EnvironmentEntity;
use App\Exception\InvalidEnvironmentException;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\Database;
use Symfony\Component\Console\Input\InputInterface;

class CurrentContext
{
    /** @var Database */
    private $database;

    /** @var ProcessProxy */
    private $processProxy;

    /** @var DockerCompose */
    private $dockerCompose;

    public function __construct(Database $database, ProcessProxy $processProxy, DockerCompose $dockerCompose)
    {
        $this->database = $database;
        $this->processProxy = $processProxy;
        $this->dockerCompose = $dockerCompose;
    }

    /**
     * Attempts to load the environment to manage in different ways and throws an exception if this is not possible.
     *
     * @throws InvalidEnvironmentException
     */
    public function getEnvironment(InputInterface $input): EnvironmentEntity
    {
        // 1. Try to load the currently running environment.
        $environment = $this->database->getActiveEnvironment();

        // 2. Try to load the environment from the user input (i.e. BuildCommand, StartCommand, and UninstallCommand).
        if (!$environment instanceof EnvironmentEntity && $input->hasArgument('environment')) {
            $argument = $input->getArgument('environment');

            if (\is_string($argument) && $argument !== '') {
                $environment = $this->database->getEnvironmentByName($argument);
            }
        }

        // 3. Try to load the environment from the current location.
        if (!$environment instanceof EnvironmentEntity) {
            $location = $this->processProxy->getWorkingDirectory();
            $environment = $this->database->getEnvironmentByLocation($location);
        }

        // 4. Throw an exception is there is still no defined environment.
        if ($environment instanceof EnvironmentEntity) {
            $this->dockerCompose->setActiveEnvironment($environment);
        } else {
            throw new InvalidEnvironmentException(
                'An environment must be given, please consider using the install command instead.'
            );
        }

        return $environment;
    }
}
