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
        if ($environment->getType() === EnvironmentEntity::TYPE_CUSTOM) {
            throw new InvalidEnvironmentException('Unable to update a custom environment.');
        }

        if ($environment->isActive()) {
            throw new InvalidEnvironmentException('Unable to update a running environment.');
        }

        $source = __DIR__.sprintf('/../../Resources/%s', $environment->getType());
        $destination = sprintf('%s/var/docker', $environment->getLocation());

        $this->copyEnvironmentFiles($source, $destination);
        $configuration = sprintf('%s/.env', $destination);

        // Replace the PHP version that was previously used.
        $phpVersion = $this->getPhpVersion($configuration);
        $this->updateEnvironment($configuration, self::PHP_IMAGE_OPTION_NAME, $phpVersion);

        $this->loadBlackfireParameters($destination);
    }
}
