<?php

declare(strict_types=1);

namespace App\Environment\Configuration;

use App\Environment\EnvironmentEntity;
use App\Exception\FilesystemException;
use App\Exception\MkcertException;

class ConfigurationInstaller extends AbstractConfiguration
{
    /**
     * Installs the Docker environment configuration.
     *
     * @throws FilesystemException|MkcertException
     */
    public function install(
        string $location,
        string $name,
        string $type,
        string $phpVersion,
        string $databaseVersion,
        ?string $domains = null
    ): EnvironmentEntity {
        $source = __DIR__."/../../Resources/{$type}";
        $destination = $location.self::INSTALLATION_DIRECTORY;

        $this->copyEnvironmentFiles($source, $destination);
        $configuration = "{$destination}/.env";

        $this->updateEnvironment($configuration, self::DATABASE_IMAGE_OPTION_NAME, $databaseVersion);
        $this->updateEnvironment($configuration, self::PHP_IMAGE_OPTION_NAME, $phpVersion);

        $this->loadBlackfireParameters($destination);

        if ($domains !== null) {
            $certificate = "{$destination}/nginx/certs/custom.pem";
            $privateKey = "{$destination}/nginx/certs/custom.key";

            $this->mkcert->generateCertificate($certificate, $privateKey, explode(' ', $domains));
        }

        return new EnvironmentEntity($name, $location, $type, $domains);
    }
}
