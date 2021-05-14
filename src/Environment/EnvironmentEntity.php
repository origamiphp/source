<?php

declare(strict_types=1);

namespace App\Environment;

/**
 * @codeCoverageIgnore
 */
class EnvironmentEntity
{
    public const TYPE_DRUPAL = 'drupal';
    public const TYPE_MAGENTO2 = 'magento2';
    public const TYPE_OROCOMMERCE = 'orocommerce';
    public const TYPE_SYLIUS = 'sylius';
    public const TYPE_SYMFONY = 'symfony';

    public const AVAILABLE_TYPES = [
        self::TYPE_DRUPAL,
        self::TYPE_MAGENTO2,
        self::TYPE_OROCOMMERCE,
        self::TYPE_SYLIUS,
        self::TYPE_SYMFONY,
    ];

    private string $name;
    private string $location;
    private string $type;
    private ?string $domains;
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

    public function activate(): void
    {
        $this->active = true;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }
}
