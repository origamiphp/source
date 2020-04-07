<?php

declare(strict_types=1);

namespace App\Command\Main;

use App\Command\AbstractBaseCommand;
use App\Environment\EnvironmentEntity;
use App\Exception\InvalidConfigurationException;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use App\Helper\ProcessProxy;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\Database;
use App\Middleware\DockerHub;
use App\Middleware\SystemManager;
use App\Validator\Constraints\LocalDomains;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class InstallCommand extends AbstractBaseCommand
{
    /** @var DockerHub */
    private $dockerHub;

    /** @var array */
    private $availableTypes = [EnvironmentEntity::TYPE_MAGENTO2, EnvironmentEntity::TYPE_SYMFONY];

    public function __construct(
        Database $database,
        SystemManager $systemManager,
        ValidatorInterface $validator,
        DockerCompose $dockerCompose,
        EventDispatcherInterface $eventDispatcher,
        ProcessProxy $processProxy,
        DockerHub $dockerHub,
        ?string $name = null
    ) {
        parent::__construct($database, $systemManager, $validator, $dockerCompose, $eventDispatcher, $processProxy, $name);

        $this->dockerHub = $dockerHub;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Installs a Docker environment in the desired directory');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->comment('Please note that the environment will be installed in the current directory.');

        try {
            $type = $this->io->choice('Which type of environment you want to install?', $this->availableTypes);

            $availableVersions = $this->dockerHub->getImageTags("{$type}-php");
            $phpVersion = \count($availableVersions) > 1
                ? $this->io->choice('Which version of PHP do you want to use?', $availableVersions, 'latest')
                : $availableVersions[0]
            ;

            if ($this->io->confirm('Do you want to generate a locally-trusted development certificate?', false)) {
                $domains = $this->io->ask(
                    'Which domains does this certificate belong to?',
                    sprintf('%s.localhost www.%s.localhost', $type, $type),
                    function ($answer) {
                        return $this->localDomainsCallback($answer);
                    }
                );
            } else {
                $domains = null;
            }

            /** @var string $location */
            $location = realpath('.');
            $environment = $this->systemManager->install($location, $type, $phpVersion, $domains);

            $this->database->add($environment);
            $this->database->save();

            $this->io->success('Environment successfully installed.');
        } catch (OrigamiExceptionInterface $exception) {
            $this->io->error($exception->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
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
