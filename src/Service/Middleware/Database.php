<?php

declare(strict_types=1);

namespace App\Service\Middleware;

use App\Exception\DatabaseException;
use App\Exception\FilesystemException;
use App\Service\Middleware\Binary\Docker;
use Ergebnis\Environment\Variables;

class Database
{
    private const DATABASE_PLACEHOLDER = '# <== DATABASE PLACEHOLDER ==>';
    private const SUPPORTED_DATABASE_TYPES = ['mariadb', 'mysql', 'postgres'];

    public const DEFAULT_BACKUP_FILENAME = 'origami_backup.sql';
    public const DEFAULT_SERVICE_PASSWORD = 'YourPwdShouldBeLongAndSecure';
    public const DEFAULT_SERVICE_DATABASE = 'origami';

    private Docker $docker;
    private Variables $systemVariables;

    public function __construct(Docker $docker, Variables $systemVariables)
    {
        $this->docker = $docker;
        $this->systemVariables = $systemVariables;
    }

    /**
     * Triggers the database dump process according to the database type.
     *
     * @throws DatabaseException
     */
    public function dump(string $path): void
    {
        if (($databaseImage = $this->systemVariables->get('DOCKER_DATABASE_IMAGE')) === '') {
            throw new DatabaseException('Unable to retrieve the database image from environment variables.');
        }

        switch ($this->extractDatabaseImageName($databaseImage)) {
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
     */
    public function restore(string $path): void
    {
        if (($databaseImage = $this->systemVariables->get('DOCKER_DATABASE_IMAGE')) === '') {
            throw new DatabaseException('Unable to retrieve the database image from environment variables.');
        }

        if (!is_file($path)) {
            throw new DatabaseException('Unable to find the backup file to restore.');
        }

        switch ($this->extractDatabaseImageName($databaseImage)) {
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
     * Extracts the image name from the given string (expecting "name:tag" format).
     *
     * @throws DatabaseException
     */
    private function extractDatabaseImageName(string $databaseImage): string
    {
        $matches = [];
        if (!preg_match('/^(?<type>[[:alpha:]]+):.+$/', $databaseImage, $matches)) {
            throw new DatabaseException('Unable to extract the database type from the string.');
        }

        return $matches['type'];
    }
}
