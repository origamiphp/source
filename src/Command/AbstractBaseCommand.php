<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Environment;
use App\Exception\InvalidEnvironmentException;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\SystemManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractBaseCommand extends Command
{
    /** @var SystemManager */
    protected $systemManager;

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
     * @param SystemManager            $systemManager
     * @param ValidatorInterface       $validator
     * @param DockerCompose            $dockerCompose
     * @param EventDispatcherInterface $eventDispatcher
     * @param null|string              $name
     */
    public function __construct(
        SystemManager $systemManager,
        ValidatorInterface $validator,
        DockerCompose $dockerCompose,
        EventDispatcherInterface $eventDispatcher,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->systemManager = $systemManager;
        $this->validator = $validator;
        $this->dockerCompose = $dockerCompose;
        $this->eventDispatcher = $eventDispatcher;
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
     * @param InputInterface $input
     *
     * @throws InvalidEnvironmentException
     */
    protected function checkPrequisites(InputInterface $input): void
    {
        // 1. Try to load the currently running environment.
        $environment = $this->systemManager->getActiveEnvironment();
        if ($environment instanceof Environment) {
            $this->environment = $environment;
        }

        // 2. Try to load the environment from the user input (i.e. BuildCommand, StartCommand, and UninstallCommand).
        if (!$this->environment instanceof Environment && $input->hasArgument('environment')) {
            $argument = $input->getArgument('environment');

            if (\is_string($argument) && $argument !== '') {
                $environment = $this->systemManager->getEnvironmentByName($argument);

                if ($environment instanceof Environment) {
                    $this->environment = $environment;
                }
            }
        }

        // 3. Try to load the environment from the current location.
        if (!$this->environment instanceof Environment && ($location = getcwd())) {
            $environment = $this->systemManager->getEnvironmentByLocation($location);

            if ($environment instanceof Environment) {
                $this->environment = $environment;
            }
        }

        // 4. Throw an exception is there is still no defined environment.
        if ($this->environment instanceof Environment) {
            $this->dockerCompose->setActiveEnvironment($this->environment);
        } else {
            throw new InvalidEnvironmentException(
                'An environment must be given, please consider using the install command instead.'
            );
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
