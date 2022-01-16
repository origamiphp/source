<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\GitHubException;
use App\Exception\OrigamiExceptionInterface;
use App\Exception\PackagistException;
use App\Service\Middleware\Api\GitHub;
use App\Service\Middleware\Api\Packagist;
use App\Service\Wrapper\OrigamiStyle;
use App\ValueObject\ApplicationVersion;
use Composer\Semver\VersionParser;

class ReleaseChecker
{
    private const CONSOLE_MESSAGE = <<<'TEXT'
<options=bold>There is a new version available for download.</>

Consider upgrading (from <fg=green;options=bold>%s</> to <fg=red;options=bold>%s</>) by following the steps described below.
  👉 Retrieve the latest version of the tool with <fg=yellow;options=bold>composer global update ajardin/origami</>.
  👉 Update your local environment configuration with <fg=yellow;options=bold>origami update</>.

TEXT;

    public function __construct(
        private ApplicationVersion $applicationVersion,
        private Packagist $packagist,
        private GitHub $github
    ) {
    }

    /**
     * Checks whether there is a more recent release.
     */
    public function validate(OrigamiStyle $io): void
    {
        if ($this->applicationVersion->isDefault()) {
            return;
        }

        $currentStatus = $this->applicationVersion->getValue();

        try {
            $release = str_contains($currentStatus, '@')
                ? $this->processDevRelease($currentStatus)
                : $this->processStableRelease($currentStatus)
            ;
        } catch (OrigamiExceptionInterface) {
            return; // The checks on release availability must be non-blocking for the users.
        }

        if ($release) {
            $this->printMessage($io, $release, $currentStatus);
        }
    }

    /**
     * Compares the current status to the latest available "dev" release.
     *
     * @throws PackagistException
     * @throws GitHubException
     */
    private function processDevRelease(string $currentStatus): ?string
    {
        if (!$latestRelease = $this->packagist->getLatestDevRelease()) {
            throw new PackagistException('Unable to retrieve the latest "dev" release.');
        }

        if (!$message = $this->github->getCommitMessage($latestRelease['source']['reference'])) {
            throw new GitHubException('Unable to retrieve the commit message.');
        }

        $currentCommit = explode('@', $currentStatus)[1];
        $releaseCommit = basename($message);

        if (str_starts_with($releaseCommit, $currentCommit)) {
            return null;
        }

        preg_match('/^Update to version v(?<version>\d\.\d\.\d)/', $releaseCommit, $matches);

        return $matches['version'] ?? substr($releaseCommit, 0, \strlen($currentCommit));
    }

    /**
     * Compares the current status to the latest available "stable" release.
     *
     * @throws PackagistException
     */
    private function processStableRelease(string $currentStatus): ?string
    {
        if (!$latestRelease = $this->packagist->getLatestStableRelease()) {
            throw new PackagistException('Unable to retrieve the latest "stable" release.');
        }

        $currentVersionNormalized = (new VersionParser())->normalize($currentStatus);
        if ($currentVersionNormalized === $latestRelease['version_normalized']) {
            return null;
        }

        return ltrim($latestRelease['version'], 'v');
    }

    /**
     * Prints the upgrade suggestion in the console.
     */
    private function printMessage(OrigamiStyle $io, string $release, string $currentStatus): void
    {
        $version = str_contains($currentStatus, '@')
            ? explode('@', $currentStatus)[1]
            : $currentStatus
        ;

        $message = sprintf(self::CONSOLE_MESSAGE, $release, $version);
        $io->text($message);
    }
}
