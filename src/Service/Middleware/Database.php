<?php

declare(strict_types=1);

namespace App\Service\Middleware;

use App\Exception\FilesystemException;
use App\Exception\InvalidConfigurationException;
use App\Service\ApplicationContext;
use App\Service\Middleware\Binary\Docker;

class Database
{
    private const SUPPORTED_DATABASE_TYPES = ['mariadb', 'mysql', 'postgres'];

    public const DEFAULT_BACKUP_FILENAME = 'origami_backup.sql';
    public const DEFAULT_SERVICE_PASSWORD = 'YourPwdShouldBeLongAndSecure';
    public const DEFAULT_SERVICE_DATABASE = 'origami';

    public function __construct(private ApplicationContext $applicationContext)
    {
    }

    /**
     * Retrieves the database type from the environment "docker-compose.yml" file.
     *
     * @throws InvalidConfigurationException
     * @throws FilesystemException
     */
    public function getDatabaseType(): string
    {
        $configuration = $this->applicationContext->getEnvironmentConfiguration();

        if (!isset($configuration['services']['database']['image'])
            || !preg_match('/^(?<type>[[:alpha:]]+(\/[[:alpha:]]+)?):.+$/', $configuration['services']['database']['image'], $matches)
            || !\in_array($matches['type'], self::SUPPORTED_DATABASE_TYPES, true)
        ) {
            throw new InvalidConfigurationException('The "database" service is misconfigured in the "docker-compose.yml" file.');
        }

        return $matches['type'];
    }

    /**
     * Retrieves the database version from the environment "docker-compose.yml" file.
     *
     * @throws InvalidConfigurationException
     * @throws FilesystemException
     */
    public function getDatabaseVersion(): string
    {
        $configuration = $this->applicationContext->getEnvironmentConfiguration();

        if (!isset($configuration['services']['database']['image'])
            || !preg_match('/^([[:alpha:]]+(\/[[:alpha:]]+)?):(?<version>.+)$/', $configuration['services']['database']['image'], $matches)
        ) {
            throw new InvalidConfigurationException('The "database" service is misconfigured in the "docker-compose.yml" file.');
        }

        return $matches['version'];
    }

    /**
     * Retrieves the username required to connect to the database.
     *
     * @throws FilesystemException
     * @throws InvalidConfigurationException
     */
    public function getDatabaseUsername(): string
    {
        return match ($this->getDatabaseType()) {
            'mariadb', 'mysql' => 'root',
            'postgres' => 'postgres',
            default => throw new InvalidConfigurationException('The database type in use is not yet supported.'),
        };
    }

    /**
     * Retrieves the password required to connect to the database.
     *
     * @throws FilesystemException
     * @throws InvalidConfigurationException
     */
    public function getDatabasePassword(): string
    {
        $databaseType = $this->getDatabaseType();

        $configuration = $this->applicationContext->getEnvironmentConfiguration();
        $environmentVariables = $configuration['services']['database']['environment'] ?? [];

        foreach ($environmentVariables as $variable) {
            if ($databaseType === 'mariadb'
                && preg_match('/^MARIADB_ROOT_PASSWORD=(?<password>.+)$/', $variable, $matches)
            ) {
                return $matches['password'];
            }

            if ($databaseType === 'mysql'
                && preg_match('/^MYSQL_ROOT_PASSWORD=(?<password>.+)$/', $variable, $matches)
            ) {
                return $matches['password'];
            }

            if ($databaseType === 'postgres'
                && preg_match('/^POSTGRES_PASSWORD=(?<password>.+)$/', $variable, $matches)
            ) {
                return $matches['password'];
            }
        }

        throw new InvalidConfigurationException('The "database" service is misconfigured in the "docker-compose.yml" file.');
    }
}
