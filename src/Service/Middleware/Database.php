<?php

declare(strict_types=1);

namespace App\Service\Middleware;

use App\Exception\FilesystemException;
use App\Exception\InvalidEnvironmentException;
use App\ValueObject\EnvironmentCollection;
use App\ValueObject\EnvironmentEntity;
use Ergebnis\Environment\Variables;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class Database
{
    public const DATABASE_FILENAME = '.origami';

    private string $path;
    private Serializer $serializer;
    private EnvironmentCollection $environments;

    /**
     * @throws FilesystemException
     */
    public function __construct(Variables $systemVariables)
    {
        $home = PHP_OS_FAMILY !== 'Windows'
            ? $systemVariables->get('HOME')                                                     // @codeCoverageIgnore
            : $systemVariables->get('HOMEDRIVE').$systemVariables->get('HOMEPATH')              // @codeCoverageIgnore
        ;

        $this->path = $home.\DIRECTORY_SEPARATOR.self::DATABASE_FILENAME;
        if (!@is_file($this->path) && !@touch($this->path)) {
            throw new FilesystemException('Unable to create the database file.');       // @codeCoverageIgnore
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
     * Saves the registered environments state in the database file.
     */
    public function save(): void
    {
        file_put_contents(
            $this->path,
            $this->serializer->serialize($this->environments, 'json')
        );
    }

    /**
     * Extracts the registered environments from the database file.
     *
     * @return \App\ValueObject\EnvironmentEntity[]
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
