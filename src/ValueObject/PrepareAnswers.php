<?php

declare(strict_types=1);

namespace App\ValueObject;

/**
 * @codeCoverageIgnore
 */
class PrepareAnswers
{
    private string $name;
    private string $location;
    private string $type;
    private array $settings;
    private ?string $domains;

    /**
     * @param array<string, string> $settings
     */
    public function __construct(
        string $name,
        string $location,
        string $type,
        ?string $domains,
        array $settings
    ) {
        $this->name = $name;
        $this->location = $location;
        $this->type = $type;
        $this->domains = $domains;
        $this->settings = $settings;
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

    /**
     * @return array[]
     */
    public function getSettings(): array
    {
        return $this->settings;
    }
}
