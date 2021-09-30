<?php

declare(strict_types=1);

namespace App\Service\Setup;

use App\Exception\DatabaseException;
use App\Exception\FilesystemException;
use App\Exception\MkcertException;
use App\Service\Middleware\Binary\Mkcert;
use App\Service\Middleware\Database;
use App\ValueObject\EnvironmentEntity;
use Symfony\Component\Filesystem\Filesystem;

class ConfigurationFiles
{
    public const INSTALLATION_DIRECTORY = '/var/docker';

    protected Mkcert $mkcert;
    protected Database $database;

    public function __construct(Mkcert $mkcert, Database $database)
    {
        $this->mkcert = $mkcert;
        $this->database = $database;
    }

    /**
     * Installs the Docker environment configuration.
     *
     * @throws FilesystemException|MkcertException|DatabaseException
     */
    public function install(EnvironmentEntity $environment, array $settings): void
    {
        $source = __DIR__."/../../Resources/templates/{$environment->getType()}";
        $destination = $environment->getLocation().self::INSTALLATION_DIRECTORY;

        $this->copyConfiguration($source, $destination);

        if (isset($settings['database'])) {
            $this->database->replaceDatabasePlaceholder($settings['database'], $destination);
        }

        $this->fillDockerComposeYamlFile("{$destination}/docker-compose.yml", $settings);

        if ($domains = $environment->getDomains()) {
            $certificate = "{$destination}/nginx/certs/custom.pem";
            $privateKey = "{$destination}/nginx/certs/custom.key";

            $this->mkcert->generateCertificate($certificate, $privateKey, explode(' ', $domains));
        }
    }

    /**
     * Uninstalls the Docker environment configuration.
     */
    public function uninstall(EnvironmentEntity $environment): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove($environment->getLocation().self::INSTALLATION_DIRECTORY);
    }

    /**
     * Copies the common configuration into the project directory.
     */
    private function copyConfiguration(string $source, string $destination): void
    {
        $filesystem = new Filesystem();

        // Create the directory where all configuration files will be stored
        $filesystem->mkdir($destination);

        // Copy the environment files into the project directory
        $filesystem->mirror($source, $destination, null, ['override' => true]);

        // Create the directory where Mkcert will store locally-trusted development certificate for Nginx
        $filesystem->mkdir("{$destination}/nginx/certs");
    }

    /**
     * Fills the environment "docker-compose.yml" file with the given settings.
     *
     * @throws FilesystemException
     */
    private function fillDockerComposeYamlFile(string $filename, array $settings): void
    {
        if (!$configuration = file_get_contents($filename)) {
            // @codeCoverageIgnoreStart
            throw new FilesystemException(sprintf("Unable to load the environment configuration.\n%s", $filename));
            // @codeCoverageIgnoreEnd
        }

        foreach ($settings as $key => $value) {
            $parameter = 'DOCKER_'.strtoupper($key).'_IMAGE';
            $configuration = str_replace("\${{$parameter}}", $value, $configuration);
        }

        if (!file_put_contents($filename, $configuration)) {
            // @codeCoverageIgnoreStart
            throw new FilesystemException(sprintf("Unable to update the environment configuration.\n%s", $filename));
            // @codeCoverageIgnoreEnd
        }
    }
}
