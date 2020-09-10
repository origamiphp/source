<?php

declare(strict_types=1);

namespace App\Environment;

use App\Environment\EnvironmentMaker\DockerHub;
use App\Environment\EnvironmentMaker\RequirementsChecker;
use App\Environment\EnvironmentMaker\TechnologyIdentifier;
use App\Exception\InvalidConfigurationException;
use App\Helper\Validator;
use Symfony\Component\Console\Style\SymfonyStyle;

class EnvironmentMaker
{
    /** @var array */
    private $availableTypes = [
        EnvironmentEntity::TYPE_DRUPAL,
        EnvironmentEntity::TYPE_MAGENTO2,
        EnvironmentEntity::TYPE_SYLIUS,
        EnvironmentEntity::TYPE_SYMFONY,
    ];

    /** @var TechnologyIdentifier */
    private $technologyIdentifier;

    /** @var DockerHub */
    private $dockerHub;

    /** @var RequirementsChecker */
    private $requirementsChecker;

    /** @var Validator */
    private $validator;

    public function __construct(
        TechnologyIdentifier $technologyIdentifier,
        DockerHub $dockerHub,
        RequirementsChecker $requirementsChecker,
        Validator $validator
    ) {
        $this->technologyIdentifier = $technologyIdentifier;
        $this->dockerHub = $dockerHub;
        $this->requirementsChecker = $requirementsChecker;
        $this->validator = $validator;
    }

    /**
     * Asks the question about the environment name.
     */
    public function askEnvironmentName(SymfonyStyle $io, string $defaultName): string
    {
        return $io->ask('What is the name of the environment you want to install?', $defaultName);
    }

    /**
     * Asks the choice question about the environment type.
     */
    public function askEnvironmentType(SymfonyStyle $io, string $location): string
    {
        return $io->choice(
            'Which type of environment you want to install?',
            $this->availableTypes,
            $this->technologyIdentifier->identify($location)
        );
    }

    /**
     * Asks the choice question about the PHP version.
     */
    public function askPhpVersion(SymfonyStyle $io, string $type): string
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
    public function askDomains(SymfonyStyle $io, string $type): ?string
    {
        if (!$this->requirementsChecker->canMakeLocallyTrustedCertificates()) {
            $io->warning('Generation of the locally-trusted development certificate skipped because the tool is not installed.');

            return null;
        }

        if ($io->confirm('Do you want to generate a locally-trusted development certificate?', false)) {
            $domains = $io->ask(
                'Which domains does this certificate belong to?',
                "{$type}.localhost",
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
        if (!$this->validator->validateHostname($answer)) {
            throw new InvalidConfigurationException('The hostname provided is invalid.');
        }

        return $answer;
    }
}
