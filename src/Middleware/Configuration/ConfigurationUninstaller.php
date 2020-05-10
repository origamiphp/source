<?php

declare(strict_types=1);

namespace App\Middleware\Configuration;

use App\Environment\EnvironmentEntity;
use Symfony\Component\Filesystem\Filesystem;

class ConfigurationUninstaller extends AbstractConfiguration
{
    /**
     * Uninstalls the Docker environment configuration.
     */
    public function uninstall(EnvironmentEntity $environment): void
    {
        if ($environment->getType() !== EnvironmentEntity::TYPE_CUSTOM) {
            $filesystem = new Filesystem();
            $filesystem->remove(sprintf('%s/var/docker', $environment->getLocation()));
        }
    }
}
