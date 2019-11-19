<?php

declare(strict_types=1);

namespace App\Helper;

use App\Exception\InvalidConfigurationException;
use App\Kernel;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class ReleaseHandler
{
    private const TRACKER_FILENAME = '.release';

    private $kernel;
    private $io;

    /**
     * ReleaseTracker constructor.
     *
     * @param Kernel $kernel
     */
    public function __construct(KernelInterface $kernel, SymfonyStyle $io)
    {
        $this->kernel = $kernel;
        $this->io = $io;
    }

    /**
     * Triggers a manual cache clearing if the tracker does not contain the current application version.
     */
    public function verify(): void
    {
        $this->createProjectDirectory();

        if ($this->isTrackerUpToDate() !== true) {
            $filesystem = new Filesystem();
            $filesystem->remove($this->kernel->getCacheDir());

            $this->io->note('The cache has been cleared since the application seems to have been upgraded.');
            $this->refreshTrackerValue();
        }
    }

    /**
     * Creates the directory where the database, the cache and the logs will be stored.
     *
     * @throws InvalidConfigurationException
     */
    private function createProjectDirectory(): void
    {
        $directory = $this->kernel->getCustomDir();

        if (!is_dir($directory)
            && !mkdir($concurrentDirectory = $directory, 0777, true) && !is_dir($concurrentDirectory)
        ) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory)); // @codeCoverageIgnore
        }
    }

    /**
     * Checks whether the tracker should be refreshed with the current version of the application.
     */
    private function isTrackerUpToDate(): bool
    {
        return file_exists($this->getAbsolutePath()) && file_get_contents($this->getAbsolutePath()) === '@git_version@';
    }

    /**
     * Refreshes the tracker value with the current version of the application.
     */
    private function refreshTrackerValue(): bool
    {
        return file_put_contents($this->getAbsolutePath(), '@git_version@') !== false;
    }

    /**
     * Retrieves the absolute path to the release tracker.
     */
    private function getAbsolutePath(): string
    {
        return $this->kernel->getCustomDir().\DIRECTORY_SEPARATOR.self::TRACKER_FILENAME;
    }
}
