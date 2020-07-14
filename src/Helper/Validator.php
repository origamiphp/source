<?php

declare(strict_types=1);

namespace App\Helper;

use App\Environment\Configuration\AbstractConfiguration;
use App\Environment\EnvironmentEntity;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Validator\Constraints\Hostname;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Validator
{
    /** @var ValidatorInterface */
    private $symfonyValidator;

    /** @var string */
    private $projectDir;

    public function __construct(ValidatorInterface $symfonyValidator, string $projectDir)
    {
        $this->symfonyValidator = $symfonyValidator;
        $this->projectDir = $projectDir;
    }

    /**
     * Checks whether the environment has a .env file.
     */
    public function validateDotEnvExistence(EnvironmentEntity $environment): bool
    {
        $filesystem = new Filesystem();

        $configuration = $environment->getLocation().AbstractConfiguration::INSTALLATION_DIRECTORY.'.env';
        if (!$filesystem->exists($configuration)) {
            return false;
        }

        return true;
    }

    /**
     * Checks whether the environment configuration is correctly installed.
     */
    public function validateConfigurationFiles(EnvironmentEntity $environment): bool
    {
        $filesystem = new Filesystem();

        $finder = new Finder();
        $finder->files()->in($this->projectDir."/src/Resources/{$environment->getType()}")->depth(0);

        foreach ($finder as $file) {
            $filename = str_replace(
                'custom-',
                '',
                $environment->getLocation().AbstractConfiguration::INSTALLATION_DIRECTORY.$file->getFilename(),
            );

            if (!$filesystem->exists($filename)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks whether the given hostname is valid for local use.
     */
    public function validateHostname(string $hostname): bool
    {
        $constraint = new Hostname(['requireTld' => false]);
        $errors = $this->symfonyValidator->validate($hostname, $constraint);

        return !$errors->has(0);
    }
}
