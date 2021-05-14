<?php

declare(strict_types=1);

namespace App\ValueObject;

/**
 * @codeCoverageIgnore
 */
class TechnologyDetails
{
    private string $name;
    private string $version;

    public function __construct(string $technology, string $version)
    {
        $this->name = $technology;
        $this->version = $version;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }
}
