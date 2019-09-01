<?php

declare(strict_types=1);

namespace App\Tests;

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @covers \App\Kernel
 */
final class KernelTest extends KernelTestCase
{
    public function testItRetrievesTheApplicationCustomDirectory(): void
    {
        /** @var Kernel $kernel */
        $kernel = self::bootKernel();
        $customDirectory = $kernel->getCustomDir();

        static::assertGreaterThan(\strlen('/.origami'), \strlen($customDirectory));
    }

    public function testItRetrievesTheCacheDirectoryAsProject(): void
    {
        /** @var Kernel $kernel */
        $kernel = self::bootKernel();

        static::assertSame($kernel->getProjectDir().'/var/cache/test', $kernel->getCacheDir());
    }

    public function testItRetrievesTheCacheDirectoryAsPhar(): void
    {
        $kernel = $this->getMockBuilder(Kernel::class)
            ->setConstructorArgs(['test', false])
            ->setMethods(['getProjectDir'])
            ->getMock()
        ;
        $kernel->method('getProjectDir')->willReturn('phar://azerty');

        static::assertTrue($kernel->isArchiveContext());
        static::assertSame($kernel->getCustomDir().'/cache', $kernel->getCacheDir());
    }

    public function testItRetrievesTheLogDirectoryAsProject(): void
    {
        /** @var Kernel $kernel */
        $kernel = self::bootKernel();

        static::assertSame($kernel->getProjectDir().'/var/log', $kernel->getLogDir());
    }

    public function testItRetrievesTheLogDirectoryAsPhar(): void
    {
        $kernel = $this->getMockBuilder(Kernel::class)
            ->setConstructorArgs(['test', false])
            ->setMethods(['getProjectDir'])
            ->getMock()
        ;
        $kernel->method('getProjectDir')->willReturn('phar://azerty');

        static::assertTrue($kernel->isArchiveContext());
        static::assertSame($kernel->getCustomDir().'/log', $kernel->getLogDir());
    }
}
