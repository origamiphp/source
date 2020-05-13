<?php

declare(strict_types=1);

namespace App\Middleware\Configuration;

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

        // Retrieve the PHP version currently configured.
        $phpVersion = $this->getPhpVersion(sprintf('%s/.env', $destination));

        // Copy all the default configuration files.
        $this->copyEnvironmentFiles($source, $destination);

        // Replace the PHP version that was previously used.
        $this->updatePhpVersion(sprintf('%s/.env', $destination), $phpVersion);
    }
}
