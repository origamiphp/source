<?php

declare(strict_types=1);

namespace App\Helper;

use App\Environment\Configuration\AbstractConfiguration;
use App\Environment\EnvironmentEntity;
use App\Exception\FilesystemException;
use App\Exception\InvalidConfigurationException;
use App\Exception\InvalidEnvironmentException;
use App\Middleware\Binary\Docker;
use App\Middleware\Database;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Dotenv\Dotenv;

class CurrentContext
{
    private const INVALID_CONFIGURATION_MESSAGE = 'The environment is not configured, consider executing the "install" command.';

    private Database $database;
    private ProcessProxy $processProxy;
    private Docker $docker;
    private Validator $validator;

    public function __construct(Database $database, ProcessProxy $processProxy, Docker $docker, Validator $validator)
    {
        $this->database = $database;
        $this->processProxy = $processProxy;
        $this->docker = $docker;
        $this->validator = $validator;
    }

    /**
     * Attempts to load the environment to manage in different ways and throws an exception if this is not possible.
     *
     * @throws FilesystemException
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
        if (!$environment instanceof EnvironmentEntity) {
            throw new InvalidEnvironmentException(
                'An environment must be given, please consider using the install command instead.'
            );
        }

        return $environment;
    }

    /**
     * Defines the active environment, after checking the configuration if it's not a custom environment.
     *
     * @throws InvalidConfigurationException
     */
    public function setActiveEnvironment(EnvironmentEntity $environment): void
    {
        $this->checkEnvironmentConfiguration($environment);
        $this->docker->refreshEnvironmentVariables($environment);
    }

    /**
     * Checks whether the environment has been installed and correctly configured.
     *
     * @throws InvalidConfigurationException
     */
    private function checkEnvironmentConfiguration(EnvironmentEntity $environment): void
    {
        if ($this->validator->validateDotEnvExistence($environment)) {
            $dotenv = new Dotenv();
            $dotenv->usePutenv(true);
            $dotenv->overload($environment->getLocation().AbstractConfiguration::INSTALLATION_DIRECTORY.'.env');
        } else {
            throw new InvalidConfigurationException(self::INVALID_CONFIGURATION_MESSAGE);
        }

        if (!$this->validator->validateConfigurationFiles($environment)) {
            throw new InvalidConfigurationException(self::INVALID_CONFIGURATION_MESSAGE);
        }
    }
}
