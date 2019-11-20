<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Helper\ReleaseHandler;
use App\Kernel;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 *
 * @covers \App\Helper\ReleaseHandler
 */
final class ReleaseHandlerTest extends WebTestCase
{
    private $location;

    /**
     * {@inheritDoc).
     */
    protected function setUp(): void
    {
        $this->location = sys_get_temp_dir().'/origami/ReleaseHandlerTest';
    }

    /**
     * {@inheritDoc).
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_dir($this->location)) {
            $filesystem = new Filesystem();
            $filesystem->remove($this->location);
        }

        $this->location = null;
    }

    public function testItClearsTheCacheWhenTrackerIsAbsent(): void
    {
        $cacheDir = $this->location.\DIRECTORY_SEPARATOR.'cache';
        mkdir($cacheDir, 0777, true);
        static::assertDirectoryExists($cacheDir);

        $trackerFile = $this->location.\DIRECTORY_SEPARATOR.'.release';
        static::assertFileNotExists($trackerFile);

        $kernel = $this->prophesize(Kernel::class);
        $kernel->getCustomDir()->shouldBeCalledTimes(3)->willReturn($this->location);
        $kernel->getCacheDir()->shouldBeCalledOnce()->willReturn($this->location.\DIRECTORY_SEPARATOR.'cache');

        $io = $this->prophesize(SymfonyStyle::class);
        $io->note('The cache has been cleared since the application seems to have been upgraded.')->shouldBeCalledOnce();

        $releaseHandler = new ReleaseHandler($kernel->reveal(), $io->reveal());
        $releaseHandler->verify();

        static::assertDirectoryNotExists($cacheDir);
        static::assertStringEqualsFile($trackerFile, '@git_version@');
    }

    public function testItClearsTheCacheWhenTrackerIsOutdated(): void
    {
        $cacheDir = $this->location.\DIRECTORY_SEPARATOR.'cache';
        mkdir($cacheDir, 0777, true);
        static::assertDirectoryExists($cacheDir);

        $trackerFile = $this->location.\DIRECTORY_SEPARATOR.'.release';
        file_put_contents($trackerFile, 'azerty');
        static::assertStringEqualsFile($trackerFile, 'azerty');

        $kernel = $this->prophesize(Kernel::class);
        $kernel->getCustomDir()->shouldBeCalledTimes(4)->willReturn($this->location);
        $kernel->getCacheDir()->shouldBeCalledOnce()->willReturn($this->location.\DIRECTORY_SEPARATOR.'cache');

        $io = $this->prophesize(SymfonyStyle::class);
        $io->note('The cache has been cleared since the application seems to have been upgraded.')->shouldBeCalledOnce();

        $releaseHandler = new ReleaseHandler($kernel->reveal(), $io->reveal());
        $releaseHandler->verify();

        static::assertDirectoryNotExists($cacheDir);
        static::assertStringEqualsFile($trackerFile, '@git_version@');
    }

    public function testItClearsTheCacheWhenTrackerIsUpToDate(): void
    {
        $cacheDir = $this->location.\DIRECTORY_SEPARATOR.'cache';
        mkdir($cacheDir, 0777, true);
        static::assertDirectoryExists($cacheDir);

        $trackerFile = $this->location.\DIRECTORY_SEPARATOR.'.release';
        file_put_contents($trackerFile, '@git_version@');
        static::assertStringEqualsFile($trackerFile, '@git_version@');

        $kernel = $this->prophesize(Kernel::class);
        $kernel->getCustomDir()->shouldBeCalledTimes(3)->willReturn($this->location);
        $kernel->getCacheDir()->shouldNotBeCalled();

        $io = $this->prophesize(SymfonyStyle::class);
        $io->note(Argument::type('string'))->shouldNotBeCalled();

        $releaseHandler = new ReleaseHandler($kernel->reveal(), $io->reveal());
        $releaseHandler->verify();

        static::assertDirectoryExists($cacheDir);
        static::assertStringEqualsFile($trackerFile, '@git_version@');
    }
}
