<?php

declare(strict_types=1);

namespace App\Tests;

use App\Service\Setup\ConfigurationFiles;
use App\ValueObject\EnvironmentEntity;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

trait TestEnvironmentTrait
{
    private string $location = '';

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->location = sys_get_temp_dir()
            .\DIRECTORY_SEPARATOR.'origami'
            .\DIRECTORY_SEPARATOR.(new ReflectionClass(static::class))->getShortName()
        ;

        $destination = $this->location.ConfigurationFiles::INSTALLATION_DIRECTORY;
        mkdir($destination, 0777, true);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_dir($this->location)) {
            $filesystem = new Filesystem();
            $filesystem->remove($this->location);
        }

        $this->location = '';
    }

    /**
     * Retrieves a new fake Environment instance.
     */
    private function createEnvironment(): EnvironmentEntity
    {
        return new EnvironmentEntity(
            'origami',
            $this->location,
            EnvironmentEntity::TYPE_SYMFONY
        );
    }

    /**
     * Installs the configuration associated to the given environment into the temporary test directory.
     */
    private function installEnvironmentConfiguration(EnvironmentEntity $environment): void
    {
        $filesystem = new Filesystem();
        $destination = $this->location.ConfigurationFiles::INSTALLATION_DIRECTORY;

        if (is_dir(__DIR__.'/../src')) {
            $source = __DIR__."/../src/Resources/docker-templates/{$environment->getType()}/";
        } else {
            throw new RuntimeException('Unable to find the environment configuration to install.');
        }

        $filesystem->mirror($source, $destination);
    }
}
