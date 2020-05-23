<?php

declare(strict_types=1);

namespace App\Command\Main;

use App\Command\AbstractBaseCommand;
use App\Environment\EnvironmentEntity;
use App\Event\EnvironmentInstalledEvent;
use App\Exception\InvalidConfigurationException;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use App\Helper\ProcessProxy;
use App\Helper\RequirementsChecker;
use App\Middleware\Configuration\ConfigurationInstaller;
use App\Middleware\DockerHub;
use App\Validator\Constraints\LocalDomains;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class InstallCommand extends AbstractBaseCommand
{
    /** @var array */
    private $availableTypes = [
        EnvironmentEntity::TYPE_MAGENTO2,
        EnvironmentEntity::TYPE_SYLIUS,
        EnvironmentEntity::TYPE_SYMFONY,
    ];

    /** @var ProcessProxy */
    private $processProxy;

    /** @var DockerHub */
    private $dockerHub;

    /** @var ValidatorInterface */
    private $validator;

    /** @var ConfigurationInstaller */
    private $installer;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var RequirementsChecker */
    private $requirementsChecker;

    public function __construct(
        ProcessProxy $processProxy,
        DockerHub $dockerHub,
        ValidatorInterface $validator,
        ConfigurationInstaller $installer,
        EventDispatcherInterface $eventDispatcher,
        RequirementsChecker $requirementsChecker,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->processProxy = $processProxy;
        $this->dockerHub = $dockerHub;
        $this->validator = $validator;
        $this->installer = $installer;
        $this->eventDispatcher = $eventDispatcher;
        $this->requirementsChecker = $requirementsChecker;
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
        $io = new SymfonyStyle($input, $output);
        $io->note('The environment will be installed in the current directory.');

        try {
            $location = $this->processProxy->getWorkingDirectory();
            $name = $this->askEnvironmentName($io, basename($location));
            $type = $this->askEnvironmentType($io);
            $phpVersion = $this->askPhpVersion($type, $io);
            $domains = $this->requirementsChecker->canMakeLocallyTrustedCertificates()
                ? $this->askDomains($type, $io) : null;

            $environment = $this->installer->install($name, $location, $type, $phpVersion, $domains);

            $event = new EnvironmentInstalledEvent($environment, $io);
            $this->eventDispatcher->dispatch($event);

            $io->success('Environment successfully installed.');
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }

    /**
     * Asks the question about the environment name.
     */
    private function askEnvironmentName(SymfonyStyle $io, string $defaultName): string
    {
        return $io->ask('What is the name of the environment you want to install?', $defaultName);
    }

    /**
     * Asks the choice question about the environment type.
     */
    private function askEnvironmentType(SymfonyStyle $io): string
    {
        return $io->choice('Which type of environment you want to install?', $this->availableTypes);
    }

    /**
     * Asks the choice question about the PHP version.
     */
    private function askPhpVersion(string $type, SymfonyStyle $io): string
    {
        $availableVersions = $this->dockerHub->getImageTags("{$type}-php");
        $defaultVersion = DockerHub::DEFAULT_IMAGE_VERSION;

        return \count($availableVersions) > 1
            ? $io->choice('Which version of PHP do you want to use?', $availableVersions, $defaultVersion)
            : $availableVersions[0]
        ;
    }

    /**
     * Asks the question about the environment domains.
     */
    private function askDomains(string $type, SymfonyStyle $io): ?string
    {
        if ($io->confirm('Do you want to generate a locally-trusted development certificate?', false)) {
            $domains = $io->ask(
                'Which domains does this certificate belong to?',
                sprintf('%s.localhost www.%s.localhost', $type, $type),
                function (string $answer) {
                    return $this->localDomainsCallback($answer);
                }
            );
        }

        return $domains ?? null;
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
