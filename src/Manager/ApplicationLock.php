<?php

declare(strict_types=1);

namespace App\Manager;

use App\Exception\ApplicationLockException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\CacheItem;

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
        $cache = new FilesystemAdapter();

        /** @var CacheItem $cacheItem */
        $cacheItem = $cache->getItem(self::LOCK_CACHE_KEY);
        $cacheItem->set($project);

        if (!$cache->save($cacheItem)) {
            throw new ApplicationLockException('Unable to generate the application lock.');
        }
    }

    /**
     * Retrieves the current lock entry.
     *
     * @return string
     */
    public function getCurrentLock(): string
    {
        $cache = new FilesystemAdapter();
        $cacheItem = $cache->getItem(self::LOCK_CACHE_KEY);

        return $cacheItem->isHit() ? $cacheItem->get() : '';
    }

    /**
     * Removes the lock entry used to avoid duplicate launches.
     *
     * @throws ApplicationLockException
     */
    public function removeLock(): void
    {
        try {
            $cache = new FilesystemAdapter();
            $cache->delete(self::LOCK_CACHE_KEY);
        } catch (InvalidArgumentException $e) {
            throw new ApplicationLockException($e->getMessage());
        }
    }
}
