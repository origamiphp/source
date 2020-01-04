<?php

declare(strict_types=1);

namespace App\Command\Main;

use App\Command\AbstractBaseCommand;
use App\Exception\InvalidConfigurationException;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\SystemManager;
use App\Validator\Constraints\LocalDomains;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class InstallCommand extends AbstractBaseCommand
{
    /** @var array */
    private array $environments;

    /**
     * InstallCommand constructor.
     */
    public function __construct(
        SystemManager $systemManager,
        ValidatorInterface $validator,
        DockerCompose $dockerCompose,
        EventDispatcherInterface $eventDispatcher,
        array $environments,
        ?string $name = null
    ) {
        parent::__construct($systemManager, $validator, $dockerCompose, $eventDispatcher, $name);

        $this->environments = $environments;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('origami:install');
        $this->setAliases(['install']);

        $this->setDescription('Installs a Docker environment in the desired directory');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $type = $this->io->choice('Which type of environment you want to install?', $this->environments);

            /** @var string $location */
            $location = realpath(
                $this->io->ask(
                    'Where do you want to install the environment?',
                    '.',
                    function ($answer) {
                        return $this->installationPathCallback($answer);
                    }
                )
            );

            if ($this->io->confirm('Do you want to generate a locally-trusted development certificate?', false)) {
                $domains = $this->io->ask(
                    'Which domains does this certificate belong to?',
                    "{$type}.localhost www.{$type}.localhost",
                    function ($answer) {
                        return $this->localDomainsCallback($answer);
                    }
                );
            } else {
                $domains = null;
            }

            $this->systemManager->install($location, $type, $domains);
            $this->io->success('Environment successfully installed.');
        } catch (OrigamiExceptionInterface $e) {
            $this->io->error($e->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }

    /**
     * Validates the response provided by the user to the installation path question.
     *
     * @throws InvalidConfigurationException
     */
    private function installationPathCallback(string $answer): string
    {
        if (!is_dir($answer)) {
            throw new InvalidConfigurationException('An existing directory must be provided.');
        }

        return $answer;
    }

    /**
     * Validates the response provided by the user to the local domains question.
     *
     * @throws InvalidConfigurationException
     */
    private function localDomainsCallback(string $answer): string
    {
        $constraint = new LocalDomains();
        $errors = $this->validator->validate($answer, $constraint);
        if ($errors->has(0)) {
            /** @var string $message */
            $message = $errors->get(0)->getMessage();

            throw new InvalidConfigurationException($message);
        }

        return $answer;
    }
}
