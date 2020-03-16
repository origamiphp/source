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
}
