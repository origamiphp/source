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
use Symfony\Component\Console\Output\OutputInterface;
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

    /** @var null|EnvironmentEntity */
    protected $environment;

    /** @var SymfonyStyle */
    protected $io;

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

        $this->environment = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
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
        if ($environment instanceof EnvironmentEntity) {
            $this->environment = $environment;
        }

        // 2. Try to load the environment from the user input (i.e. BuildCommand, StartCommand, and UninstallCommand).
        if (!$this->environment instanceof EnvironmentEntity && $input->hasArgument('environment')) {
            $argument = $input->getArgument('environment');

            if (\is_string($argument) && $argument !== '') {
                $environment = $this->database->getEnvironmentByName($argument);

                if ($environment instanceof EnvironmentEntity) {
                    $this->environment = $environment;
                }
            }
        }

        // 3. Try to load the environment from the current location.
        if (!$this->environment instanceof EnvironmentEntity) {
            $location = $this->processProxy->getWorkingDirectory();
            $environment = $this->database->getEnvironmentByLocation($location);

            if ($environment instanceof EnvironmentEntity) {
                $this->environment = $environment;
            }
        }

        // 4. Throw an exception is there is still no defined environment.
        if ($this->environment instanceof EnvironmentEntity) {
            $this->dockerCompose->setActiveEnvironment($this->environment);
        } else {
            throw new InvalidEnvironmentException(
                'An environment must be given, please consider using the install command instead.'
            );
        }

        return $this->environment;
    }

    /**
     * Prints additional details to the console: environment location and environment type.
     */
    protected function printEnvironmentDetails(): void
    {
        if ($this->environment instanceof EnvironmentEntity) {
            $this->io->success('An environment is currently running.');
            $this->io->listing(
                [
                    sprintf('Environment location: %s', $this->environment->getLocation()),
                    sprintf('Environment type: %s', $this->environment->getType()),
                ]
            );
        }
    }
}
