<?php

declare(strict_types=1);

namespace App\Service\Setup;

use App\Exception\FilesystemException;
use App\Exception\InvalidConfigurationException;
use App\Exception\MkcertException;
use App\Service\Middleware\Binary\Mkcert;
use App\ValueObject\EnvironmentEntity;
use Symfony\Component\Filesystem\Filesystem;

class ConfigurationFiles
{
    public const INSTALLATION_DIRECTORY = '/var/docker';
    private const DATABASE_PLACEHOLDER = '# <== DATABASE PLACEHOLDER ==>';

    public function __construct(private Mkcert $mkcert)
    {
    }

    /**
     * Installs the Docker environment configuration.
     *
     * @throws FilesystemException
     * @throws MkcertException
     * @throws InvalidConfigurationException
     */
    public function install(EnvironmentEntity $environment, array $settings): void
    {
        $source = __DIR__."/../../Resources/docker-templates/{$environment->getType()}";
        $destination = $environment->getLocation().self::INSTALLATION_DIRECTORY;

        $this->copyConfiguration($source, $destination);

        if (isset($settings['database'])) {
            $this->replaceDatabasePlaceholder($settings['database'], $destination);
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
     * Replaces the database placeholder in the environment configuration by the fragment associated to the given image.
     *
     * @throws FilesystemException
     * @throws InvalidConfigurationException
     */
    public function replaceDatabasePlaceholder(string $image, string $destination): void
    {
        if (!preg_match('/^(?<type>[[:alpha:]]+(\/[[:alpha:]]+)?):.+$/', $image, $matches)) {
            throw new InvalidConfigurationException('');
        }

        $fragment = __DIR__."/../../Resources/docker-fragments/{$matches['type']}.yml";
        if (!$service = file_get_contents($fragment)) {
            // @codeCoverageIgnoreStart
            throw new FilesystemException(sprintf("Unable to load the database fragment.\n%s", $fragment));
            // @codeCoverageIgnoreEnd
        }

        $filename = "{$destination}/docker-compose.yml";
        if (!$content = file_get_contents($filename)) {
            // @codeCoverageIgnoreStart
            throw new FilesystemException(sprintf("Unable to load the configuration content.\n%s", $filename));
            // @codeCoverageIgnoreEnd
        }

        if (!file_put_contents($filename, str_replace(self::DATABASE_PLACEHOLDER, rtrim($service), $content))) {
            // @codeCoverageIgnoreStart
            throw new FilesystemException(sprintf("Unable to update the environment configuration.\n%s", $filename));
            // @codeCoverageIgnoreEnd
        }
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
