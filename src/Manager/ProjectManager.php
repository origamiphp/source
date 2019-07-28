<?php

declare(strict_types=1);

namespace App\Manager;

use App\Entity\Project;
use App\Exception\ProjectException;
use App\Exception\ProjectNotFoundException;
use App\Manager\Process\Mkcert;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProjectManager
{
    /** @var Mkcert */
    private $mkcert;

    /** @var ValidatorInterface */
    private $validator;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ProjectRepository */
    private $projectRepository;

    /**
     * ProjectManager constructor.
     *
     * @param Mkcert                 $mkcert
     * @param ValidatorInterface     $validator
     * @param EntityManagerInterface $entityManager
     * @param ProjectRepository      $projectRepository
     */
    public function __construct(
        Mkcert $mkcert,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        ProjectRepository $projectRepository
    ) {
        $this->mkcert = $mkcert;
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->projectRepository = $projectRepository;
    }

    /**
     * Installs the Docker environment configuration.
     *
     * @param string      $location
     * @param string      $type
     * @param string|null $domains
     *
     * @throws ProjectException
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

        $this->addNewProject(
            [
                'name' => basename($location),
                'location' => $location,
                'type' => $type,
                'domains' => $domains,
            ]
        );
    }

    /**
     * Retrieves the project associated to the given location.
     *
     * @param string $location
     *
     * @return Project|null
     */
    public function getLocationProject(string $location): ?Project
    {
        return $this->projectRepository->findOneBy(['location' => $location]);
    }

    /**
     * Retrieves the currently active project.
     *
     * @return Project|null
     */
    public function getActiveProject(): ?Project
    {
        return $this->projectRepository->findOneBy(['active' => true]);
    }

    /**
     * Uninstalls the Docker environment configuration.
     *
     * @param string $name
     *
     * @throws ProjectNotFoundException
     */
    public function uninstall(string $name): void
    {
        $project = $this->projectRepository->findOneBy(['name' => $name]);
        if (!$project instanceof Project) {
            throw new ProjectNotFoundException('Unable to find an environment for the specified project.');
        }

        $filesystem = new Filesystem();
        $filesystem->remove("{$project->getLocation()}/var/docker");

        $this->removeExistingProject($project);
    }

    /**
     * Copies all environment files into the project directory.
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
     * Adds a new project entry in the database.
     *
     * @param array $details
     *
     * @throws ProjectException
     */
    private function addNewProject(array $details): void
    {
        $newProject = new Project();
        $newProject->setName($details['name'] ?? '');
        $newProject->setLocation($details['location'] ?? '');
        $newProject->setType($details['type'] ?? '');
        $newProject->setDomains($details['domains'] ?? '');

        $errors = $this->validator->validate($newProject);
        if ($errors->count() > 0) {
            throw new ProjectException($errors->get(0)->getMessage());
        }

        $this->entityManager->persist($newProject);
        $this->entityManager->flush();
    }

    /**
     * Removes an existing project entry from the database.
     *
     * @param Project $project
     */
    private function removeExistingProject(Project $project): void
    {
        $this->entityManager->remove($project);
        $this->entityManager->flush();
    }
}
