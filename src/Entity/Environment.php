<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @codeCoverageIgnore
 *
 * @ORM\Entity(repositoryClass="App\Repository\EnvironmentRepository")
 * @UniqueEntity(fields={"name"}, message="There is already an environment of the same name.")
 */
class Environment
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=32, unique=true)
     */
    private string $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $location;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private string $type;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $domains;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private bool $active;

    public function __construct(
        string $name,
        string $location,
        string $type,
        ?string $domains = null,
        bool $active = false
    ) {
        $this->name = $name;
        $this->location = $location;
        $this->type = $type;
        $this->domains = $domains;
        $this->active = $active;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDomains(): ?string
    {
        return $this->domains;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }
}
