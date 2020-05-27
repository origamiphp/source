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
        if ($type !== EnvironmentEntity::TYPE_CUSTOM) {
            $source = __DIR__.sprintf('/../../Resources/%s', $type);
            $destination = sprintf('%s/var/docker', $location);

            $this->copyEnvironmentFiles($source, $destination);

            if ($phpVersion !== null) {
                $this->updatePhpVersion(sprintf('%s/.env', $destination), $phpVersion);
            }

            if ($domains !== null) {
                $certificate = sprintf('%s/nginx/certs/custom.pem', $destination);
                $privateKey = sprintf('%s/nginx/certs/custom.key', $destination);

                $this->mkcert->generateCertificate($certificate, $privateKey, explode(' ', $domains));
            }
        }

        return new EnvironmentEntity($name, $location, $type, $domains);
    }
}
