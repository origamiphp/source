<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exception\UnsupportedOperatingSystemException;
use App\Helper\ProcessFactory;

class Hosts
{
    private ProcessFactory $processFactory;

    public function __construct(ProcessFactory $processFactory)
    {
        $this->processFactory = $processFactory;
    }

    /**
     * Checks whether the system hosts file has the given domains.
     *
     * @throws UnsupportedOperatingSystemException
     */
    public function hasDomains(string $domains): bool
    {
        $filename = $this->getHostsFile();
        $pattern = "/^127\\.0\\.0\\.1.+{$domains}$/m";

        return (bool) preg_match($pattern, file_get_contents($filename));
    }

    /**
     * Add the given domains to the system hosts file.
     *
     * @throws UnsupportedOperatingSystemException
     */
    public function fixHostsFile(string $domains): void
    {
        $this->processFactory->runForegroundProcessFromShellCommandLine(
            "echo '127.0.0.1 {$domains}' | sudo tee -a {$this->getHostsFile()} > /dev/null"
        );
    }

    /**
     * Retrieves the path to the system hosts file.
     *
     * @throws UnsupportedOperatingSystemException
     */
    private function getHostsFile(): string
    {
        if (\in_array(PHP_OS_FAMILY, ['Darwin', 'Linux'], true)) {
            return '/etc/hosts';
        }

        throw new UnsupportedOperatingSystemException(sprintf('%s is not yet supported.', PHP_OS_FAMILY)); // @codeCoverageIgnore
    }
}
