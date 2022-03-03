<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\InvalidEnvironmentException;
use App\ValueObject\EnvironmentCollection;
use App\ValueObject\EnvironmentEntity;
use Ergebnis\Environment\Variables;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ApplicationData
{
    /** @deprecated */
    public const OLDER_DATA_FILENAME = '.origami';

    public const DATA_FILE_FOLDER = '.config'.\DIRECTORY_SEPARATOR.'origami';
    public const DATA_FILE_PATH = self::DATA_FILE_FOLDER.\DIRECTORY_SEPARATOR.'.environments';

    private string $path;
    private Serializer $serializer;
    private EnvironmentCollection $environments;

    /**
     * @throws IOException
     */
    public function __construct(Variables $systemVariables)
    {
        $home = PHP_OS_FAMILY !== 'Windows'
            ? $systemVariables->get('HOME')                                                     // @codeCoverageIgnore
            : $systemVariables->get('HOMEDRIVE').$systemVariables->get('HOMEPATH')              // @codeCoverageIgnore
        ;

        $olderPath = $home.\DIRECTORY_SEPARATOR.self::OLDER_DATA_FILENAME;

        $directoryPath = $home.\DIRECTORY_SEPARATOR.self::DATA_FILE_FOLDER;
        $this->path = $home.\DIRECTORY_SEPARATOR.self::DATA_FILE_PATH;

        $filesystem = new Filesystem();

        if (!$filesystem->exists($directoryPath)) {
            try {
                $filesystem->mkdir($directoryPath);
            } catch (IOExceptionInterface) {
                throw new IOException('Unable to create the directory '.$directoryPath);
            }
        }

        /* @deprecated */
        if ($filesystem->exists($olderPath)) {
            try {
                $filesystem->rename($olderPath, $this->path);
            } catch (IOExceptionInterface) {
                throw new IOException(sprintf('Unable to rename the data file from %s to %s ', $olderPath, $this->path));
            }
        }

        if (!$filesystem->exists($this->path)) {
            try {
                $filesystem->touch($this->path);
            } catch (IOExceptionInterface) {
                throw new IOException('Unable to create the data file at '.$this->path);
            }
        }

        $this->serializer = new Serializer([new ObjectNormalizer(), new ArrayDenormalizer()], [new JsonEncoder()]);
        $this->environments = new EnvironmentCollection($this->getRegisteredEnvironments());
    }

    /**
     * Retrieves all the environments currently registered.
     */
    public function getAllEnvironments(): EnvironmentCollection
    {
        return $this->environments;
    }

    /**
     * Retrieves the active registered environment.
     */
    public function getActiveEnvironment(): ?EnvironmentEntity
    {
        foreach ($this->environments as $environment) {
            if ($environment->isActive()) {
                return $environment;
            }
        }

        return null;
    }

    /**
     * Retrieves a registered environment by its name.
     */
    public function getEnvironmentByName(string $name): ?EnvironmentEntity
    {
        foreach ($this->environments as $environment) {
            if ($name === $environment->getName()) {
                return $environment;
            }
        }

        return null;
    }

    /**
     * Retrieves a registered environment by its location.
     */
    public function getEnvironmentByLocation(string $location): ?EnvironmentEntity
    {
        foreach ($this->environments as $environment) {
            if ($location === $environment->getLocation()) {
                return $environment;
            }
        }

        return null;
    }

    /**
     * Adds a new environment to the list of registered environments without saving the state.
     *
     * @throws InvalidEnvironmentException
     */
    public function add(EnvironmentEntity $environment): void
    {
        $this->environments->add($environment);
    }

    /**
     * Removes an environment to the list of registered environments without saving the state.
     */
    public function remove(EnvironmentEntity $environment): void
    {
        $this->environments->remove($environment);
    }

    /**
     * Saves the registered environments state in the data file.
     */
    public function save(): void
    {
        file_put_contents(
            $this->path,
            $this->serializer->serialize($this->environments, 'json')
        );
    }

    /**
     * Extracts the registered environments from the data file.
     *
     * @return EnvironmentEntity[]
     */
    private function getRegisteredEnvironments(): array
    {
        if ($records = file_get_contents($this->path)) {
            $entities = $this->serializer->deserialize($records, 'App\ValueObject\EnvironmentEntity[]', 'json');
            if (\is_array($entities)) {
                return $entities;
            }
        }

        return [];
    }
}
