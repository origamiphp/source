<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Entity\Environment;
use App\Exception\InvalidEnvironmentException;
use App\Helper\ProcessFactory;
use App\Middleware\Binary\Mkcert;
use App\Repository\EnvironmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SystemManager
{
    /** @var Mkcert */
    private $mkcert;

    /** @var ValidatorInterface */
    private $validator;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var EnvironmentRepository */
    private $environmentRepository;

    /** @var ProcessFactory */
    private $processFactory;

    /**
     * SystemManager constructor.
     */
    public function __construct(
        Mkcert $mkcert,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        EnvironmentRepository $environmentRepository,
        ProcessFactory $processFactory
    ) {
        $this->mkcert = $mkcert;
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->environmentRepository = $environmentRepository;
        $this->processFactory = $processFactory;
    }

    /**
     * Installs the Docker environment configuration.
     *
     * @throws InvalidEnvironmentException
     */
    public function install(string $location, string $type, ?string $domains = null): void
    {
        $source = __DIR__."/../Resources/{$type}";
        $destination = "{$location}/var/docker";

        $filesystem = new Filesystem();
        $this->copyEnvironmentFiles($filesystem, $source, $destination);

        if ($domains !== null) {
            $certificate = "{$destination}/nginx/certs/custom.pem";
            $privateKey = "{$destination}/nginx/certs/custom.key";

            $this->mkcert->generateCertificate($certificate, $privateKey, explode(' ', $domains));
        }

        $this->addNewEnvironment(
            [
                'name' => basename($location),
                'location' => $location,
                'type' => $type,
                'domains' => $domains,
            ]
        );
    }

    /**
     * Retrieves the environment associated to the given name.
     */
    public function getEnvironmentByName(string $name): ?Environment
    {
        return $this->environmentRepository->findOneBy(['name' => $name]);
    }

    /**
     * Retrieves the environment associated to the given location.
     */
    public function getEnvironmentByLocation(string $location): ?Environment
    {
        return $this->environmentRepository->findOneBy(['location' => $location]);
    }

    /**
     * Retrieves the currently active environment.
     */
    public function getActiveEnvironment(): ?Environment
    {
        return $this->environmentRepository->findOneBy(['active' => true]);
    }

    /**
     * Retrieves all the environments.
     */
    public function getAllEnvironments(): array
    {
        return $this->environmentRepository->findAll();
    }

    /**
     * Uninstalls the Docker environment configuration.
     */
    public function uninstall(Environment $environment): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove("{$environment->getLocation()}/var/docker");

        $this->removeExistingEnvironment($environment);
    }

    /**
     * Checks whether the given binary is available.
     */
    public function isBinaryInstalled(string $binary): bool
    {
        return $this->processFactory->runBackgroundProcess(['which', $binary])->isSuccessful();
    }

    /**
     * Prepare the project directory with environment files.
     */
    private function copyEnvironmentFiles(Filesystem $filesystem, string $source, string $destination): void
    {
        // Create the directory where all configuration files will be stored
        $filesystem->mkdir($destination);

        // Copy the environment files into the project directory
        $filesystem->mirror($source, $destination);
    }

    /**
     * Adds a new environment entry in the database.
     *
     * @throws InvalidEnvironmentException
     */
    private function addNewEnvironment(array $details): void
    {
        $environment = new Environment();
        $environment->setName($details['name'] ?? '');
        $environment->setLocation($details['location'] ?? '');
        $environment->setType($details['type'] ?? '');
        $environment->setDomains($details['domains'] ?? '');

        $errors = $this->validator->validate($environment);
        if ($errors->count() > 0) {
            throw new InvalidEnvironmentException($errors->get(0)->getMessage());
        }

        $this->entityManager->persist($environment);
        $this->entityManager->flush();
    }

    /**
     * Removes an existing environment entry from the database.
     */
    private function removeExistingEnvironment(Environment $environment): void
    {
        $this->entityManager->remove($environment);
        $this->entityManager->flush();
    }
}
