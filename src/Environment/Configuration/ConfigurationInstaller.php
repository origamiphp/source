<?php

declare(strict_types=1);

namespace App\Environment\Configuration;

use App\Environment\EnvironmentEntity;
use App\Exception\FilesystemException;

class ConfigurationInstaller extends AbstractConfiguration
{
    /**
     * Installs the Docker environment configuration.
     *
     * @throws FilesystemException
     */
    public function install(
        string $name,
        string $location,
        string $type,
        ?string $phpVersion = null,
        ?string $domains = null
    ): EnvironmentEntity {
        $source = __DIR__."/../../Resources/{$type}";
        $destination = $location.self::INSTALLATION_DIRECTORY;

        $this->copyEnvironmentFiles($source, $destination);
        $configuration = "{$destination}/.env";

        if ($phpVersion !== null) {
            $this->updateEnvironment($configuration, self::PHP_IMAGE_OPTION_NAME, $phpVersion);
        }

        $this->loadBlackfireParameters($destination);

        if ($domains !== null) {
            $certificate = "{$destination}/nginx/certs/custom.pem";
            $privateKey = "{$destination}/nginx/certs/custom.key";

            $this->mkcert->generateCertificate($certificate, $privateKey, explode(' ', $domains));
        }

        return new EnvironmentEntity($name, $location, $type, $domains);
    }
}
