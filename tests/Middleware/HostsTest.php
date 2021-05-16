<?php

declare(strict_types=1);

namespace App\Tests\Middleware;

use App\Exception\UnsupportedOperatingSystemException;
use App\Helper\ProcessFactory;
use App\Middleware\Hosts;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 *
 * @covers \App\Middleware\Hosts
 */
final class HostsTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @throws UnsupportedOperatingSystemException
     */
    public function testItFindsExistingDomains(): void
    {
        $processFactory = $this->prophesize(ProcessFactory::class);

        $hosts = new Hosts($processFactory->reveal());
        static::assertTrue($hosts->hasDomains('localhost'));
    }

    /**
     * @throws UnsupportedOperatingSystemException
     */
    public function testItDoesNotFindExistingDomains(): void
    {
        $processFactory = $this->prophesize(ProcessFactory::class);

        $hosts = new Hosts($processFactory->reveal());
        static::assertFalse($hosts->hasDomains('azertyuiopqsdfghjklmwxcvbn'));
    }

    /**
     * @throws UnsupportedOperatingSystemException
     */
    public function testItTriggersTheFixingProcess(): void
    {
        $processFactory = $this->prophesize(ProcessFactory::class);

        $processFactory
            ->runForegroundProcessFromShellCommandLine(Argument::type('string'))
            ->shouldBeCalledOnce()
        ;

        $hosts = new Hosts($processFactory->reveal());
        $hosts->fixHostsFile('mydomain.test');
    }
}
