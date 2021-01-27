<?php

declare(strict_types=1);

namespace App\Environment\Configuration;

use App\Environment\EnvironmentEntity;
use App\Exception\FilesystemException;
use App\Exception\InvalidEnvironmentException;
use App\Exception\MkcertException;

class ConfigurationUpdater extends AbstractConfiguration
{
    /**
     * Updates the Docker environment configuration.
     *
     * @throws FilesystemException|InvalidEnvironmentException|MkcertException
     */
    public function update(
        EnvironmentEntity $environment,
        string $phpVersion,
        string $databaseVersion,
        ?string $domains = null
    ): void {
        if ($environment->isActive()) {
            throw new InvalidEnvironmentException('Unable to update a running environment.');
        }

        $source = __DIR__."/../../Resources/{$environment->getType()}";
        $destination = $environment->getLocation().self::INSTALLATION_DIRECTORY;

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
    }
}
