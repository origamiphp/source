<?php

declare(strict_types=1);

namespace App\Command;

use App\Environment\EnvironmentEntity;
use App\Exception\InvalidEnvironmentException;
use App\Helper\ProcessProxy;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\Database;
use App\Middleware\SystemManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractBaseCommand extends Command
{
    /** @var Database */
    protected $database;

    /** @var SystemManager */
    protected $systemManager;

    /** @var ValidatorInterface */
    protected $validator;

    /** @var DockerCompose */
    protected $dockerCompose;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var ProcessProxy */
    protected $processProxy;

    public function __construct(
        Database $database,
        SystemManager $systemManager,
        ValidatorInterface $validator,
        DockerCompose $dockerCompose,
        EventDispatcherInterface $eventDispatcher,
        ProcessProxy $processProxy,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->database = $database;
        $this->systemManager = $systemManager;
        $this->validator = $validator;
        $this->dockerCompose = $dockerCompose;
        $this->eventDispatcher = $eventDispatcher;
        $this->processProxy = $processProxy;
    }

    /**
     * Attempts to load the environment to manage in different ways and throws an exception if this is not possible.
     *
     * @throws InvalidEnvironmentException
     */
    protected function getEnvironment(InputInterface $input): EnvironmentEntity
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

    /**
     * Prints additional details to the console: environment location and environment type.
     */
    protected function printEnvironmentDetails(EnvironmentEntity $environment, SymfonyStyle $io): void
    {
        $io->success('An environment is currently running.');
        $io->listing(
            [
                sprintf('Environment location: %s', $environment->getLocation()),
                sprintf('Environment type: %s', $environment->getType()),
            ]
        );
    }
}
