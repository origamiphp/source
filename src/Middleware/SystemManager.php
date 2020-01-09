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
    private Mkcert $mkcert;

    private ValidatorInterface $validator;

    private EntityManagerInterface $entityManager;

    private EnvironmentRepository $environmentRepository;

    private ProcessFactory $processFactory;

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
        if ($type !== Environment::TYPE_CUSTOM) {
            $source = __DIR__.sprintf('/../Resources/%s', $type);
            $destination = sprintf('%s/var/docker', $location);

            $filesystem = new Filesystem();
            $this->copyEnvironmentFiles($filesystem, $source, $destination);

            if ($domains !== null) {
                $certificate = sprintf('%s/nginx/certs/custom.pem', $destination);
                $privateKey = sprintf('%s/nginx/certs/custom.key', $destination);

                $this->mkcert->generateCertificate($certificate, $privateKey, explode(' ', $domains));
            }
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
        if ($environment->getType() !== Environment::TYPE_CUSTOM) {
            $filesystem = new Filesystem();
            $filesystem->remove(sprintf('%s/var/docker', $environment->getLocation()));
        }

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
        $environment = new Environment(
            $details['name'] ?? '',
            $details['location'] ?? '',
            $details['type'] ?? '',
            $details['domains'] ?? ''
        );

        $errors = $this->validator->validate($environment);
        if ($errors->count() > 0) {
            /** @var string $message */
            $message = $errors->get(0)->getMessage();

            throw new InvalidEnvironmentException($message);
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
