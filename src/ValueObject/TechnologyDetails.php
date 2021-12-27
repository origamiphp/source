<?php

declare(strict_types=1);

namespace App\ValueObject;

/**
 * @codeCoverageIgnore
 */
class TechnologyDetails
{
    public function __construct(
        private string $name,
        private string $version
    ) {
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
