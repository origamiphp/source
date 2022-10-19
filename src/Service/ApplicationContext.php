<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\FilesystemException;
use App\Exception\InvalidConfigurationException;
use App\Exception\InvalidEnvironmentException;
use App\Service\Setup\Validator;
use App\Service\Wrapper\ProcessProxy;
use App\ValueObject\EnvironmentEntity;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Yaml;

class ApplicationContext
{
    private EnvironmentEntity $environment;
    private ?array $configuration = null;

    public function __construct(
        private ApplicationData $applicationData,
        private ProcessProxy $processProxy,
        private Validator $validator,
        private string $installDir,
    ) {
    }

    /**
     * Attempts to load the environment to manage in different ways and throws an exception if this is not possible.
     *
     * @throws FilesystemException
     * @throws InvalidConfigurationException
     * @throws InvalidEnvironmentException
     */
    public function loadEnvironment(InputInterface $input): void
    {
        // 1. Try to load the currently running environment.
        $environment = $this->applicationData->getActiveEnvironment();

        // 2. Try to load the environment from the user input (i.e. BuildCommand, StartCommand, and UninstallCommand).
        if (!$environment instanceof EnvironmentEntity && $input->hasArgument('environment')) {
            $argument = $input->getArgument('environment');

            if (\is_string($argument) && $argument !== '') {
                $environment = $this->applicationData->getEnvironmentByName($argument);
            }
        }

        // 3. Try to load the environment from the current location.
        if (!$environment instanceof EnvironmentEntity) {
            $location = $this->processProxy->getWorkingDirectory();
            $environment = $this->applicationData->getEnvironmentByLocation($location);
        }

        // 4. Throw an exception is there is still no defined environment.
        if (!$environment instanceof EnvironmentEntity) {
            throw new InvalidEnvironmentException('An environment must be given, please consider using the install command instead.');
        }

        $this->checkEnvironmentConfiguration($environment);
        $this->environment = $environment;
    }

    /**
     * Retrieves the currently active environment.
     */
    public function getActiveEnvironment(): EnvironmentEntity
    {
        return $this->environment;
    }

    /**
     * Retrieves the project name based on the currently active environment data.
     */
    public function getProjectName(): string
    {
        return "{$this->environment->getType()}_{$this->environment->getName()}";
    }

    /**
     * Retrieves the configuration from the environment "docker-compose.yml" file.
     *
     * @return array[]
     *
     * @throws FilesystemException
     */
    public function getEnvironmentConfiguration(): array
    {
        if ($this->configuration) {
            return $this->configuration;
        }

        $environment = $this->getActiveEnvironment();
        $configurationPath = $environment->getLocation().$this->installDir.'/docker-compose.yml';

        if (!is_file($configurationPath)) {
            throw new FilesystemException(sprintf("Unable to find the file.\n%s", $configurationPath));
        }

        $configuration = Yaml::parseFile($configurationPath);
        if (!\is_array($configuration)) {
            throw new FilesystemException(sprintf("Unable to parse the file content as YAML.\n%s", $configurationPath));
        }

        return $this->configuration = $configuration;
    }

    /**
     * Checks whether the environment has been installed and correctly configured.
     *
     * @throws InvalidConfigurationException
     */
    private function checkEnvironmentConfiguration(EnvironmentEntity $environment): void
    {
        if (!$this->validator->validateConfigurationFiles($environment)) {
            throw new InvalidConfigurationException('The environment is not configured, consider executing the "install" command.');
        }
    }
}
