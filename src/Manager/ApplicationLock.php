<?php

declare(strict_types=1);

namespace App\Manager;

use App\Exception\ApplicationLockException;
use Symfony\Component\Cache\Simple\FilesystemCache;

class ApplicationLock
{
    private const LOCK_CACHE_KEY = 'project.location';

    /**
     * Generates the lock entry used to avoid duplicate launches.
     *
     * @param string $project
     *
     * @throws ApplicationLockException
     */
    public function generateLock(string $project): void
    {
        try {
            $cache = new FilesystemCache();
            $cache->set(self::LOCK_CACHE_KEY, $project);
        } catch (\Psr\SimpleCache\InvalidArgumentException $e) {
            throw new ApplicationLockException($e->getMessage());
        }
    }

    /**
     * Retrieves the current lock entry.
     *
     * @return string
     */
    public function getCurrentLock(): string
    {
        $cache = new FilesystemCache();

        try {
            $project = $cache->get(self::LOCK_CACHE_KEY, false);
            $result = !empty($project) ? $project : '';
        } catch (\Psr\SimpleCache\InvalidArgumentException $e) {
            $result = '';
        }

        return $result;
    }

    /**
     * Removes the lock entry used to avoid duplicate launches.
     */
    public function removeLock(): void
    {
        $cache = new FilesystemCache();
        $cache->delete(self::LOCK_CACHE_KEY);
    }
}
