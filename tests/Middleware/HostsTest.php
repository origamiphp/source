<?php

declare(strict_types=1);

namespace App\Tests\Middleware;

use App\Exception\UnsupportedOperatingSystemException;
use App\Helper\ProcessFactory;
use App\Middleware\Hosts;
use App\Tests\CustomProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @internal
 *
 * @covers \App\Middleware\Hosts
 */
final class HostsTest extends TestCase
{
    use CustomProphecyTrait;

    /**
     * @throws UnsupportedOperatingSystemException
     */
    public function testItFindsExistingDomains(): void
    {
        [$processFactory] = $this->prophesizeObjectArguments();
        $hosts = new Hosts($processFactory->reveal());

        static::assertTrue($hosts->hasDomains('localhost'));
    }

    /**
     * @throws UnsupportedOperatingSystemException
     */
    public function testItDoesNotFindExistingDomains(): void
    {
        [$processFactory] = $this->prophesizeObjectArguments();
        $hosts = new Hosts($processFactory->reveal());

        static::assertFalse($hosts->hasDomains('azertyuiopqsdfghjklmwxcvbn'));
    }

    /**
     * @throws UnsupportedOperatingSystemException
     */
    public function testItTriggersTheFixingProcess(): void
    {
        [$processFactory] = $this->prophesizeObjectArguments();
        $processFactory->runForegroundProcessFromShellCommandLine(Argument::type('string'))->shouldBeCalledOnce();

        $hosts = new Hosts($processFactory->reveal());
        $hosts->fixHostsFile('origami.localhost');
    }

    /**
     * {@inheritdoc}
     */
    protected function prophesizeObjectArguments(): array
    {
        return [
            $this->prophesize(ProcessFactory::class),
        ];
    }
}
