<?php

declare(strict_types=1);

namespace App\Service\Setup;

use App\Exception\FilesystemException;
use App\Exception\InvalidConfigurationException;
use App\Service\Middleware\Wrapper\ProcessProxy;
use App\Service\RequirementsChecker;
use App\ValueObject\EnvironmentEntity;
use App\ValueObject\PrepareAnswers;
use Composer\Semver\VersionParser;
use Symfony\Component\Console\Style\SymfonyStyle;

class EnvironmentBuilder
{
    private TechnologyIdentifier $technologyIdentifier;
    private ProcessProxy $processProxy;
    private RequirementsChecker $requirementsChecker;
    private Validator $validator;
    private array $requirements;

    /**
     * @param mixed[] $requirements
     */
    public function __construct(
        ProcessProxy $processProxy,
        TechnologyIdentifier $technologyIdentifier,
        RequirementsChecker $requirementsChecker,
        Validator $validator,
        array $requirements
    ) {
        $this->technologyIdentifier = $technologyIdentifier;
        $this->processProxy = $processProxy;
        $this->requirementsChecker = $requirementsChecker;
        $this->validator = $validator;
        $this->requirements = $requirements;
    }

    /**
     * Prepares the installation by asking interactive questions to the user.
     *
     * @throws FilesystemException
     */
    public function prepare(SymfonyStyle $io, ?EnvironmentEntity $environment = null): PrepareAnswers
    {
        $location = $this->processProxy->getWorkingDirectory();
        $technology = $this->technologyIdentifier->identify($location);

        if ($environment !== null) {
            $name = $environment->getName();
            $type = $environment->getType();
            $domains = $environment->getDomains();
        } else {
            $name = $this->askEnvironmentName($io, basename($location));
            $type = $this->askEnvironmentType($io, $technology !== null ? $technology->getName() : null);
        }

        $version = $this->askEnvironmentVersion($io, $type, $technology !== null ? $technology->getVersion() : null);
        $settings = $this->askEnvironmentSettings($io, $type, $version);

        if ($environment === null) {
            if ($this->requirementsChecker->canMakeLocallyTrustedCertificates()) {
                $domains = $this->askDomains($io, $name);
            } else {
                $io->warning('Generation of the locally-trusted development certificate skipped.');
            }
        }

        return new PrepareAnswers($name, $location, $type, $domains ?? null, $settings);
    }

    /**
     * Asks the question about the environment name.
     */
    private function askEnvironmentName(SymfonyStyle $io, string $defaultName): string
    {
        return $io->ask('What is the <options=bold>name</> of the environment you want to install?', $defaultName);
    }

    /**
     * Asks the choice question about the environment type.
     */
    private function askEnvironmentType(SymfonyStyle $io, ?string $assumption): string
    {
        return $io->choice(
            'Which <options=bold>type</> of environment you want to install?',
            EnvironmentEntity::AVAILABLE_TYPES,
            $assumption
        );
    }

    /**
     * Asks the choice question about the environment version.
     */
    private function askEnvironmentVersion(SymfonyStyle $io, string $type, ?string $assumption): string
    {
        if ($assumption) {
            $version = $this->parseVersionAssumption($assumption);
            $default = isset($this->requirements[$type][$version]) ? $version : null;
        }

        return $io->choice(
            'Which <options=bold>version</> are you using in your project?',
            array_keys($this->requirements[$type]),
            $default ?? null
        );
    }

    /**
     * Asks the choice questions about the environment settings.
     *
     * @return array<int|string, mixed>
     */
    private function askEnvironmentSettings(SymfonyStyle $io, string $type, string $version): array
    {
        $settings = [];

        foreach ($this->requirements[$type][$version] as $service => $choices) {
            $settings[$service] = $io->choice(
                "Which <options=bold>{$service}</> version do you want to use in your project?",
                $choices,
                $choices[0],
            );
        }

        return $settings;
    }

    /**
     * Asks the question about the environment domains.
     */
    private function askDomains(SymfonyStyle $io, string $name): ?string
    {
        if ($io->confirm('Do you want to generate a locally-trusted development <options=bold>certificate</>?', false)) {
            $domains = $io->ask(
                'Which <options=bold>domains</> does this certificate belong to?',
                "{$name}.test",
                fn (string $answer): string => $this->localDomainsCallback($answer)
            );
        }

        return $domains ?? null;
    }

    /**
     * Parses the given version to retrieve a X.Y format.
     */
    private function parseVersionAssumption(string $assumption): string
    {
        $versionParser = new VersionParser();
        $lowerBoundVersion = $versionParser->parseConstraints($assumption)->getLowerBound()->getVersion();
        $version = $versionParser->normalize($lowerBoundVersion);

        $matches = [];
        if (preg_match('/(?<version>\d+.\d+)/', $version, $matches)) {
            $version = $matches['version'];
        }

        return $version;
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
