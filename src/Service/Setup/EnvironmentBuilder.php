<?php

declare(strict_types=1);

namespace App\Service\Setup;

use App\Exception\FilesystemException;
use App\Exception\InvalidConfigurationException;
use App\Service\RequirementsChecker;
use App\Service\Wrapper\OrigamiStyle;
use App\Service\Wrapper\ProcessProxy;
use App\ValueObject\EnvironmentEntity;
use App\ValueObject\PrepareAnswers;
use Composer\Semver\VersionParser;

class EnvironmentBuilder
{
    private TechnologyIdentifier $technologyIdentifier;
    private ProcessProxy $processProxy;
    private RequirementsChecker $requirementsChecker;
    private Validator $validator;
    private array $requirements;

    /**
     * @param array[] $requirements
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
    public function prepare(OrigamiStyle $io, ?EnvironmentEntity $environment = null): PrepareAnswers
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
     * Asks question about the environment name.
     */
    private function askEnvironmentName(OrigamiStyle $io, string $defaultName): string
    {
        return $io->ask('What is the <options=bold>name</> of the environment you want to install?', $defaultName);
    }

    /**
     * Asks choice question about the environment type.
     */
    private function askEnvironmentType(OrigamiStyle $io, ?string $assumption): string
    {
        return $io->choice(
            'Which <options=bold>type</> of environment you want to install?',
            EnvironmentEntity::AVAILABLE_TYPES,
            $assumption
        );
    }

    /**
     * Asks choice question about the environment version.
     */
    private function askEnvironmentVersion(OrigamiStyle $io, string $type, ?string $assumption): string
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
     * Asks choice questions about the environment settings.
     *
     * @return array<string, string>
     */
    private function askEnvironmentSettings(OrigamiStyle $io, string $type, string $version): array
    {
        $settings = [];

        foreach ($this->requirements[$type][$version] as $service => $choices) {
            /** @var string $choice */
            $choice = $io->choice(
                "Which <options=bold>{$service}</> version do you want to use in your project?",
                $choices,
                $choices[0],
            );

            $settings[(string) $service] = $choice;
        }

        return $settings;
    }

    /**
     * Asks question about the environment domains.
     */
    private function askDomains(OrigamiStyle $io, string $name): ?string
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
