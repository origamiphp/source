<?php

declare(strict_types=1);

namespace App\ValueObject;

/**
 * @codeCoverageIgnore
 */
class PrepareAnswers
{
    /**
     * @param array<string, string> $settings
     */
    public function __construct(
        private string $name,
        private string $location,
        private string $type,
        private array $settings
    ) {
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

    /**
     * @return array<string, string>
     */
    public function getSettings(): array
    {
        return $this->settings;
    }
}
