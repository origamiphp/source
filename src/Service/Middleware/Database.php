<?php

declare(strict_types=1);

namespace App\Service\Middleware;

use App\Exception\DatabaseException;
use App\Exception\FilesystemException;
use App\Exception\InvalidConfigurationException;
use App\Service\CurrentContext;
use App\Service\Middleware\Binary\Docker;

class Database
{
    private const DATABASE_PLACEHOLDER = '# <== DATABASE PLACEHOLDER ==>';
    private const SUPPORTED_DATABASE_TYPES = ['mariadb', 'mysql', 'postgres'];

    public const DEFAULT_BACKUP_FILENAME = 'origami_backup.sql';
    public const DEFAULT_SERVICE_PASSWORD = 'YourPwdShouldBeLongAndSecure';
    public const DEFAULT_SERVICE_DATABASE = 'origami';

    private CurrentContext $currentContext;
    private Docker $docker;
    private string $installDir;

    public function __construct(CurrentContext $currentContext, Docker $docker, string $installDir)
    {
        $this->docker = $docker;
        $this->currentContext = $currentContext;
        $this->installDir = $installDir;
    }

    /**
     * Triggers the database dump process according to the database type.
     *
     * @throws DatabaseException
     * @throws InvalidConfigurationException
     * @throws FilesystemException
     */
    public function dump(string $path): void
    {
        switch ($this->getDatabaseType()) {
            case 'mariadb':
            case 'mysql':
                if (!$this->docker->dumpMysqlDatabase($path)) {
                    throw new DatabaseException('Unable to complete the MySQL dump process.');
                }
                break;

            case 'postgres':
                if (!$this->docker->dumpPostgresDatabase($path)) {
                    throw new DatabaseException('Unable to complete the Postgres dump process.');
                }
                break;

            default:
                throw new DatabaseException('The database type in use is not yet supported.');
        }
    }

    /**
     * Triggers the database restore process according to the database type.
     *
     * @throws DatabaseException
     * @throws InvalidConfigurationException
     * @throws FilesystemException
     */
    public function restore(string $path): void
    {
        if (!is_file($path)) {
            throw new DatabaseException('Unable to find the backup file to restore.');
        }

        switch ($this->getDatabaseType()) {
            case 'mariadb':
            case 'mysql':
                if (!$this->docker->restoreMysqlDatabase($path)) {
                    throw new DatabaseException('Unable to complete the MySQL restore process.');
                }
                break;

            case 'postgres':
                if (!$this->docker->restorePostgresDatabase($path)) {
                    throw new DatabaseException('Unable to complete the Postgres restore process.');
                }
                break;

            default:
                throw new DatabaseException('The database type in use is not yet supported.');
        }
    }

    /**
     * Replaces the database placeholder in the environment configuration by the fragment associated to the given image.
     *
     * @throws DatabaseException
     * @throws FilesystemException
     * @throws InvalidConfigurationException
     */
    public function replaceDatabasePlaceholder(string $image, string $destination): void
    {
        $type = $this->extractDatabaseImageName($image);
        if (!\in_array($type, self::SUPPORTED_DATABASE_TYPES, true)) {
            throw new DatabaseException('The database type in use is not yet supported.');
        }

        $fragment = __DIR__."/../../Resources/fragments/{$type}.yml";
        if (!$service = file_get_contents($fragment)) {
            // @codeCoverageIgnoreStart
            throw new FilesystemException(sprintf("Unable to load the database fragment.\n%s", $fragment));
            // @codeCoverageIgnoreEnd
        }

        $filename = "{$destination}/docker-compose.yml";
        if (!$content = file_get_contents($filename)) {
            // @codeCoverageIgnoreStart
            throw new FilesystemException(sprintf("Unable to load the configuration content.\n%s", $filename));
            // @codeCoverageIgnoreEnd
        }

        if (!file_put_contents($filename, str_replace(self::DATABASE_PLACEHOLDER, rtrim($service), $content))) {
            // @codeCoverageIgnoreStart
            throw new FilesystemException(sprintf("Unable to update the environment configuration.\n%s", $filename));
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Retrieves the database type from the environment "docker-compose.yml" file.
     *
     * @throws InvalidConfigurationException
     * @throws FilesystemException
     */
    private function getDatabaseType(): ?string
    {
        $environment = $this->currentContext->getActiveEnvironment();
        $configurationPath = $environment->getLocation().$this->installDir.'/docker-compose.yml';

        if (!is_file($configurationPath)) {
            throw new FilesystemException(sprintf("Unable to find the file.\n%s", $configurationPath));
        }

        if (!$configuration = file_get_contents($configurationPath)) {
            // @codeCoverageIgnoreStart
            throw new FilesystemException(sprintf("Unable to load the file content.\n%s", $configurationPath));
            // @codeCoverageIgnoreEnd
        }

        $matches = [];
        if (!preg_match_all('/image: (?<image>.+)/', $configuration, $matches)) {
            throw new InvalidConfigurationException('');
        }

        foreach ($matches['image'] as $match) {
            $serviceImage = $this->extractDatabaseImageName($match);
            if (\in_array($serviceImage, self::SUPPORTED_DATABASE_TYPES, true)) {
                return $serviceImage;
            }
        }

        return null;
    }

    /**
     * Extracts the image name from the given string (expecting "name:tag" format).
     *
     * @throws InvalidConfigurationException
     */
    private function extractDatabaseImageName(string $databaseImage): string
    {
        $matches = [];

        if (!preg_match('/^(?<type>[[:alpha:]]+(\/[[:alpha:]]+)?):.+$/', $databaseImage, $matches)) {
            throw new InvalidConfigurationException('');
        }

        return $matches['type'];
    }
}
