<?php

declare(strict_types=1);

namespace App\Middleware\Configuration;

use App\Exception\FilesystemException;
use App\Middleware\Binary\Mkcert;
use App\Middleware\DockerHub;
use Symfony\Component\Filesystem\Filesystem;

class AbstractConfiguration
{
    protected const PHP_IMAGE_OPTION_NAME = 'DOCKER_PHP_IMAGE';

    /** @var Mkcert */
    protected $mkcert;

    public function __construct(Mkcert $mkcert)
    {
        $this->mkcert = $mkcert;
    }

    /**
     * Prepare the project directory with environment files.
     */
    protected function copyEnvironmentFiles(string $source, string $destination): void
    {
        $filesystem = new Filesystem();

        // Create the directory where all configuration files will be stored
        $filesystem->mkdir($destination);

        // Copy the environment files into the project directory
        $filesystem->mirror($source, $destination);
    }

    /**
     * Updates the PHP image version in the environment configuration.
     *
     * @throws FilesystemException
     */
    protected function updatePhpVersion(string $filename, string $phpVersion): void
    {
        if (!$configuration = file_get_contents($filename)) {
            // @codeCoverageIgnoreStart
            throw new FilesystemException(
                sprintf("Unable to load the environment configuration.\n%s", $filename)
            );
            // @codeCoverageIgnoreEnd
        }

        $pattern = sprintf('/%s=.*/', self::PHP_IMAGE_OPTION_NAME);
        $replacement = sprintf('%s=%s', self::PHP_IMAGE_OPTION_NAME, $phpVersion);

        if (!$updates = preg_replace($pattern, $replacement, $configuration)) {
            // @codeCoverageIgnoreStart
            throw new FilesystemException(
                sprintf("Unable to parse the environment configuration.\n%s", $filename)
            );
            // @codeCoverageIgnoreEnd
        }

        if (!file_put_contents($filename, $updates)) {
            // @codeCoverageIgnoreStart
            throw new FilesystemException(
                sprintf("Unable to update the environment configuration.\n%s", $filename)
            );
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Retrieves the value currently defined as the environment PHP version.
     *
     * @throws FilesystemException
     */
    protected function getPhpVersion(string $filename): string
    {
        if (!$configuration = file_get_contents($filename)) {
            // @codeCoverageIgnoreStart
            throw new FilesystemException(
                sprintf("Unable to load the environment configuration.\n%s", $filename)
            );
            // @codeCoverageIgnoreEnd
        }

        $pattern = sprintf('/%s=(?<version>.*)/', self::PHP_IMAGE_OPTION_NAME);
        $matches = [];

        if (preg_match($pattern, $configuration, $matches) !== false && $matches['version'] !== '') {
            return $matches['version'];
        }

        return DockerHub::DEFAULT_IMAGE_VERSION;
    }
}
