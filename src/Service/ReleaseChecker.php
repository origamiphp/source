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
🎉 A new version is available: <fg=green;options=bold>%s</>, currently running <fg=red;options=bold>%s</>!
    Consider upgrading soon with <fg=yellow;options=bold>composer global update ajardin/origami</>.

TEXT;

    private ApplicationVersion $applicationVersion;
    private Packagist $packagist;
    private GitHub $github;

    public function __construct(ApplicationVersion $applicationVersion, Packagist $packagist, GitHub $github)
    {
        $this->applicationVersion = $applicationVersion;
        $this->packagist = $packagist;
        $this->github = $github;
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
            $release = strpos($currentStatus, '@') === false
                ? $this->processStableRelease($currentStatus)
                : $this->processDevRelease($currentStatus)
            ;
        } catch (OrigamiExceptionInterface $exception) {
            return; // The checks on release availability must be non-blocking for the users.
        }

        if ($release) {
            $this->printMessage($io, $release, $currentStatus);
        }
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

        if (strpos($releaseCommit, $currentCommit) === 0) {
            return null;
        }

        $matches = [];
        preg_match('/^Update to version v(?<version>\d\.\d\.\d)/', $releaseCommit, $matches);

        return $matches['version'] ?? substr($releaseCommit, 0, \strlen($currentCommit));
    }

    /**
     * Prints the upgrade suggestion in the console.
     */
    private function printMessage(OrigamiStyle $io, string $release, string $currentStatus): void
    {
        $version = strpos($currentStatus, '@') !== false
            ? explode('@', $currentStatus)[1]
            : $currentStatus
        ;

        $message = sprintf(self::CONSOLE_MESSAGE, $release, $version);
        $io->text($message);
    }
}
