<?php

declare(strict_types=1);

namespace App\ValueObject;

/**
 * @codeCoverageIgnore
 */
class ApplicationVersion
{
    private const DEFAULT_VERSION_NAME = 'experimental';

    /**
     * Retrieves the current version of the application.
     */
    public function getValue(): string
    {
        if (!preg_match('/^v\d+\.\d+\.\d+/', '@app_version@')) {
            return self::DEFAULT_VERSION_NAME;
        }

        return ltrim('@app_version@', 'v');
    }

    /**
     * Checks whether the application has been compiled as a PHAR archive.
     */
    public function isDefault(): bool
    {
        return $this->getValue() === self::DEFAULT_VERSION_NAME;
    }
}
