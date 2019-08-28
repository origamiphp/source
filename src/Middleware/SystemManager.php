<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Entity\Environment;
use App\Exception\InvalidEnvironmentException;
use App\Middleware\Binary\Mkcert;
use App\Repository\EnvironmentRepository;
use App\Traits\CustomProcessTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SystemManager
{
    use CustomProcessTrait;

    /** @var Mkcert */
    private $mkcert;

    /** @var ValidatorInterface */
    private $validator;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var EnvironmentRepository */
    private $environmentRepository;

    /**
     * SystemManager constructor.
     *
     * @param Mkcert                 $mkcert
     * @param ValidatorInterface     $validator
     * @param EntityManagerInterface $entityManager
     * @param EnvironmentRepository  $environmentRepository
     * @param LoggerInterface        $logger
     */
    public function __construct(
        Mkcert $mkcert,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        EnvironmentRepository $environmentRepository,
        LoggerInterface $logger
    ) {
        $this->mkcert = $mkcert;
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->environmentRepository = $environmentRepository;
        $this->logger = $logger;
    }

    /**
     * Installs the Docker environment configuration.
     *
     * @param string      $location
     * @param string      $type
     * @param string|null $domains
     *
     * @throws InvalidEnvironmentException
     */
    public function install(string $location, string $type, ?string $domains = null): void
    {
        $source = __DIR__."/../Resources/$type";
        $destination = "$location/var/docker";

        $filesystem = new Filesystem();
        $this->copyEnvironmentFiles($filesystem, $source, $destination);

        if ($domains !== null) {
            $certificate = "$destination/nginx/certs/custom.pem";
            $privateKey = "$destination/nginx/certs/custom.key";

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
     *
     * @param string $name
     *
     * @return Environment|null
     */
    public function getEnvironmentByName(string $name): ?Environment
    {
        return $this->environmentRepository->findOneBy(['name' => $name]);
    }

    /**
     * Retrieves the environment associated to the given location.
     *
     * @param string $location
     *
     * @return Environment|null
     */
    public function getEnvironmentByLocation(string $location): ?Environment
    {
        return $this->environmentRepository->findOneBy(['location' => $location]);
    }

    /**
     * Retrieves the currently active environment.
     *
     * @return Environment|null
     */
    public function getActiveEnvironment(): ?Environment
    {
        return $this->environmentRepository->findOneBy(['active' => true]);
    }

    /**
     * Retrieves all the environments.
     *
     * @return array
     */
    public function getAllEnvironments(): array
    {
        return $this->environmentRepository->findAll();
    }

    /**
     * Uninstalls the Docker environment configuration.
     *
     * @param Environment $environment
     */
    public function uninstall(Environment $environment): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove("{$environment->getLocation()}/var/docker");

        $this->removeExistingEnvironment($environment);
    }

    /**
     * Prepare the project directory with environment files.
     *
     * @param Filesystem $filesystem
     * @param string     $source
     * @param string     $destination
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
     * @param array $details
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
     *
     * @param Environment $environment
     */
    private function removeExistingEnvironment(Environment $environment): void
    {
        $this->entityManager->remove($environment);
        $this->entityManager->flush();
    }

    /**
     * Checks whether the given binary is available.
     *
     * @param string $binary
     *
     * @return bool
     */
    public function isBinaryInstalled(string $binary): bool
    {
        if (strpos($binary, '/') === false) {
            $process = $this->runBackgroundProcess(['which', $binary]);
            $result = $process->isSuccessful();
        } else {
            $process = $this->runBackgroundProcess(['brew', 'list']);
            $result = strpos($process->getOutput(), substr($binary, strrpos($binary, '/') + 1)) !== false;
        }

        return $result;
    }
}
