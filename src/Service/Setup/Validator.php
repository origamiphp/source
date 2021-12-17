<?php

declare(strict_types=1);

namespace App\Service\Setup;

use App\ValueObject\EnvironmentEntity;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Validator\Constraints\Hostname;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Validator
{
    public function __construct(
        private ValidatorInterface $symfonyValidator,
        private string $projectDir,
        private string $installDir
    ) {
    }

    /**
     * Checks whether the environment configuration is correctly installed.
     */
    public function validateConfigurationFiles(EnvironmentEntity $environment): bool
    {
        $filesystem = new Filesystem();

        $finder = new Finder();
        $finder->files()->in($this->projectDir."/src/Resources/templates/{$environment->getType()}")->depth(0);

        foreach ($finder as $file) {
            $filename = str_replace(
                'custom-',
                '',
                $environment->getLocation().$this->installDir.'/'.$file->getFilename(),
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
