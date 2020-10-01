<?php

declare(strict_types=1);

namespace App\Environment\Configuration;

use App\Environment\EnvironmentEntity;
use App\Exception\FilesystemException;
use App\Exception\InvalidEnvironmentException;

class ConfigurationUpdater extends AbstractConfiguration
{
    /**
     * Updates the Docker environment configuration.
     *
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function update(EnvironmentEntity $environment): void
    {
        if ($environment->isActive()) {
            throw new InvalidEnvironmentException('Unable to update a running environment.');
        }

        $source = __DIR__."/../../Resources/{$environment->getType()}";
        $destination = $environment->getLocation().self::INSTALLATION_DIRECTORY;

        $configuration = "{$destination}/.env";
        $phpVersion = $this->getPhpVersion($configuration);

        $this->copyEnvironmentFiles($source, $destination);
        $this->updateEnvironment($configuration, self::PHP_IMAGE_OPTION_NAME, $phpVersion);
        $this->loadBlackfireParameters($destination);
    }
}
