<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Component\Filesystem\Filesystem;

trait TestLocationTrait
{
    private string $location;

    /**
     * Creates the temporary directory that will be used by the current test.
     */
    private function createLocation(): void
    {
        $this->location = sys_get_temp_dir()
            .\DIRECTORY_SEPARATOR.'origami'
            .\DIRECTORY_SEPARATOR.(new \ReflectionClass(static::class))->getShortName();

        mkdir($this->location, 0777, true);
    }

    /**
     * Removes the temporary directory that has been used by the current test.
     */
    private function removeLocation(): void
    {
        if (is_dir($this->location)) {
            $filesystem = new Filesystem();
            $filesystem->remove($this->location);
        }

        $this->location = '';
    }
}
