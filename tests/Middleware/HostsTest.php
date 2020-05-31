<?php

declare(strict_types=1);

namespace App\Tests\Middleware;

use App\Helper\ProcessFactory;
use App\Middleware\Hosts;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;

/**
 * @internal
 *
 * @covers \App\Middleware\Hosts
 */
final class HostsTest extends TestCase
{
    /** @var Prophet */
    private $prophet;

    /** @var ObjectProphecy */
    private $processFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prophet = new Prophet();
        $this->processFactory = $this->prophet->prophesize(ProcessFactory::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->prophet->checkPredictions();
    }

    /**
     * @throws \App\Exception\UnsupportedOperatingSystemException
     */
    public function testItFindsExistingDomains(): void
    {
        $hosts = new Hosts($this->processFactory->reveal());
        static::assertTrue($hosts->hasDomains('localhost'));
    }

    /**
     * @throws \App\Exception\UnsupportedOperatingSystemException
     */
    public function testItDoesNotFindExistingDomains(): void
    {
        $hosts = new Hosts($this->processFactory->reveal());
        static::assertFalse($hosts->hasDomains('azertyuiopqsdfghjklmwxcvbn'));
    }

    /**
     * @throws \App\Exception\UnsupportedOperatingSystemException
     */
    public function testItTriggersTheFixingProcess(): void
    {
        (new MethodProphecy($this->processFactory, 'runForegroundProcessFromShellCommandLine', ["echo '127.0.0.1 origami.localhost' | sudo tee -a /etc/hosts > /dev/null"]))
            ->shouldBeCalledOnce()
        ;

        $hosts = new Hosts($this->processFactory->reveal());
        $hosts->fixHostsFile('origami.localhost');

        // Temporary workaround to avoid the test being marked as risky.
        static::assertTrue(true);
    }
}
