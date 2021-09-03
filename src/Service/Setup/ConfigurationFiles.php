<?php

declare(strict_types=1);

namespace App\Service\Setup;

use App\Exception\DatabaseException;
use App\Exception\FilesystemException;
use App\Exception\MkcertException;
use App\Service\Middleware\Binary\Mkcert;
use App\Service\Middleware\Database;
use App\ValueObject\EnvironmentEntity;
use Ergebnis\Environment\Variables;
use Symfony\Component\Filesystem\Filesystem;

class ConfigurationFiles
{
    private const BLACKFIRE_PARAMETERS = [
        'BLACKFIRE_CLIENT_ID',
        'BLACKFIRE_CLIENT_TOKEN',
        'BLACKFIRE_SERVER_ID',
        'BLACKFIRE_SERVER_TOKEN',
    ];
    public const INSTALLATION_DIRECTORY = '/var/docker';

    protected Mkcert $mkcert;
    protected Variables $systemVariables;
    protected Database $database;

    public function __construct(Mkcert $mkcert, Variables $systemVariables, Database $database)
    {
        $this->mkcert = $mkcert;
        $this->systemVariables = $systemVariables;
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
        $configuration = "{$destination}/.env";

        if (isset($settings['database'])) {
            $this->database->replaceDatabasePlaceholder($settings['database'], $destination);
        }

        foreach ($settings as $key => $value) {
            $this->fillDotEnvFile($configuration, 'DOCKER_'.strtoupper($key).'_IMAGE', $value);
        }

        $this->loadBlackfireParameters($destination);

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

        // Copy the common dotenv file into the project directory
        $filesystem->copy("{$source}/../.env", "{$destination}/.env", true);

        // Create the directory where Mkcert will store locally-trusted development certificate for Nginx
        $filesystem->mkdir("{$destination}/nginx/certs");
    }

    /**
     * Fills the environment dotenv file with the given parameter/value pair.
     *
     * @throws FilesystemException
     */
    private function fillDotEnvFile(string $filename, string $parameter, string $value): void
    {
        if (!$configuration = file_get_contents($filename)) {
            // @codeCoverageIgnoreStart
            throw new FilesystemException(sprintf("Unable to load the environment configuration.\n%s", $filename));
            // @codeCoverageIgnoreEnd
        }

        if (!$updates = preg_replace("/{$parameter}=.*/", "{$parameter}={$value}", $configuration)) {
            // @codeCoverageIgnoreStart
            throw new FilesystemException(sprintf("Unable to parse the environment configuration.\n%s", $filename));
            // @codeCoverageIgnoreEnd
        }

        if (!file_put_contents($filename, $updates)) {
            // @codeCoverageIgnoreStart
            throw new FilesystemException(sprintf("Unable to update the environment configuration.\n%s", $filename));
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Loads Blackfire credentials from the environment variables and updates the environment dotenv file.
     *
     * @throws FilesystemException
     */
    private function loadBlackfireParameters(string $destination): void
    {
        $filename = "{$destination}/.env";
        foreach (self::BLACKFIRE_PARAMETERS as $parameter) {
            if ($this->systemVariables->has($parameter)) {
                $this->fillDotEnvFile($filename, $parameter, $this->systemVariables->get($parameter));
            }
        }
    }
}
