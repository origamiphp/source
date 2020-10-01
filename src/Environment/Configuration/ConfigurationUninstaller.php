<?php

declare(strict_types=1);

namespace App\Environment\Configuration;

use App\Environment\EnvironmentEntity;
use Symfony\Component\Filesystem\Filesystem;

class ConfigurationUninstaller extends AbstractConfiguration
{
    /**
     * Uninstalls the Docker environment configuration.
     */
    public function uninstall(EnvironmentEntity $environment): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove($environment->getLocation().self::INSTALLATION_DIRECTORY);
    }
}
