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
    /** @var string */
    public const TYPE_CUSTOM = 'custom';

    /** @var string */
    public const TYPE_MAGENTO2 = 'magento2';

    /** @var string */
    public const TYPE_SYMFONY = 'symfony';

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=32, unique=true)
     *
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var string
     */
    private $location;

    /**
     * @ORM\Column(type="string", length=32)
     *
     * @var string
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @var null|string
     */
    private $domains;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     *
     * @var bool
     */
    private $active;

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
