<?php

declare(strict_types=1);

namespace App\Environment;

/**
 * @codeCoverageIgnore
 */
class EnvironmentEntity
{
    /** @var string */
    public const TYPE_CUSTOM = 'custom';

    /** @var string */
    public const TYPE_MAGENTO2 = 'magento2';

    /** @var string */
    public const TYPE_SYLIUS = 'sylius';

    /** @var string */
    public const TYPE_SYMFONY = 'symfony';

    /** @var string */
    private $name;

    /** @var string */
    private $location;

    /** @var string */
    private $type;

    /** @var null|string */
    private $domains;

    /** @var bool */
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
